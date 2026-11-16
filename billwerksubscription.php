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
require_once __DIR__.'/classes/BillwerkWebhookSignatureVerifier.php';
require_once __DIR__.'/classes/BillwerkWebhookSecretManager.php';
require_once __DIR__.'/classes/BillwerkWebhookAuthenticator.php';
class BillwerkSubscription extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'billwerksubscription';
        $this->tab = 'payments_gateways';
        $this->version = '1.1.2';
        $this->author = 'Frisbii';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6.0',
            'max' => '9.99.99',
        ];
        $this->need_instance = 0;

        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Frisbii Subscription');
        $this->description = $this->l('Frisbii Subscription module allows to sell subscription products with Frisbii account');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        return parent::install()
            && $this->hooks() && $this->install_db() && $this->installTab();
    }

    public function uninstall()
    {
        return $this->uninstallTab() && parent::uninstall();
    }

    public function installTab()
    {
        $tab = new Tab();
        $tab->class_name = 'AdminAjaxBillwerkSubscription';
        $tab->module = $this->name;
        $tab->active = true;
        $tab->id_parent = -1;
        $tab->name = array_fill_keys(Language::getIDs(false), $this->displayName);

        return $tab->add();
    }

    public function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminAjaxBillwerkSubscription');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            if (Validate::isLoadedObject($tab)) {
                return $tab->delete();
            }
        }

        return true;
    }

    public function getContent()
    {
        $controller = $this->getHookController('getContent');

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

        // Forward $params (contains id_product) to the controller. On PS9's new
        // Product page id_product is not available via $_GET/Tools::getValue(),
        // it's only passed through the hook's $params, so dropping it here made
        // the controller unable to look up the product's saved plan.
        return $controller->run($params);
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

    /**
     * RSCC-002: builds a BillwerkWebhookAuthenticator wired to the real Frisbii API and Configuration.
     */
    public function getWebhookSecretManager()
    {
        $fetcher = function () {
            $result = BillwerkPlusApi::getWebhookSettings();
            if (!isset($result['response']) || empty($result['response']['secret'])) {
                return null;
            }

            return $result['response']['secret'];
        };

        return new BillwerkWebhookSecretManager(
            $fetcher,
            function ($key) {
                return Configuration::get($key);
            },
            function ($key, $value) {
                Configuration::updateValue($key, $value);
            },
            function ($key) {
                Configuration::deleteByName($key);
            }
        );
    }

    public function getWebhookAuthenticator()
    {
        $tolerance = Configuration::get('BILLWERKSUBSCRIPTION_WEBHOOK_TOLERANCE');

        return new BillwerkWebhookAuthenticator(
            $this->getWebhookSecretManager(),
            ($tolerance === false || $tolerance === '' || $tolerance === null)
                ? BillwerkWebhookAuthenticator::DEFAULT_TOLERANCE_SECONDS
                : (int) $tolerance
        );
    }

    public function WebhookRenewSubscriptionOrder($params)
    {
        // RSCC-001: the handle comes from the webhook body; stop on anything that is not a plain handle.
        if (!isset($params['subscription']) || !BillwerkSubscriptionSubscriptions::isValidHandle($params['subscription'])) {
            return;
        }

        // RSCC-002: an event must name an invoice, that invoice must be settled, and it must belong
        // to this subscription — otherwise anyone who can reach the webhook (or a captured event
        // replayed later) could get a free renewal order. See task.md TECH DETAILS: getInvoice()'s
        // exact response shape for a subscription renewal was not confirmed against a real invoice.
        if (!isset($params['invoice']) || !is_string($params['invoice']) || $params['invoice'] === '') {
            PrestaShopLogger::addLog('Webhook renewal rejected: event for subscription '.$params['subscription'].' has no invoice handle.', 2, '0000002');

            return;
        }
        $inv_handle = $params['invoice'];

        // RSCC-002: the same invoice must not create a second order (Frisbii retry, or a replay).
        if (BillwerkSubscriptionOrder::existsByInvoiceHandle($inv_handle)) {
            PrestaShopLogger::addLog('Webhook renewal rejected: invoice '.$inv_handle.' was already used for an order.', 2, '0000002');

            return;
        }

        $invoiceResult = BillwerkPlusApi::getInvoice($inv_handle);
        $invoice = isset($invoiceResult['response']) && is_array($invoiceResult['response']) ? $invoiceResult['response'] : null;

        if ($invoice === null || empty($invoice['state']) || $invoice['state'] !== 'settled') {
            PrestaShopLogger::addLog('Webhook renewal rejected: invoice '.$inv_handle.' could not be verified as settled.', 2, '0000002');

            return;
        }

        if (!isset($invoice['subscription']) || $invoice['subscription'] !== $params['subscription']) {
            PrestaShopLogger::addLog('Webhook renewal rejected: invoice '.$inv_handle.' does not belong to subscription '.$params['subscription'].'.', 2, '0000002');

            return;
        }

        $res = BillwerkSubscriptionSubscriptions::getById($params['subscription']);

        if ($res) {
            $id_cart = $res['id_cart'];
            $cart = new Cart($id_cart);
            $result = $cart->duplicate();
            if (!$result['success']) { // seems like product is run out
                $message = "Can't duplicate a cart during creation of a subscription renewal order.";
                $message .= 'Subscription: '.$params['subscription'].', invoice: '.$inv_handle;
                PrestaShopLogger::addLog($message, 4, '0000002', 'Cart', $id_cart);
                throw new CartException($message);
            }

            $cart = $result['cart'];
            $customer = new Customer($cart->id_customer);
            $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

            $order_status = _PS_OS_PAYMENT_;
            $this->validateOrder($cart->id, $order_status, $total, $this->displayName, null, null, (int) $cart->id_currency, false, $customer->secure_key);
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
