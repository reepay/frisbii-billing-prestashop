<?php

class BillwerkSubscriptionDisplayAdminOrderContentOrderController
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
        if (version_compare(_PS_VERSION_, '1.7.7.4', '>=')) {
            $order = new Order($params['id_order']);
        } else {
            $order = $params['order'];
        }

        if ($order->module != $this->module->name) {
            return '';
        }

        $subscription_order = BillwerkSubscriptionOrder::getByShppIdOrderId($this->context->shop->id, $order->id);

        $this->context->smarty->assign([
            'sub_handle' => $subscription_order['sub_handle'],
            'inv_handle' => $subscription_order['inv_handle'],
        ]);

        return $this->module->display($this->file, 'hookDisplayAdminOrderContentOrder.tpl');
    }
}
