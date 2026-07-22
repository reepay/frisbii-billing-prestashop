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
}
