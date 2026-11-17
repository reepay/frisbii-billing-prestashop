<?php

include_once _PS_MODULE_DIR_.'billwerksubscription/classes/BillwerkManageGuard.php';

class BillwerkSubscriptionManageSubscriptionModuleFrontController extends ModuleFrontControllerCore
{
    public function postProcess()
    {
        $id_shop = Context::getContext()->shop->id;
        if (!Context::getContext()->customer->isLogged()) {
            Tools::redirect(Context::getContext()->link->getPageLink('my-account', true,
                Context::getContext()->language->id, null, false, $id_shop));
        }

        $action = Tools::getValue('action');
        // The handle goes straight into the Frisbii API URL (FP-32); only a real sub-<digits> handle may pass.
        if (in_array($action, ['pause', 'activate', 'cancel', 'uncancel', 'change-payment'], true)
            && !BillwerkSubscriptionSubscriptions::isValidHandle(Tools::getValue('handle'))) {
            Tools::redirect(Context::getContext()->link->getPageLink('my-account', true,
                Context::getContext()->language->id, null, false, $id_shop));
        }

        // FP-33 / RSCC-004: only the owner may manage a subscription, in the shop it was bought in.
        if (in_array($action, ['pause', 'activate', 'cancel', 'uncancel', 'change-payment'], true)) {
            $row = BillwerkSubscriptionSubscriptions::getById(Tools::getValue('handle'));
            if (!$row
                || (int) $row['presta_customer_id'] !== (int) Context::getContext()->customer->id
                || (int) $row['id_shop'] !== (int) $id_shop) {
                Tools::redirect(Context::getContext()->link->getPageLink('my-account', true,
                    Context::getContext()->language->id, null, false, $id_shop));
            }
        }

        if (in_array($action, BillwerkManageGuard::ACTIONS, true)) {
            $this->guardAction($action, Tools::getValue('handle'));
        }

        switch ($action) {
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

    /**
     * FP-35: POST only, valid token, and the shop settings and the Frisbii state must allow the action.
     * Any failure ends on the subscription page with an error message and no change at Frisbii.
     */
    private function guardAction($action, $handle)
    {
        $reason = null;
        if (!$this->isPostRequest()) {
            $reason = 'the request is not a POST';
        } elseif (!BillwerkManageGuard::isValidToken(Tools::getValue('token'), Tools::getToken('bws-manage-subscription'))) {
            $reason = 'the security token is missing or wrong';
        } else {
            $result = BillwerkPlusApi::getSubscription($handle);
            $reason = BillwerkManageGuard::refusal(
                $action,
                200 == ($result['code'] ?? 0) ? ($result['response'] ?? null) : null,
                Configuration::get(self::originKey($handle)),
                (bool) (int) Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_ON_HOLD'),
                (bool) (int) Configuration::get('BILLWERKSUBSCRIPTION_ENABLE_CANCEL')
            );
        }

        if (null === $reason) {
            return;
        }

        PrestaShopLogger::addLog(
            sprintf('Frisbii Subscription: %s refused for %s: %s', $action, $handle, $reason),
            2,
            null,
            'Customer',
            (int) $this->context->customer->id,
            true
        );
        $this->module->setFlashMessage('sb_action_error', 'This action is not available for this subscription');
        Tools::redirect($this->context->link->getModuleLink('billwerksubscription', 'subscription', ['h' => $handle], true));
    }

    private function isPostRequest()
    {
        return isset($_SERVER['REQUEST_METHOD']) && 'POST' === $_SERVER['REQUEST_METHOD'];
    }

    private static function originKey($handle)
    {
        return 'BWS_ORIGIN_'.$handle; // handle is sub-<digits> here
    }

    private function rememberOrigin($handle, $origin)
    {
        if (null === $origin) {
            Configuration::deleteByName(self::originKey($handle));
        } else {
            Configuration::updateValue(self::originKey($handle), $origin);
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
            $this->rememberOrigin($handle, BillwerkManageGuard::ORIGIN_HOLD);
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
            $this->rememberOrigin($handle, null);
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
            $this->rememberOrigin($handle, BillwerkManageGuard::ORIGIN_CANCEL);
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
            $this->rememberOrigin($handle, null);
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
