<?php

class BillwerkSubscriptionDisplayPaymentController
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
        if (!$this->module->cartHasSubscriptionProducts($params['cart'])) {
            return null;
        }

        return $this->module->display($this->file, 'hookDisplayPayment.tpl');
    }
}
