<?php

class BillwerkSubscriptionMySubscriptionsModuleFrontController extends ModuleFrontControllerCore
{
    public $display_column_left = false;

    protected $state_names = ['pending' => 'Pending', 'active' => 'Active', 'cancelled' => 'Cancelled', 'on_hold' => 'On Hold'];

    public function postProcess()
    {
        $id_shop = Context::getContext()->shop->id;
        if (!Context::getContext()->customer->isLogged()) {
            Tools::redirect(Context::getContext()->link->getPageLink('my-account', true, Context::getContext()->language->id, null, false, $id_shop));
        }

        $data = [];

        $customer_handle = 'cust-'.$this->context->customer->id;

        $api_subscriptions = BillwerkPlusApi::getCustomerSubscriptions($customer_handle);

        $planList = BillwerkPlusApi::getPlanList();

        $planData = [];
        foreach ($planList['response']['content'] as $plan) {
            $planData[$plan['handle']] = ['name' => $plan['name'], 'amount' => $plan['amount'], 'currency' => $plan['currency']];
        }
        $api_subscriptions = $api_subscriptions['response'];
        $stored_subscriptions = BillwerkSubscriptionSubscriptions::getAll($this->context->shop->id, $this->context->customer->id);

        $subscriptions_handles = [];
        foreach ($stored_subscriptions as $subscription) {
            $subscriptions_handles[] = $subscription['sub_handle'];
        }

        if (true) {
            foreach ($api_subscriptions['content'] as $api_subscription) {
                if ('pending' == $api_subscription['state']) {
                    continue;
                }

                if (!in_array($api_subscription['handle'], $subscriptions_handles)) {
                    continue;
                }

                $subscription['state_name'] = $this->state_names[$api_subscription['state']];
                if ($api_subscription['is_cancelled']) {
                    $subscription['state_name'] = 'Cancelled';
                }

                // sub_handle
                $subscription_link = $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $api_subscription['handle']], true);
                $subscription['subscription_link'] = $subscription_link;

                $subscription['plan_name'] = $planData[$api_subscription['plan']]['name'];

                $subscription['amount'] = $planData[$api_subscription['plan']]['amount'] / 100 .'&nbsp;'.$planData[$api_subscription['plan']]['currency'];

                $dateTime = new DateTime($api_subscription['next_period_start']);

                $subscription['next_billing'] = $dateTime->format('d M Y');
                $subscription['sub_handle'] = $api_subscription['handle'];

                $data[] = ['subscription' => $subscription];
            }
        }

        $this->context->smarty->assign(['subscriptions' => $data]);
        $this->setTemplate('my_subscriptions.tpl');
    }
}
