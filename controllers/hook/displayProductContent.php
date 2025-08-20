<?php

class BillwerkSubscriptionDisplayProductContentController
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
        $subscription_product = BillwerkSubscriptionProduct::getByShopProductId($this->context->shop->id, $params['product']->id);
        if ($subscription_product) {
            $params['product']->show_price = false;
        }
    }
}
