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
        $this->context->smarty->assign(['my_subscriptions_page' => $url]);

        return $this->module->display($this->file, 'hookDisplayCustomerAccount.tpl');
    }
}
