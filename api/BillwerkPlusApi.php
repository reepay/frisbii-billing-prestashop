<?php

class BillwerkPlusApi
{
    protected static function curlSession()
    {
        $privateApiKey = Configuration::get('BILLWERK_PRIVATE_API_KEY');
        // Some PHP/Apache setups (e.g. WAMP) ship without a configured CA bundle
        // (curl.cainfo/openssl.cafile empty), so cURL fails HTTPS requests to the
        // Frisbii/Reepay API with "SSL certificate problem: unable to get local
        // issuer certificate" (curl errno 60) even though the request itself is fine.
        // Reuse PrestaShop core's own CA bundle (refreshed into var/cache) to fix this
        // without depending on the server's PHP/OS certificate configuration.
        Tools::refreshCACertFile();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $privateApiKey.':');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CAINFO, _PS_CACHE_CA_CERT_FILE_);

        return $ch;
    }

    /**
     * A URL path or query value built from a handle or id (FP-32, FP-33). Accepts only letters,
     * digits, "_", "-" and ".", up to 64 characters, never a value made only of dots and never
     * "..". ("." and ".." are path steps that curl removes from the URL.) Anything else could change
     * which object the URL points to or add a parameter, so it is refused.
     *
     * @return string|null null when the value must not be sent to Frisbii
     */
    protected static function segment($value)
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $value = (string) $value;
        if (1 !== preg_match('/^[A-Za-z0-9_.-]{1,64}$/D', $value)
            || 1 === preg_match('/^\.+$/D', $value)
            || false !== strpos($value, '..')) {
            return null;
        }

        return rawurlencode($value);
    }

    /**
     * What a wrapped (handleResponse()) call returns when segment() refused the handle:
     * the same shape as a real Frisbii answer, so callers do not need a separate check.
     */
    protected static function rejected()
    {
        return ['response' => null, 'code' => 0];
    }

    public static function getInvoice($invoiceId)
    {
        if (null === ($invoiceId = self::segment($invoiceId))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/invoice/$invoiceId");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getChargeSession($orderId)
    {
        if (null === ($orderId = self::segment($orderId))) {
            return null;
        }
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
        if (null === ($handle = self::segment($handle))) {
            return null;
        }
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
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/customer/{$handle}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getCustomerSubscriptions($handle)
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/list/subscription?customer={$handle}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getByHandle($handle)
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getPlan($handle)
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
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
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function getPaymentMethod($payment_id)
    {
        if (null === ($payment_id = self::segment($payment_id))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/payment_method/{$payment_id}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

        return self::handleResponse($ch);
    }

    public static function pauseSubscription($handle)
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}/on_hold");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        return self::handleResponse($ch);
    }

    public static function reactivateSubscription($handle)
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}/reactivate");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        return self::handleResponse($ch);
    }

    public static function cancelSubscription($handle)
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}/cancel");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        return self::handleResponse($ch);
    }

    /**
     * End a subscription now and refund what was paid for it (used when an order is refused after payment, FP-34).
     */
    public static function expireSubscription($handle, $compensationMethod = 'full_refund')
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        $dataJson = json_encode(['compensation_method' => $compensationMethod]);
        curl_setopt($ch, CURLOPT_URL, "https://api.reepay.com/v1/subscription/{$handle}/expire");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: '.Tools::strlen($dataJson)]);

        return self::handleResponse($ch);
    }

    /**
     * Release an authorized charge (FP-34).
     */
    public static function cancelCharge($handle)
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        curl_setopt($ch, CURLOPT_URL, 'https://api.reepay.com/v1/charge/'.$handle.'/cancel');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

        return self::handleResponse($ch);
    }

    /**
     * Refund the whole amount of a settled invoice (FP-34).
     */
    public static function refundInvoice($handle)
    {
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
        $ch = self::curlSession();
        $dataJson = json_encode(['invoice' => $handle]);
        curl_setopt($ch, CURLOPT_URL, 'https://api.reepay.com/v1/refund');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: '.Tools::strlen($dataJson)]);

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
        if (null === ($handle = self::segment($handle))) {
            return self::rejected();
        }
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
