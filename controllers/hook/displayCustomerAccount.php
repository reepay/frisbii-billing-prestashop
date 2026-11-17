<?php

class BillwerksubscriptionDisplayCustomerAccountController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run()
    {
        if (!Module::isEnabled('billwerksubscription') || !$this->context->customer->isLogged()) {
            return false;
        }

        $url = $this->context->link->getModuleLink('billwerksubscription', 'my-subscriptions');
        $this->context->smarty->assign([
            'my_subscriptions_page' => $url,
            'bws_is_current' => 'my-subscriptions' === Tools::getValue('controller'),
            'bws_is_hummingbird' => $this->isHummingbird(),
        ]);

        return $this->module->display($this->file, 'hookDisplayCustomerAccount.tpl');
    }

    /**
     * True when the active theme, or its parent, is Hummingbird (it uses account-menu markup).
     */
    private function isHummingbird()
    {
        $theme = isset($this->context->shop->theme) ? $this->context->shop->theme : null;
        if (!is_object($theme) || !method_exists($theme, 'getName')) {
            return false;
        }

        return 'hummingbird' === $theme->getName() || 'hummingbird' === $theme->get('parent');
    }
}
