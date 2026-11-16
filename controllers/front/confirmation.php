<?php

include_once _PS_MODULE_DIR_.'billwerksubscription/classes/BillwerkConfirmationGuard.php';

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
        if (!in_array($order_type, ['ch', 's'], true) || !$this->context->customer->isLogged()) {
            $this->backToCheckout();
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

            $product = $this->module->getSubscriptionProduct($cart);
            $customer = new Customer($cart->id_customer);
            $sub_handle = 'sub-'.$cart->id;
            if ($product && !BillwerkSubscriptionSubscriptions::getById($sub_handle)) {
                $customer_handle = 'cust-'.$customer->id;
                $source = $this->getVerifiedPaymentMethod(Tools::getValue('payment_method'), $customer_handle);
                if (null === $source) {
                    $this->backToCheckout();
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

            $customer = new Customer($cart->id_customer);
            $shop_id = Context::getContext()->shop->id;
            $products = $cart->getProducts();
            $product = current($products);
            if (!BillwerkSubscriptionSubscriptions::getById($sub_handle)) {
                BillwerkSubscriptionSubscriptions::saveSubscription($shop_id, $cart->id, $product['id_product'],
                    $subscription['handle'], $subscription['plan'], $subscription['customer'], $customer->id);
            }
        }

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
