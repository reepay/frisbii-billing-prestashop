<?php

include_once _PS_MODULE_DIR_.'billwerksubscription/classes/BillwerkChargeAmount.php';

class BillwerkSubscriptionValidationModuleFrontController extends ModuleFrontControllerCore
{
    public function postProcess()
    {
        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        if (!Tools::isSubmit('is_submit')) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        if (!Configuration::get('BILLWERKSUBSCRIPTION_ENABLED')) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $cart = $this->context->cart;
        if (0 == count($cart->getProducts()) || 0 == $cart->id_customer || 0 == $cart->id_address_delivery || 0 == $cart->id_address_invoice || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ('billwerksubscription' == $module['name']) {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $cart = $this->context->cart;

        $products = $cart->getProducts();
        if ($this->module->cartHasSubscriptionProducts($cart) && 1 == count($products)) {
            // placing a regular subscription order: confirmation.php accepts only quantity 1 (FP-34)
            if (1 !== (int) current($products)['cart_quantity']) {
                $this->module->setFlashMessage('frisbii_checkout_error', 'A subscription product can only be ordered with quantity 1.');
                Tools::redirect(Context::getContext()->link->getPageLink('cart', true, null, ['action' => 'show']));
            }
            $response = $this->createSubscriptionSession($cart);
        } else {
            // placing a mixed order
            $response = $this->createCheckoutSession($cart);
        }

        if (200 == $response['code']) {
            Tools::redirect($response['response']['url']);
        } else {
            if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
                PrestaShopLogger::addLOg('Subscription response params: '.print_r($response, true), 3);
            }
            $this->module->setFlashMessage('frisbii_checkout_error', 'Some error occurred.');
            // The legacy payment page template does not exist in PS 1.7+/8/9: go back to the checkout.
            $url = Context::getContext()->link->getPageLink('order', true, null, ['step' => 3]);
            Tools::redirect($url);
        }
    }

    public function createSubscriptionSession($cart)
    {
        $products = $cart->getProducts();
        $product = current($products);
        $product = BillwerkSubscriptionProduct::getByShopProductId($product['id_product'], $this->context->shop->id);

        $customer = $this->context->customer;
        $email = $customer->email;
        $customer_handle = 'cust-'.$customer->id;
        $api_customer = BillwerkPlusApi::getCustomer($customer_handle);

        if (200 == $api_customer['code']) {
            $customer_data = $customer_handle;
        } else {
            $customer_data = ['email' => $email,
                'handle' => $customer_handle];
        }

        $data = [
            'prepare_subscription' => [
                'plan' => $product['plan_handle'],
                'handle' => 'sub-'.$cart->id,
            ],
            'accept_url' => $this->context->link->getModuleLink('billwerksubscription', 'confirmation', ['t' => 's'], true),
            'cancel_url' => $this->context->link->getModuleLink('billwerksubscription', 'cancel', [], true),
        ];

        if ('string' == gettype($customer_data)) {
            $data['prepare_subscription']['customer'] = $customer_data;
        }

        if ('array' == gettype($customer_data)) {
            $data['prepare_subscription']['create_customer'] = $customer_data;
        }

        if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
            PrestaShopLogger::addLOg('Subscription request params: '.print_r($data, true), 1);
        }

        return BillwerkPlusApi::createSubscriptionSession($data);
    }

    public function createCheckoutSession($cart)
    {
        // Same calculation confirmation.php uses to check the paid amount (FP-34)
        $amount = BillwerkChargeAmount::forCart($cart);

        $deliveryAddress = new Address(intval($cart->id_address_delivery));
        $billingAddress = new Address(intval($cart->id_address_invoice));
        $address_delivery = new Address($cart->id_address_delivery);
        $address_delivery_country = new Country($address_delivery->id_country);
        $iso_code = $address_delivery_country->iso_code;

        $customer = $this->context->customer;

        $data = [
            'order' => [
                'handle' => $cart->id,
                'amount' => $amount,
                'currency' => $this->context->currency->iso_code,
                'customer' => [
                    'email' => $customer->email,
                    'handle' => 'cust-'.$customer->id,
                    'first_name' => $customer->firstname,
                    'last_name' => $customer->lastname,
                    'country' => $iso_code,
                ],
                'billing_address' => [
                    'address' => $billingAddress->address1.' '.$billingAddress->address2,
                    'city' => $billingAddress->city,
                    'country' => $iso_code,
                    'email' => $customer->email,
                    'first_name' => $billingAddress->firstname,
                    'last_name' => $billingAddress->lastname,
                    'postal_code' => $billingAddress->postcode,
                    'phone' => $billingAddress->phone,
                ],
                'shipping_address' => [
                    'address' => $deliveryAddress->address1.' '.$deliveryAddress->address2,
                    'city' => $deliveryAddress->city,
                    'country' => $iso_code,
                    'email' => $customer->email,
                    'first_name' => $deliveryAddress->firstname,
                    'last_name' => $deliveryAddress->lastname,
                    'postal_code' => $deliveryAddress->postcode,
                    'phone' => $deliveryAddress->phone,
                ],
            ],
            'recurring' => true,
            'accept_url' => $this->context->link->getModuleLink('billwerksubscription', 'confirmation', ['t' => 'ch'], true),
            'cancel_url' => $this->context->link->getModuleLink('billwerksubscription', 'cancel', [], true),
        ];

        return BillwerkPlusApi::createChargeSession($data);
    }
}
