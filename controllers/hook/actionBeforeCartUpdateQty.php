<?php

class BillwerkSubscriptionActionBeforeCartUpdateQtyController
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
        $result = $this->module->cartHasSubscriptionProducts($params['cart']);
        $res = BillwerkSubscriptionProduct::getByShopProductId($params['product']->id, $this->context->shop->id);
        if ($result && $res) {
            exit(json_encode([
                'errors' => ['There is only one subscription product is allowed in the cart. You are already have subscription product in your cart.'],
                'hasError' => true,
            ]));
        }
    }
}
