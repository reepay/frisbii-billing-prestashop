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

    public function run($params = [])
    {
        // On PS9's new Product page (route /{productId}/edit) id_product is not in the
        // query string, so Tools::getValue('id_product') returns nothing there; PS passes
        // it via the hook's $params instead. Keep the Tools::getValue() fallback for other
        // (legacy) callers of this hook that still rely on the query string.
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
        } else {
            $id_product = (int) Tools::getValue('id_product');
        }
        $product = BillwerkSubscriptionProduct::getByShopProductId($id_product, $this->context->shop->id);

        $this->context->smarty->assign([
            'plan_handle' => $product['plan_handle'],
        ]);

        $admin_link = $this->context->link->getAdminLink('AdminAjaxBillwerkSubscription');
        $ajax_action_url = $admin_link.'&ajax=1&action=GetPlan';
        // URL for the "Refresh list" button (ajaxProcessGetPlans), passed to the
        // template/JS so it can refetch the plan list without a full form submit.
        $ajax_get_plans_url = $admin_link.'&ajax=1&action=GetPlans';

        $plans = BillwerkPlusApi::getSubscriptionPlans();
        $this->context->smarty->assign([
            'plans' => $plans,
            'pc_base_dir' => __PS_BASE_URI__.'modules/'.$this->module->name.'/',
            'ajax_action_url' => $ajax_action_url,
            'ajax_get_plans_url' => $ajax_get_plans_url,
            'plan_handle' => $product['plan_handle'],
            'enabled_subscription' => 1,
            'product_has_subscription' => $product ? 1 : 0,
            'hash' => md5(microtime()),
        ]);

        return $this->module->display($this->file, 'hookDisplayAdminProductsExtra.tpl');
    }

}
