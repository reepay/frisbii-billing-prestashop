<?php

class BillwerksubscriptionDisplayBeforePaymentController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        if (Configuration::get('BILLWERKSUBSCRIPTION_ENABLED')) {
            if ($this->module->cartHasSubscriptionProducts($params['cart'])) {
                Tools::redirect($this->context->link->getModuleLink('billwerksubscription', 'payment'));
            }
        }
    }
}
