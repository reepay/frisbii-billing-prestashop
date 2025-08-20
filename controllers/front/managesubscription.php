<?php

class BillwerkSubscriptionManageSubscriptionModuleFrontController extends ModuleFrontControllerCore
{
    public function postProcess()
    {
        $id_shop = Context::getContext()->shop->id;
        if (!Context::getContext()->customer->isLogged()) {
            Tools::redirect(Context::getContext()->link->getPageLink('my-account', true,
                Context::getContext()->language->id, null, false, $id_shop));
        }

        switch (Tools::getValue('action')) {
            case 'pause':
                $this->pauseSubscription(Tools::getValue('handle'));
                break;
            case 'activate':
                $this->reactivateSubscription(Tools::getValue('handle'));
                break;
            case 'cancel':
                $this->cancelSubscription(Tools::getValue('handle'));
                break;
            case 'uncancel':
                $this->uncancelSubscription(Tools::getValue('handle'));
                break;

            case 'change-payment':
                $this->changePayment(Tools::getValue('handle'));
                break;
        }
    }

    protected function pauseSubscription($handle)
    {
        if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
            PrestaShopLogger::addLOg('pauseSubscription action initiated: handle '.$handle, true, 1);
        }
        $result = BillwerkPlusApi::pauseSubscription($handle);
        if (200 == $result['code']) {
            $message = 'Subscription was successfully paused';
            $this->module->setFlashMessage('sb_action_success', $message);
        } else {
            if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
                PrestaShopLogger::addLOg('pauseSubscription error: response '.print_r($result, true), true, 3);
            }
            $message = 'Subscription was not paused. Some error occurred';
            $this->module->setFlashMessage('sb_action_error', $message);
        }
        $redirect = $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $handle], true);
        Tools::redirect($redirect);
    }

    protected function reactivateSubscription($handle)
    {
        if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
            PrestaShopLogger::addLOg('reactivateSubscription action initiated: handle '.$handle, true, 1);
        }
        $result = BillwerkPlusApi::reactivateSubscription($handle);
        if (200 == $result['code']) {
            $message = 'Subscription was successfully activated';
            $this->module->setFlashMessage('sb_action_success', $message);
        } else {
            $message = 'Subscription was not activated. Some error occurred';
            $this->module->setFlashMessage('sb_action_error', $message);
            if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
                PrestaShopLogger::addLOg('reactivateSubscription error response: '.print_r($result, true), true, 1);
            }
        }
        $redirect = $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $handle], true);
        Tools::redirect($redirect);
    }

    protected function cancelSubscription($handle)
    {
        if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
            PrestaShopLogger::addLOg('cancelSubscription action initiated: handle '.$handle, true, 1);
        }
        $result = BillwerkPlusApi::cancelSubscription($handle);
        if (200 == $result['code']) {
            $message = 'Subscription was successfully cancelled';
            $this->module->setFlashMessage('sb_action_success', $message);
        } else {
            $message = 'Subscription was not cancelled. Some error occurred';
            $this->module->setFlashMessage('sb_action_error', $message);
            if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
                PrestaShopLogger::addLOg('reactivateSubscription error response: '.print_r($result, true), true, 1);
            }
        }
        $redirect = $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $handle], true);
        Tools::redirect($redirect);
    }

    protected function uncancelSubscription($handle)
    {
        if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
            PrestaShopLogger::addLOg('uncancelSubscription action initiated: handle '.$handle, true, 1);
        }
        $result = BillwerkPlusApi::uncancelSubscription($handle);
        if (200 == $result['code']) {
            $message = 'Subscription was successfully restarted';
            $this->module->setFlashMessage('sb_action_success', $message);
        } else {
            $message = 'Subscription was not restarted. Some error occurred';
            $this->module->setFlashMessage('sb_action_error', $message);
            if (Configuration::get('BILLWERKSUBSCRIPTION_DEBUG_ENABLED')) {
                PrestaShopLogger::addLOg('uncancelSubscription error response: '.print_r($result, true), true, 1);
            }
        }
        $redirect = $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $handle], true);
        Tools::redirect($redirect);
    }

    protected function changePayment($handle)
    {
        $data = [
            'subscription' => $handle,
            'accept_url' => $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $handle], true),
            'cancel_url' => $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $handle], true),
            'recurring_optional' => true,
        ];

        $result = BillwerkPlusApi::createSubscriptionSession($data);
        if (200 == $result['code']) {
            $response = $result['response'];
            $this->module->setFlashMessage('sb_action_ch_payment', 1);
            Tools::redirect($response['url']);
        } else {
            $redirect = $this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $handle], true);
            Tools::redirect($redirect);
        }
    }
}
