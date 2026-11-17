<?php

include_once _PS_MODULE_DIR_.'billwerksubscription/classes/BillwerkConfirmationGuard.php';
include_once _PS_MODULE_DIR_.'billwerksubscription/classes/BillwerkChargeAmount.php';

class BillwerkSubscriptionConfirmationModuleFrontController extends ModuleFrontControllerCore
{
    public function postProcess()
    {
        parent::postProcess();

        if (!Configuration::get('BILLWERKSUBSCRIPTION_ENABLED')) {
            exit('Frisbii Subscription is not enabled');
        }

        $order_type = Tools::getValue('t');
        $session_customer_id = (int) $this->context->customer->id;
        if (!in_array($order_type, ['ch', 's'], true)) {
            $this->backToCheckout();
        }
        if (!$this->context->customer->isLogged()) {
            // The customer may already have paid: resume this confirmation after login, never drop it
            $this->loginAndReturn($order_type);
        }

        if ('ch' == $order_type) {
            $cart_id = Tools::getValue('invoice');
            if (!is_string($cart_id) || !ctype_digit($cart_id)) {
                $this->backToCheckout();
            }

            $invoice = BillwerkPlusApi::getInvoice($cart_id);
            $invoice = $invoice['response'] ?? null;

            $cart = new Cart((int) $cart_id);
            if (!Validate::isLoadedObject($cart)
                || !BillwerkConfirmationGuard::isPaidInvoice($invoice, $cart->id)
                || !BillwerkConfirmationGuard::ownsCart($session_customer_id, $cart->id_customer)) {
                $this->backToCheckout();
            }

            // The cart must still need exactly what was paid (FP-34)
            $cart_currency = new Currency((int) $cart->id_currency);
            if (!BillwerkConfirmationGuard::amountMatches(
                $invoice['amount'] ?? null,
                BillwerkChargeAmount::forCart($cart),
                $invoice['currency'] ?? null,
                $cart_currency->iso_code
            )) {
                $this->refuse($cart, 'paid amount or currency does not match the cart', $this->undoChargeCallback($invoice, $cart));
            }

            $product = $this->module->getSubscriptionProduct($cart);
            $customer = new Customer($cart->id_customer);
            $sub_handle = 'sub-'.$cart->id;
            if ($product && !BillwerkSubscriptionSubscriptions::getById($sub_handle)) {
                $customer_handle = 'cust-'.$customer->id;
                $source = $this->getVerifiedPaymentMethod(Tools::getValue('payment_method'), $customer_handle);
                if (null === $source) {
                    // The charge is paid and owned by this customer, so release it instead of keeping it
                    $this->refuse($cart, 'the payment method could not be verified', $this->undoChargeCallback($invoice, $cart));
                }

                $data['handle'] = $sub_handle;
                $data['plan'] = $product['plan_handle'];
                $data['customer'] = $customer_handle;
                $data['source'] = $source;
                $result = $this->module->createSubscription($data);
                if (isset($result['response']['handle'])) {
                    BillwerkSubscriptionSubscriptions::saveSubscription($cart->id_shop, $cart->id, $product['id_product'],
                        $data['handle'], $product['plan_handle'], $customer_handle, $customer->id);
                }
            }
        }

        if ('s' == $order_type) {
            $sub_handle = Tools::getValue('subscription');
            if (!BillwerkSubscriptionSubscriptions::isValidHandle($sub_handle)) {
                $this->backToCheckout();
            }

            $subscription = BillwerkPlusApi::getSubscription($sub_handle);
            $subscription = $subscription['response'] ?? null;

            $cart = new Cart((int) substr($sub_handle, 4));
            if (!Validate::isLoadedObject($cart)
                || !BillwerkConfirmationGuard::ownsCart($session_customer_id, $cart->id_customer)
                || !BillwerkConfirmationGuard::isSubscriptionOf($subscription, $sub_handle, 'cust-'.$cart->id_customer)) {
                $this->backToCheckout();
            }

            // The cart must still hold only the subscription product the session was created for (FP-34)
            $products = $cart->getProducts();
            $product = current($products);
            if (!BillwerkConfirmationGuard::isCartUnchangedForSubscription(
                $products,
                $product ? BillwerkSubscriptionProduct::getByShopProductId($product['id_product'], $cart->id_shop) : null,
                $subscription
            )) {
                $this->refuse($cart, 'the cart changed after the subscription session was created', function () use ($sub_handle) {
                    // Ends the subscription now and refunds what was paid for it
                    return BillwerkPlusApi::expireSubscription($sub_handle, 'full_refund');
                });
            }

            // "active" is also true for a trial or a subscription in dunning: a payment must be settled (FP-34)
            if (!BillwerkConfirmationGuard::hasSettledPayment($subscription)) {
                // No order is made, so the live subscription must end too: it would bill later (end of trial) and
                // the customer could not start again (the handle is built from the cart id)
                $this->refuse($cart, 'the subscription has no settled payment (trial, dunning or unpaid)', function () use ($sub_handle) {
                    return BillwerkPlusApi::expireSubscription($sub_handle, 'full_refund');
                });
            }

            $customer = new Customer($cart->id_customer);
            $shop_id = Context::getContext()->shop->id;
            if (!BillwerkSubscriptionSubscriptions::getById($sub_handle)) {
                BillwerkSubscriptionSubscriptions::saveSubscription($shop_id, $cart->id, $product['id_product'],
                    $subscription['handle'], $subscription['plan'], $subscription['customer'], $customer->id);
            }
        }

        // The cart owner was checked above and equals the session customer: only their own key is ever used (FP-34)
        $customer = $this->context->customer;

        // order hasn't been placed with webhook yet
        if (!$cart->orderExists()) {
            $currency = $this->context->currency;

            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

            $order_status = _PS_OS_PAYMENT_;
            $this->module->validateOrder($cart->id, $order_status, $total, $this->module->displayName,
                null, null, (int) $currency->id, false, $customer->secure_key);
        }

        Tools::redirect('index.php?controller=order-confirmation&id_cart='.
            (int) $cart->id.'&id_module='.
            (int) $this->module->id.'&id_order='.
            (int) Order::getIdByCartId((int) $cart->id).'&key='.
            $customer->secure_key);
    }

