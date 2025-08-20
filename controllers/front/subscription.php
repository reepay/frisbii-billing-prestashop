<?php

class BillwerkSubscriptionSubscriptionModuleFrontController extends ModuleFrontControllerCore
{
    public $display_column_left = false;

    protected $state_names = ['pending' => 'Pending', 'active' => 'Active', 'cancelled' => 'Cancelled', 'on_hold' => 'On Hold'];

    public function postProcess()
    {
        $id_shop = Context::getContext()->shop->id;
        if (!Context::getContext()->customer->isLogged()) {
            Tools::redirect(Context::getContext()->link->getPageLink('my-account', true, Context::getContext()->language->id, null, false, $id_shop));
        }

        $sub_handle = Tools::getValue('h');

        $subscription = BillwerkSubscriptionSubscriptions::getById($sub_handle);

        if (!$subscription) {
            Tools::redirect(Context::getContext()->link->getPageLink('my-account', true, Context::getContext()->language->id, null, false, $id_shop));
        }

        $result = BillwerkSubscriptionSubscriptions::getById($sub_handle);

        if (!$result) {
            Tools::redirect(Context::getContext()->link->getPageLink('my-account', true, Context::getContext()->language->id, null, false, $id_shop));
        }

        if ($result['presta_customer_id'] != Context::getContext()->customer->id || $result['id_shop'] != Context::getContext()->shop->id) {
            Tools::redirect(Context::getContext()->link->getPageLink('my-account', true, Context::getContext()->language->id, null, false, $id_shop));
        }

        $result = BillwerkPlusApi::getSubscription($sub_handle);
        $subscription = $result['response'];

        $data = [];
        $data['payment_method_added'] = $subscription['payment_method_added'];
        if ($subscription['active_payment_methods']) {
            $payment_id = current($subscription['active_payment_methods']);
            $result = BillwerkPlusApi::getPaymentMethod($payment_id);
            $payment_method = $result['response'];
            $data['payment_method'] = ['card_type' => $payment_method['card']['card_type'], 'masked_card' => $payment_method['card']['masked_card']];
        }

        $plan = BillwerkPlusApi::getSubscriptionPlan($subscription['plan']);

        $data['state_name'] = $this->state_names[$subscription['state']];
        $data['state'] = $subscription['state'];
        if ($subscription['is_cancelled']) {
            $data['state_name'] = $this->state_names['cancelled'];
            $data['state'] = 'cancelled';
        }

        $data['plan'] = $plan['name'];
        $dateTime = new DateTime($subscription['first_period_start']);
        $data['first_period_start'] = $dateTime->format('d M Y');
        $dateTime = new DateTime($subscription['current_period_start']);
        $data['current_period_start'] = $dateTime->format('d M Y');
        $dateTime = new DateTime($subscription['next_period_start']);
        $data['next_period_start'] = $dateTime->format('d M Y');

        $check_customer = true;

        $subscription_link = $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $subscription['handle']], true);
        $data['my-subscription-link'] = $subscription_link;
        $subscription_page = $this->context->link->getModuleLink('billwerksubscription', 'my-subscriptions', [], true);
        $data['my-subscription-page'] = $subscription_page;
        $data['handle'] = $subscription['handle'];
        $data['manage_subscription_link'] = $this->context->link->getModuleLink('billwerksubscription', 'manage-subscription', [], true);

        if (!$result && !$check_customer) {
            $data['error'] = true;
        } else {
            $data['error'] = false;
        }

        $on_hold = Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_ON_HOLD');
        $cancel = Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_CANCEL');
        $data['enable_on_hold'] = $on_hold;
        $data['enable_cancel'] = $cancel;

        $data['in_trial'] = $subscription['in_trial'];

        if ($subscription['in_trial']) {
            $data['trial_start'] = $subscription['trial_start'];
            $data['trial_end'] = $subscription['trial_end'];

            $dateTime = new DateTime($data['trial_start']);
            $data['trial_start'] = $dateTime->format('d M Y');

            $dateTime = new DateTime($data['trial_end']);
            $data['trial_end'] = $dateTime->format('d M Y');
        }

        $data['sb_action_success'] = $this->module->getFlashMessage('sb_action_success');
        Context::getContext()->cookie->sb_action_success = null;

        $data['sb_action_error'] = $this->module->getFlashMessage('sb_action_error');
        Context::getContext()->cookie->sb_action_error = null;

        // it is supposed that the payment method was changed
        $payment_method = Tools::getValue('payment_method');
        if ($payment_method) {
            $result = BillwerkPlusApi::getPaymentMethod($payment_method);
            if (200 == $result['code'] && $this->module->getFlashMessage('sb_action_ch_payment')) {
                $data['sb_action_success'] = 'Payment method was changed successfully';
                $this->module->setFlashMessage('sb_action_ch_payment', null);
            }
        }

        $this->context->smarty->assign(['data' => $data]);
        $this->setTemplate('subscription.tpl');
    }
}
