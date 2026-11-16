<?php

include_once _PS_MODULE_DIR_.'billwerksubscription/classes/BillwerkWebhookSignatureVerifier.php';
include_once _PS_MODULE_DIR_.'billwerksubscription/classes/BillwerkWebhookSecretManager.php';
include_once _PS_MODULE_DIR_.'billwerksubscription/classes/BillwerkWebhookAuthenticator.php';

class BillwerkSubscriptionWebhookModuleFrontController extends ModuleFrontControllerCore
{
    // http://presta16.local/index.php?fc=module&module=billwerksubscription&controller=webhook
    public function postProcess()
    {
        $content = Tools::file_get_contents('php://input');
        $this->processRequest($content);
    }

    protected function processRequest($request)
    {
        $request_body = json_decode($request, true);

        // RSCC-002: the event must be signed by Frisbii before any field in it is trusted.
        $authResult = $this->module->getWebhookAuthenticator()->authenticate($request_body);
        if ($authResult !== BillwerkWebhookAuthenticator::RESULT_OK) {
            PrestaShopLogger::addLog('Webhook rejected: '.$authResult, 2, '0000002');
            http_response_code(401);
            die('Unauthorized');
        }

        if (!isset($request_body['event_type'])) {
            exit(1);
        }

        switch ($request_body['event_type']) {
            case 'subscription_renewal':
                $this->module->WebhookRenewSubscriptionOrder($request_body);
                break;
        }

        die(1);
    }
}