    /**
     * Log why an order was refused, cancel the payment at Frisbii, then send the customer back to the checkout.
     * The payment is the customer's own (owner and invoice were checked first), so it is released or refunded.
     *
     * @param callable|null $undo returns the Frisbii API answer of the cancel or refund call
     */
    private function refuse($cart, $reason, $undo = null)
    {
        // Never undo a payment that already has an order
        if (null !== $undo && !$cart->orderExists()) {
            $answer = $undo();
            $code = (int) ($answer['code'] ?? 0);
            $reason .= 200 === $code
                ? '. The payment was cancelled or refunded at Frisbii'
                : sprintf('. The payment could NOT be cancelled at Frisbii (HTTP %d): cancel or refund it by hand', $code);
        }

        PrestaShopLogger::addLog(
            sprintf('Frisbii Subscription: order refused for cart %d: %s', (int) $cart->id, $reason),
            3,
            null,
            'Cart',
            (int) $cart->id,
            true
        );
        $this->backToCheckout();
    }

    /**
     * An authorized charge is released; a settled one is refunded in full.
     *
     * @return callable
     */
    private function undoChargeCallback($invoice, $cart)
    {
        return function () use ($invoice, $cart) {
            if ('settled' === $invoice['state']) {
                return BillwerkPlusApi::refundInvoice((string) $cart->id);
            }

            return BillwerkPlusApi::cancelCharge((string) $cart->id);
        };
    }

    /**
     * Send a logged-out customer to the login page and bring them back to this same confirmation.
     * Ownership is checked after login, so nothing is refunded or ordered for an unknown session.
     */
    private function loginAndReturn($order_type)
    {
        $query = ['t' => $order_type];
        foreach (['invoice', 'subscription', 'payment_method'] as $key) {
            $value = Tools::getValue($key);
            if (is_string($value) && preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $value)) {
                $query[$key] = $value;
            }
        }

        $back = $this->context->link->getModuleLink('billwerksubscription', 'confirmation', $query, true);
        Tools::redirect($this->context->link->getPageLink('authentication', true, null, ['back' => $back]));
    }

    private function backToCheckout()
    {
        Tools::redirect('index.php?controller=order&step=3');
    }

    private function getVerifiedPaymentMethod($payment_method, $customer_handle)
    {
        if (!is_string($payment_method) || !preg_match('/^[A-Za-z0-9_-]{1,64}$/D', $payment_method)) {
            return null;
        }

        $result = BillwerkPlusApi::getPaymentMethod($payment_method);
        if (200 == $result['code'] && BillwerkConfirmationGuard::isActiveMethodOf($result['response'], $customer_handle)) {
            return $payment_method;
        }

        return null;
    }
}
