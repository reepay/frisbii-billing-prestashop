<?php
/**
 * NOTICE OF LICENSE
 *
 * This file is licenced under the Software License Agreement.
 * With the purchase or the installation of the software in your application
 * you accept the licence agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 *  @author    Frisbii
 *  @copyright 2026 Frisbii
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

/**
 * RSCC-002: pure HMAC-SHA256 signature check for Frisbii/Reepay webhook payloads sent to this
 * module. Has no PrestaShop dependency so it can be unit tested in isolation.
 *
 * Basis for the scheme (see task.md TO DO 1): this module's own webhook URL is registered
 * through the same account-level `account/webhook_settings` endpoint that the sibling `reepay`
 * module uses (`controllers/hook/getContent.php` `updateWebhooks()` reads the existing settings
 * and appends this module's URL to the same list). The `reepay` module already signs its events
 * this way (`modules/reepay/classes/WebhookSignatureVerifier.php`), with the secret read from the
 * same endpoint. Not yet confirmed against a real subscription_renewal payload captured live; see
 * `fix-verification.md` / TO DO 1.
 */
class BillwerkWebhookSignatureVerifier
{
    private static $requiredFields = array('timestamp', 'id', 'signature');

    /**
     * @param mixed $data Decoded webhook body.
     * @return bool True when every required field is present and non-empty.
     */
    public static function validatePayload($data)
    {
        if (!is_array($data)) {
            return false;
        }

        foreach (self::$requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
            $value = $data[$field];
            if (!is_scalar($value) || (string) $value === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string Lowercase hex HMAC-SHA256 of "$timestamp$id" keyed by $secret.
     */
    public static function computeSignature($timestamp, $id, $secret)
    {
        return hash_hmac('sha256', $timestamp.$id, $secret);
    }

    public static function isValidSignature($timestamp, $id, $signature, $secret)
    {
        if (!is_string($secret) || $secret === '') {
            return false;
        }
        $expected = self::computeSignature($timestamp, $id, $secret);

        return hash_equals($expected, (string) $signature);
    }
}
