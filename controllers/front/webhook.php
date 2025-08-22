<?php

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
