<?php

class BillwerkSubscriptionConfirmationModuleFrontController extends ModuleFrontControllerCore
{
    public function postProcess()
    {
        parent::postProcess();

        if (!Configuration::get('BILLWERKSUBSCRIPTION_ENABLED')) {
            exit('Frisbii Subscriptions is not enabled');
        }

        $order_type = Tools::getValue('t');
        if (!$order_type) {
            Tools::redirect('index.php?controller=order&step=3');
        }

        if ('ch' == $order_type) {
            $invoice = BillwerkPlusApi::getInvoice(Tools::getValue('invoice'));
            $invoice = $invoice['response'];
            if (!isset($invoice['handle'])) {
                Tools::redirect('index.php?controller=order&step=3');
            }

            if ('ch' != $invoice['type']) {
                Tools::redirect('index.php?controller=order&step=3');
            }

            $cart = new Cart(Tools::getValue('invoice'));
            $product = $this->module->getSubscriptionProduct($cart);
            $customer = new Customer($cart->id_customer);
            if ($product) {
                $customer_handle = 'cust-'.$customer->id;
                $data['handle'] = 'sub-'.$cart->id;
                $data['plan'] = $product['plan_handle'];
                $data['customer'] = $customer_handle;
                $data['source'] = Tools::getValue('payment_method');
                $result = $this->module->createSubscription($data);
                if (isset($result['response']['handle'])) {
                    BillwerkSubscriptionSubscriptions::saveSubscription($cart->id_shop, $cart->id, $product['id_product'],
                        $data['handle'], $product['plan_handle'], $customer_handle, $customer->id);
                }
            }
        }

        if ('s' == $order_type) {
            $subscription = BillwerkPlusApi::getSubscription(Tools::getValue('subscription'));
            $subscription = $subscription['response'] ?? null;

            if (!(isset($subscription['state']) && 'active' == $subscription['state'])) {
                Tools::redirect('index.php?controller=order&step=3');
            }

            $cart = new Cart(substr(Tools::getValue('subscription'), 4));
            $customer = new Customer($cart->id_customer);
            $shop_id = Context::getContext()->shop->id;
            $products = $cart->getProducts();
            $product = current($products);
            BillwerkSubscriptionSubscriptions::saveSubscription($shop_id, $cart->id, $product['id_product'],
                $subscription['handle'], $subscription['plan'], $subscription['customer'], $customer->id);
        }

        // order hasn't been placed with webhook yet
        if (!$cart->orderExists()) {
            $currency = $this->context->currency;

            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);
            if (isset($subscription_handle['in_trial']) && $subscription_handle['in_trial']) {
                $total = 0;
            }

            $order_status = Configuration::get('BILLWERKSUBSCRIPTION_ORDER_DEFAULT_STATUS_AFTER_CREATION');
            $this->module->validateOrder($cart->id, $order_status, $total, $this->module->displayName,
                null, null, (int) $currency->id, false, $customer->secure_key);
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.
                (int) $cart->id.'&id_module='.
                (int) $this->module->id.'&id_order='.
                $this->module->currentOrder.'&key='.
                $customer->secure_key);
        } else {
            Tools::redirect('index.php?controller=order-confirmation&id_cart='.
                (int) $cart->id.'&id_module='.
                (int) $this->module->id.'&id_order='.
                $this->module->currentOrder.'&key='.
                $customer->secure_key);
        }
    }
}
