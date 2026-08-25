<?php

class billwerksubscriptionPaymentReturnController
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
        $enabled = Configuration::get('BILLWERKSUBSCRIPTION_ENABLED');

        if (!$enabled) {
            exit("Frisbii Subscription Payment's not enabled");
        }

        if (PS_1_6) {
            $order = $params['objOrder'];
        } elseif (PS_1_7) {
            $order = $params['order'];
        }

        $this->context->smarty->assign([
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice($params['total_to_pay'], $params['currencyObj'], false),
            'status' => 'ok',
        ]);
        return $this->module->display($this->file, 'hookPaymentConfirmation.tpl');
    }
}
