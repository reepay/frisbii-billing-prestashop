<?php

use PrestaShop\Module\PrestashopCheckout\Cart\Exception\CartException;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__.'/api/BillwerkPlusApi.php';
require_once __DIR__.'/classes/BillwerkSubscriptionPlan.php';
require_once __DIR__.'/classes/BillwerkSubscriptionProduct.php';
require_once __DIR__.'/classes/BillwerkSubscriptionSubscriptions.php';
require_once __DIR__.'/classes/BillwerkSubscriptionPlanHelper.php';
require_once __DIR__.'/classes/BillwerkSubscriptionOrder.php';
class BillwerkSubscription extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billwerksubscription';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'Frisbii';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6.0',
            'max' => '1.7.9',
        ];
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Frisbii Subscription');
        $this->description = $this->l('Frisbii Subscription module allows to sell subscription produce with Frisbii account');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->hooks() && $this->install_db();
    }

    public function getContent()
    {
        $controller = $this->getHookController('getContent');
        $ajax_hook = Tools::getValue('ajax_hook');
        if ('' != $ajax_hook) {
            if (method_exists($controller, $ajax_hook)) {
                $controller->processAjax();
            }
        }

        return $controller->run();
    }

    public function hooks()
    {
        $hookList = [
            'displayAdminProductsExtra',
            'actionProductUpdate',
            'displayProductPriceBlock',
            'payment',
            'displayPayment',
            'displayBeforePayment',
            'displayHeader',
            'displayProductContent',
            'displayCustomerAccount',
            'actionBeforeCartUpdateQty',
            'paymentReturn',
            'actionValidateOrder',
            'displayAdminOrderContentOrder',
            'displayAdminOrderTabContent',
        ];

        return $this->registerHook($hookList);
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $controller = $this->getHookController('displayAdminProductsExtra');

        return $controller->run();
    }

    public function hookActionProductUpdate($params)
    {
        $controller = $this->getHookController('actionProductUpdate');

        return $controller->run($params);
    }

    public function hookDisplayPayment($params)
    {
        $enabled = Configuration::get('BILLWERKSUBSCRIPTION_ENABLED');

        if (!$enabled) {
            return '';
        }
        $controller = $this->getHookController('displayPayment');

        return $controller->run($params);
    }

    public function hookDisplayHeader($params)
    {
        $this->context->controller->addCSS($this->_path.'/views/css/styles.css');
    }

    public function hookDisplayProductPriceBlock($params)
    {
        $controller = $this->getHookController('displayProductPriceBlock');

        return $controller->run($params);
    }

    public function hookDisplayProductContent($params)
    {
        $controller = $this->getHookController('displayProductContent');

        return $controller->run($params);
    }

    public function hookDisplayCustomerAccount($params)
    {
        $controller = $this->getHookController('displayCustomerAccount');

        return $controller->run($params);
    }

    public function hookActionBeforeCartUpdateQty($params)
    {
        $controller = $this->getHookController('actionBeforeCartUpdateQty');

        return $controller->run($params);
    }

    public function hookDisplayBeforePayment($params)
    {
        $controller = $this->getHookController('displayBeforePayment');

        return $controller->run($params);
    }

    public function hookPaymentReturn($params)
    {
        $controller = $this->getHookController('paymentReturn');

        return $controller->run($params);
    }

    public function hookActionValidateOrder($params)
    {
        $controller = $this->getHookController('actionValidateOrder');

        return $controller->run($params);
    }

    public function hookDisplayAdminOrderTabContent($params)
    {
        return $this->hookDisplayAdminOrderContentOrder($params);
    }

    public function hookDisplayAdminOrderContentOrder($params)
    {
        $controller = $this->getHookController('displayAdminOrderContentOrder');

        return $controller->run($params);
    }

    public function install_db()
    {
        $file = __DIR__.'/install/install.sql';
        $sql = file_get_contents($file);
        $result = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql_requests = preg_split("/;\s*[\r\n]+/", $result);
        $result = true;
        foreach ($sql_requests as $request) {
            if (!empty($request)) {
                $result &= DB::getInstance()->execute(trim($request));
            }
        }

        return $result;
    }

    public function getHookController($hook_name)
    {
        require_once dirname(__FILE__).'/controllers/hook/'.$hook_name.'.php';
        $controller_name = $this->name.$hook_name.'Controller';
        $controller = new $controller_name($this, __FILE__, $this->_path);

        return $controller;
    }

    public function cartHasSubscriptionProducts($cart)
    {
        return $this->getSubscriptionProduct($cart);
    }

    public function getSubscriptionProduct($cart)
    {
        $products = $cart->getProducts();
        foreach ($products as $product) {
            $shop = $this->context->shop->id;
            $subscription_product = BillwerkSubscriptionProduct::getByShopProductId($product['id_product'], $shop);
            if ($subscription_product) {
                return $subscription_product;
            }
        }
    }

    public function createSubscription($data)
    {
        $data = ['handle' => $data['handle'],
            'plan' => $data['plan'],
            'customer' => $data['customer'],
            'source' => $data['source'],
            'signup_method' => 'source'];
        return BillwerkPlusApi::createSubscription($data);
    }

    public function WebhookRenewSubscriptionOrder($params)
    {
        $res = BillwerkSubscriptionSubscriptions::getById($params['subscription']);

        if ($res) {
            $id_cart = $res['id_cart'];
            $cart = new Cart($id_cart);
            $result = $cart->duplicate();
            if (!$result['success']) { // seems like product is run out
                $message = "Can't duplicate a cart during creation of a subscription renewal order.";
                $message .= 'Event payload: '.json_encode($params);
                PrestaShopLogger::addLog($message, 4, '0000002', 'Cart', $id_cart);
                throw new CartException($message);
            }

            $cart = $result['cart'];
            $customer = new Customer($cart->id_customer);
            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

            $this->validateOrder($cart->id, Configuration::get('BILLWERKSUBSCRIPTION_RENEWAL_DEFAULT_STATUS_AFTER_CREATION'),
                $total, $this->displayName, null, null, (int) $cart->id_currency, false, $customer->secure_key);
        }
    }

    public function setFlashMessage($id, $message)
    {
        $key = 'frisbii_'.$id;
        Context::getContext()->cookie->{$key} = $message;
    }

    public function getFlashMessage($id)
    {
        $key = 'frisbii_'.$id;
        $return = Context::getContext()->cookie->{$key};
        Context::getContext()->cookie->{$key} = null;

        return $return;
    }
}
