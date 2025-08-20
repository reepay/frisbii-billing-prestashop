<?php

class BillwerkSubscriptionCancelModuleFrontController extends ModuleFrontControllerCore
{
    public function postProcess()
    {
        Tools::redirect('index.php?controller=order&step=1');
    }
}
