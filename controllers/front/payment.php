<?php

class BillwerkSubscriptionPaymentModuleFrontController extends ModuleFrontControllerCore
{
    public $display_column_left = false;

    public function postProcess()
    {
        if (!Configuration::get('BILLWERKSUBSCRIPTION_ENABLED')) {
            exit('Frisbii Optimize not enabled');
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
            exit($this->module->l('This payment method is not available.', 'validation'));
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $this->context->smarty->assign([
            'img' => $this->module->getPathUri().'views/img/bg.png',
            'is_error' => $this->module->getFlashMessage('frisbii_checkout_error'),
        ]);
        $this->setTemplate('payment_confirmation.tpl');
    }
}
