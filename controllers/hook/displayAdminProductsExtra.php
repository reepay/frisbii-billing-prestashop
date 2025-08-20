<?php

class BillwerkSubscriptionDisplayAdminProductsExtraController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run()
    {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
        } else {
            $id_product = (int) Tools::getValue('id_product');
        }
        $product = BillwerkSubscriptionProduct::getByShopProductId($id_product, $this->context->shop->id);

        $this->context->smarty->assign([
            'plan_handle' => $product['plan_handle'],
        ]);

        $ajax_action_url = $this->context->link->getAdminLink('AdminModules', true);
        $ajax_action_url = str_replace('index.php', 'ajax-tab.php', $ajax_action_url);
        $ajax_action_url .= '&configure=billwerksubscription&ajax_hook=processAjax';

        $this->context->smarty->assign('ajax_action_url', $ajax_action_url);
        $plans = BillwerkPlusApi::getSubscriptionPlans();
        $this->context->smarty->assign([
            'plans' => $plans,
            'pc_base_dir' => __PS_BASE_URI__.'modules/'.$this->module->name.'/',
            'ajax_action_url' => $ajax_action_url,
            'plan_handle' => $product['plan_handle'],
            'enabled_subscription' => 1,
            'product_has_subscription' => $product ? 1 : 0,
            'hash' => md5(microtime()),
        ]);

        return $this->module->display($this->file, 'hookDisplayAdminProductsExtra.tpl');
    }

}
