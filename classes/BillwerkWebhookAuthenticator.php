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
 * RSCC-002: decides whether an incoming webhook payload is a genuine Frisbii event, before any
 * order business logic runs. Combines payload shape validation
 * (BillwerkWebhookSignatureVerifier), the cached secret (BillwerkWebhookSecretManager), and a
 * single refresh-and-retry on mismatch that supports secret rotation.
 */
class BillwerkWebhookAuthenticator
{
    const RESULT_OK = 'ok';
    const RESULT_MALFORMED = 'malformed';
    const RESULT_UNAUTHORIZED = 'unauthorized';
    /** Correctly signed, but the timestamp is outside the freshness window (captured/replayed triple). */
    const RESULT_STALE = 'stale';

    /**
     * Frisbii signs the original event time and retries after 20 min, 30 min, then hourly, so a retry
     * is always older than a short window. 24 h keeps retries working; replays are still stopped by
     * the per-invoice check in WebhookRenewSubscriptionOrder().
     */
    const DEFAULT_TOLERANCE_SECONDS = 86400;

    /** @var BillwerkWebhookSecretManager */
    private $secretManager;

    /** @var int Max allowed difference (seconds, both directions) between the webhook timestamp and now. 0 disables. */
    private $toleranceSeconds;

    /** @var callable Returns the current unix time. */
    private $clock;

    public function __construct(BillwerkWebhookSecretManager $secretManager, $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS, $clock = null)
    {
        $this->secretManager = $secretManager;
        $this->toleranceSeconds = (int) $toleranceSeconds;
        $this->clock = $clock !== null ? $clock : 'time';
    }

    /**
     * @param mixed $timestamp ISO 8601 string (as Frisbii sends) or unix seconds / milliseconds.
     * @return int|null Unix seconds, or null when unparsable.
     */
    public static function parseTimestamp($timestamp)
    {
        $timestamp = trim((string) $timestamp);
        if ($timestamp === '') {
            return null;
        }
        if (ctype_digit($timestamp)) {
            $value = (int) $timestamp;

            return $value > 100000000000 ? (int) ($value / 1000) : $value;
        }
        $parsed = strtotime($timestamp);

        return $parsed === false ? null : $parsed;
    }

    private function isFresh($timestamp)
    {
        if ($this->toleranceSeconds <= 0) {
            return true;
        }
        $time = self::parseTimestamp($timestamp);
        if ($time === null) {
            return false;
        }

        return abs(call_user_func($this->clock) - $time) <= $this->toleranceSeconds;
    }

    /**
     * @param mixed $payload Decoded webhook body.
     * @return string One of the RESULT_* constants.
     */
    public function authenticate($payload)
    {
        if (!BillwerkWebhookSignatureVerifier::validatePayload($payload)) {
            return self::RESULT_MALFORMED;
        }

        $secret = $this->secretManager->getSecret();
        $valid = BillwerkWebhookSignatureVerifier::isValidSignature($payload['timestamp'], $payload['id'], $payload['signature'], $secret);

        if (!$valid) {
            // Secret may have rotated; refresh once and retry before giving up.
            $secret = $this->secretManager->refresh();
            $valid = BillwerkWebhookSignatureVerifier::isValidSignature($payload['timestamp'], $payload['id'], $payload['signature'], $secret);
        }

        if (!$valid) {
            return self::RESULT_UNAUTHORIZED;
        }

        if (!$this->isFresh($payload['timestamp'])) {
            return self::RESULT_STALE;
        }

        return self::RESULT_OK;
    }
}
