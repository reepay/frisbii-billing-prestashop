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

        // PS 1.6 passes objOrder; PS 1.7/8/9 pass order. Do not depend on constants defined by other modules.
        $order = isset($params['order']) ? $params['order'] : (isset($params['objOrder']) ? $params['objOrder'] : null);
        if (!Validate::isLoadedObject($order)) {
            return '';
        }

        $currency = new Currency((int) $order->id_currency);
        $amount = isset($params['total_to_pay']) ? (float) $params['total_to_pay'] : (float) $order->getOrdersTotalPaid();
        if (method_exists($this->context, 'getCurrentLocale') && $this->context->getCurrentLocale()) {
            $total = $this->context->getCurrentLocale()->formatPrice($amount, $currency->iso_code);
        } else {
            $total = Tools::displayPrice($amount, $currency, false);
        }

        $this->context->smarty->assign([
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => $total,
            'shop_name' => $this->context->shop->name,
            'status' => 'ok',
        ]);
        return $this->module->display($this->file, 'hookPaymentConfirmation.tpl');
    }
}
