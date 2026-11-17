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
        foreach ((array) ($planList['response']['content'] ?? []) as $plan) {
            $planData[$plan['handle']] = ['name' => $plan['name'], 'amount' => $plan['amount'], 'currency' => $plan['currency']];
        }
        $api_subscriptions = $api_subscriptions['response'];
        $stored_subscriptions = BillwerkSubscriptionSubscriptions::getAll($this->context->shop->id, $this->context->customer->id);

        $subscriptions_handles = [];
        foreach ($stored_subscriptions as $subscription) {
            $subscriptions_handles[] = $subscription['sub_handle'];
        }

        foreach ((array) ($api_subscriptions['content'] ?? []) as $api_subscription) {
            if ('pending' == $api_subscription['state']) {
                continue;
            }

            if (!in_array($api_subscription['handle'], $subscriptions_handles)) {
                continue;
            }

            $subscription = [];
            $subscription['state'] = $api_subscription['is_cancelled'] ? 'cancelled' : $api_subscription['state'];
            $subscription['state_name'] = $this->state_names[$subscription['state']] ?? ucfirst(str_replace('_', ' ', $subscription['state']));

            $subscription['subscription_link'] = $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $api_subscription['handle']], true);

            // The plan list only covers plans up to today; ask for a missing plan directly.
            if (!isset($planData[$api_subscription['plan']])) {
                $plan = BillwerkPlusApi::getSubscriptionPlan($api_subscription['plan']);
                if (is_array($plan) && isset($plan['name'])) {
                    $planData[$api_subscription['plan']] = ['name' => $plan['name'], 'amount' => $plan['amount'] ?? 0, 'currency' => $plan['currency'] ?? ''];
                }
            }
            $plan = $planData[$api_subscription['plan']] ?? null;
            $subscription['plan_name'] = $plan ? $plan['name'] : $api_subscription['plan'];
            $subscription['amount'] = $plan ? ($plan['amount'] / 100).' '.$plan['currency'] : '-';

            $subscription['next_billing'] = empty($api_subscription['next_period_start'])
                ? '-'
                : (new DateTime($api_subscription['next_period_start']))->format('d M Y');
            $subscription['sub_handle'] = $api_subscription['handle'];

            $data[] = ['subscription' => $subscription];
        }

        $this->context->smarty->assign(['subscriptions' => $data]);
        $this->setTemplate('module:billwerksubscription/views/templates/front/my_subscriptions.tpl');
    }
}
