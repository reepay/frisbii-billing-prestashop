<?php

class BillwerkSubscriptionDisplayProductPriceBlockController
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
        if ('after_price' == $params['type']) {
            if (isset($params['product']) && is_object($params['product'])) {
                $subscription_product = BillwerkSubscriptionProduct::getByShopProductId($params['product']->id, $this->context->shop->id);
                if ($subscription_product) {
                    $planData = BillwerkPlusApi::getSubscriptionPlan($subscription_product['plan_handle'], false);
                    $subscription_plan = new BillwerkSubscriptionPlan($planData);
                    $plan_helper = new BillwerkSubscriptionPlanHelper($subscription_plan);

                    $data = $plan_helper->getSubscriptionDetails();

                    $this->context->smarty->assign([
                        'price_details' => $data['amount'] / 100 .' '.$data['currency'].' / '.$data['schedule_type_text'],
                        'subscription_details_text' => 'Subscription details',
                        'billing_description_text' => $data['billing_description'],
                    ]);

                    return $this->module->display($this->file, 'hookDisplayProductPriceBlock.tpl');
                }
            }
        }
    }
}
