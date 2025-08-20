<?php

class BillwerkPlusApi
{
    protected static function curlSession()
    {
        $privateApiKey = Configuration::get('BILLWERK_PRIVATE_API_KEY');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $privateApiKey.':');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        return $ch;
    }

    public static function getInvoice($invoiceId)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/invoice/$invoiceId");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getChargeSession($orderId)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, 'https://api.reepay.com/v1/charge/'.$orderId);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $result = json_decode(curl_exec($ch));

        return $result;
    }

    public static function getSubscriptionPlans()
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, 'https://api.reepay.com/v1/plan?only_active=true');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $result = json_decode(curl_exec($ch));

        return $result;
    }

    public static function getSubscriptionPlan($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/plan/{$handle}/current");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $result = json_decode(curl_exec($ch), true);

        return $result;
    }

    public static function createSubscriptionSession($data)
    {
        $ch = self::curlSession();
        $dataJson = json_encode($data);
        curl_setopt($ch, CURLOPT_URL, 'https://checkout-api.reepay.com/v1/session/subscription');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: '.Tools::strlen($dataJson),
            ]
        );

        return self::handleResponse($ch);
    }

    public static function createChargeSession($data)
    {
        $ch = self::curlSession();
        $dataJson = json_encode($data);
        curl_setopt($ch, CURLOPT_URL, 'https://checkout-api.reepay.com/v1/session/charge');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: '.Tools::strlen($dataJson),
            ]
        );

        return self::handleResponse($ch);
    }

    protected static function handleResponse($ch)
    {
        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return ['response' => json_decode($result, true), 'code' => $code];
    }

    public static function getCustomer($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/customer/{$handle}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getCustomerSubscriptions($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/list/subscription?customer={$handle}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getByHandle($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getPlan($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/plan/{$handle}/current");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getPlanList()
    {
        $ch = self::curlSession();
        $from = '1970-01-01';
        $to = date('Y-m-d');
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/list/plan?from=$from&to=$to&size=100");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getSubscription($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getPaymentMethod($payment_id)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/payment_method/{$payment_id}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function pauseSubscription($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}/on_hold");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        return self::handleResponse($ch);
    }

    public static function reactivateSubscription($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}/reactivate");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        return self::handleResponse($ch);
    }

    public static function cancelSubscription($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}/cancel");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        return self::handleResponse($ch);
    }

    public static function getWebhookSettings()
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, 'https://api.frisbii.com/v1/account/webhook_settings');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function updateWebhooks($data)
    {
        $ch = self::curlSession();
        $dataJson = json_encode($data, true);
        curl_setopt($ch, CURLOPT_URL, 'https://api.reepay.com/v1/account/webhook_settings');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: '.Tools::strlen($dataJson),
            ]
        );

        return self::handleResponse($ch);
    }

    public static function uncancelSubscription($handle)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}/uncancel");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        return self::handleResponse($ch);
    }

    public static function createSubscription($data)
    {
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, 'https://api.reepay.com/v1/subscription/');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Content-Type: application/json',
                'Content-Length: '.Tools::strlen(json_encode($data)),
            ]
        );

        return self::handleResponse($ch);
    }
}
