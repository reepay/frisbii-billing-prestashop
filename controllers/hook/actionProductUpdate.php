<?php

class BillwerkSubscriptionActionProductUpdateController
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
        $plan = BillwerkPlusApi::getSubscriptionPlan(Tools::getValue('plan'));
        $currency = Context::getContext()->currency->iso_code;

        $shop_id = Context::getContext()->shop->id;
        $plan_handle = Tools::getValue('plan');

        if (!Tools::getValue('billwerksubscription_plan_attach_to_product')) {
            BillwerkSubscriptionProduct::removeProduct($shop_id, $params['id_product']);

            return;
        }

        if ($currency != $plan['currency']) {
            $this->context->controller->errors[] = '<b>Frisbii Subscription</b>: the currency provided in subscription 
            plan is not supported. <br/ > Allowed currency is: <b>'.$currency.'</b>'
            .' Please choose another subscription plan.';
        } else {
            $plan_name = Tools::getValue('plan-name');
            BillwerkSubscriptionProduct::saveProduct($shop_id, $plan_handle, $plan_name, $params['id_product']);

            $price = $plan['amount'] / 100;

            // update product price to show
            $sql = 'UPDATE '._DB_PREFIX_.'product_shop SET `price`='.
            $price.' WHERE `id_product`= '.(int) $params['id_product'].' AND id_shop = '.(int) $shop_id;
            Db::getInstance()->execute($sql);
        }
    }
}
