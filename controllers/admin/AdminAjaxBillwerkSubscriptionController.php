<?php

class AdminAjaxBillwerkSubscriptionController extends ModuleAdminController
{
    public function ajaxProcessGetPlan()
    {
        $handle = Tools::getValue('handle');
        if ($handle) {
            $planData = BillwerkPlusApi::getSubscriptionPlan($handle, false);
            $subscriptionPlan = new BillwerkSubscriptionPlan($planData);
            $planHelper = new BillwerkSubscriptionPlanHelper($subscriptionPlan);
            $this->ajaxRender($planHelper->getPlanMerchantDataTable());
        }
        exit;
    }

    // Backs the "Refresh list" button: refetches the plan list from Frisbii and
    // returns it as JSON so the JS can rebuild the <select> without a full page/form submit.
    public function ajaxProcessGetPlans()
    {
        $plans = BillwerkPlusApi::getSubscriptionPlans();
        $result = [];
        if (is_array($plans)) {
            foreach ($plans as $plan) {
                $result[] = [
                    'handle' => $plan->handle,
                    'name' => $plan->name,
                ];
            }
        }
        $this->ajaxRender(json_encode($result));
        exit;
    }
}
