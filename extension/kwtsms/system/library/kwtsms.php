<?php
namespace Opencart\System\Library\Extension\Kwtsms;

use KwtSMS\KwtSMS as KwtSMSClient;
use KwtSMS\PhoneUtils;
use KwtSMS\MessageUtils;

class Kwtsms {
    private object $db;
    private object $config;
    private \Opencart\System\Engine\Registry $registry;

    public function __construct(\Opencart\System\Engine\Registry $registry) {
        $this->registry = $registry;
        $this->db = $registry->get('db');
        $this->config = $registry->get('config');

        require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
    }

    /**
     * Authenticate with the kwtSMS gateway, store balance/senderids/coverage.
     */
    public function login(string $username, string $password): array {
        try {
            $client = new KwtSMSClient($username, $password, 'KWT-SMS', false, '');

            $balanceResult = $client->balance();
            if ($balanceResult === null) {
                return ['success' => false, 'error' => 'Failed to retrieve balance. Check credentials.'];
            }

            $senderidsResult = $client->senderids();
            $coverageResult = $client->coverage();

            $senderids = [];
            if (isset($senderidsResult['result']) && $senderidsResult['result'] === 'OK') {
                $senderids = $senderidsResult['senderids'] ?? [];
            }

            $coverage = [];
            if (isset($coverageResult['result']) && $coverageResult['result'] === 'OK') {
                $coverage = $coverageResult['prefixes'] ?? [];
            }

            $this->setGatewayCache('balance', $balanceResult);
            $this->setGatewayCache('senderids', $senderids);
            $this->setGatewayCache('coverage', $coverage);

            return [
                'success'   => true,
                'balance'   => $balanceResult,
                'senderids' => $senderids,
                'coverage'  => $coverage,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Clear all cached gateway data.
     */
    public function logout(): void {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "kwtsms_gateway`");
    }

    /**
     * Reload gateway data from stored credentials.
     */
    public function reload(): array {
        $username = $this->config->get('module_kwtsms_username');
        $password = $this->config->get('module_kwtsms_password');

        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'No stored credentials found.'];
        }

        try {
            $client = new KwtSMSClient($username, $password, 'KWT-SMS', false, '');

            $balanceResult = $client->balance();
            if ($balanceResult === null) {
                return ['success' => false, 'error' => 'Failed to retrieve balance.'];
            }

            $senderidsResult = $client->senderids();
            $coverageResult = $client->coverage();

            $senderids = [];
            if (isset($senderidsResult['result']) && $senderidsResult['result'] === 'OK') {
                $senderids = $senderidsResult['senderids'] ?? [];
            }

            $coverage = [];
            if (isset($coverageResult['result']) && $coverageResult['result'] === 'OK') {
                $coverage = $coverageResult['prefixes'] ?? [];
            }

            $this->setGatewayCache('balance', $balanceResult);
            $this->setGatewayCache('senderids', $senderids);
            $this->setGatewayCache('coverage', $coverage);

            return [
                'success'   => true,
                'balance'   => $balanceResult,
                'senderids' => $senderids,
                'coverage'  => $coverage,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send SMS through the gateway with full pipeline: validation, normalization, coverage check, logging.
     */
    public function send(string|array $phone, string $message, string $eventType, ?int $orderId = null): array {
        // 1. Check if extension is enabled
        if (!$this->isEnabled()) {
            $this->debugLog('info', 'send', 'Extension is disabled, skipping send');
            return ['success' => false, 'skipped' => true, 'reason' => 'Extension disabled'];
        }

        // 2. Check credentials
        if (!$this->isConfigured()) {
            $this->debugLog('info', 'send', 'Credentials not configured, skipping send');
            return ['success' => false, 'skipped' => true, 'reason' => 'Not configured'];
        }

        // 3. Check balance
        $balance = $this->getBalance();
        if ($balance !== null && (float)$balance <= 0) {
            $this->debugLog('warning', 'send', 'Zero balance, skipping send');
            return ['success' => false, 'skipped' => true, 'reason' => 'Zero balance'];
        }

        // 4. Normalize phones
        $phones = [];
        if (is_array($phone)) {
            foreach ($phone as $p) {
                $phones[] = trim((string)$p);
            }
        } else {
            $parts = explode(',', (string)$phone);
            foreach ($parts as $p) {
                $p = trim($p);
                if ($p !== '') {
                    $phones[] = $p;
                }
            }
        }

        $this->debugLog('debug', 'send', 'Raw phones', ['phones' => $phones]);

        $normalized = [];
        $skipped = 0;
        $coverage = $this->getCoverage();
        $coveragePrefixes = is_array($coverage) ? $coverage : [];

        foreach ($phones as $raw) {
            $norm = $this->normalize($raw);

            // 5. Verify each phone
            if (!$this->verify($norm)) {
                $this->debugLog('info', 'send', 'Phone failed verification, skipping', ['phone' => $norm]);
                $this->smsLog([
                    'batch_id'    => '',
                    'order_id'    => $orderId,
                    'recipient'   => $norm,
                    'message'     => $message,
                    'status'      => 'skipped',
                    'event_type'  => $eventType,
                    'sender_id'   => $this->config->get('module_kwtsms_sender_id') ?: 'KWT-SMS',
                    'msg_id'      => '',
                    'points_charged' => 0,
                    'balance_after'  => 0,
                    'api_response'   => '',
                    'error_code'     => 'INVALID_PHONE',
                    'test_mode'      => (int)(bool)$this->config->get('module_kwtsms_test_mode'),
                ]);
                $skipped++;
                continue;
            }

            // 6. Check coverage
            if (!empty($coveragePrefixes)) {
                $countryCode = PhoneUtils::find_country_code($norm);
                if ($countryCode !== null) {
                    $found = false;
                    foreach ($coveragePrefixes as $prefix) {
                        $prefixStr = is_array($prefix) ? (string)($prefix['prefix'] ?? $prefix['code'] ?? '') : (string)$prefix;
                        if ($prefixStr === $countryCode) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $this->debugLog('info', 'send', 'Phone not covered, skipping', ['phone' => $norm, 'country_code' => $countryCode]);
                        $this->smsLog([
                            'batch_id'    => '',
                            'order_id'    => $orderId,
                            'recipient'   => $norm,
                            'message'     => $message,
                            'status'      => 'skipped',
                            'event_type'  => $eventType,
                            'sender_id'   => $this->config->get('module_kwtsms_sender_id') ?: 'KWT-SMS',
                            'msg_id'      => '',
                            'points_charged' => 0,
                            'balance_after'  => 0,
                            'api_response'   => '',
                            'error_code'     => 'NO_COVERAGE',
                            'test_mode'      => (int)(bool)$this->config->get('module_kwtsms_test_mode'),
                        ]);
                        $skipped++;
                        continue;
                    }
                }
            }

            $normalized[] = $norm;
        }

        // 7. Clean message
        $cleanedMessage = $this->cleanMessage($message);
        $this->debugLog('debug', 'send', 'Cleaned message', ['original_length' => strlen($message), 'cleaned_length' => strlen($cleanedMessage)]);

        if (trim($cleanedMessage) === '') {
            $this->debugLog('warning', 'send', 'Message is empty after cleaning');
            return ['success' => false, 'error' => 'Message is empty after cleaning'];
        }

        // 8. Deduplicate
        $normalized = array_values(array_unique($normalized));

        if (empty($normalized)) {
            $this->debugLog('info', 'send', 'No valid phones remaining after filtering');
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'skipped' => $skipped, 'reason' => 'No valid recipients'];
        }

        // 9. Generate batch_id
        $batchId = $this->generateBatchId();
        $this->debugLog('debug', 'send', 'Batch created', ['batch_id' => $batchId, 'phone_count' => count($normalized)]);

        $username = $this->config->get('module_kwtsms_username');
        $password = $this->config->get('module_kwtsms_password');
        $senderId = $this->config->get('module_kwtsms_sender_id') ?: 'KWT-SMS';
        $testMode = (bool)$this->config->get('module_kwtsms_test_mode');

        // 10. Bulk or single send
        if (count($normalized) > 200) {
            $result = $this->bulkSend($normalized, $cleanedMessage, $batchId, $eventType, $orderId);
            $result['skipped'] += $skipped;
            return $result;
        }

        // 11. Single API call
        try {
            $client = new KwtSMSClient($username, $password, $senderId, $testMode, '');
            $result = $client->send(implode(',', $normalized), $cleanedMessage);

            $this->debugLog('debug', 'send', 'API response', ['result' => $result]);

            $sent = 0;
            $failed = 0;

            if (isset($result['result']) && $result['result'] === 'OK') {
                // 12. Success: log each recipient
                foreach ($normalized as $recipient) {
                    $this->smsLog([
                        'batch_id'       => $batchId,
                        'order_id'       => $orderId,
                        'recipient'      => $recipient,
                        'message'        => $cleanedMessage,
                        'status'         => 'sent',
                        'event_type'     => $eventType,
                        'sender_id'      => $senderId,
                        'msg_id'         => $result['msg-id'] ?? '',
                        'points_charged' => (float)($result['points-charged'] ?? 0),
                        'balance_after'  => (float)($result['balance-after'] ?? 0),
                        'api_response'   => json_encode($result),
                        'error_code'     => '',
                        'test_mode'      => (int)$testMode,
                    ]);
                    $sent++;
                }

                // Update stored balance
                if (isset($result['balance-after'])) {
                    $this->updateBalance((float)$result['balance-after']);
                }
            } else {
                // 13. Failure: log each recipient
                foreach ($normalized as $recipient) {
                    $this->smsLog([
                        'batch_id'       => $batchId,
                        'order_id'       => $orderId,
                        'recipient'      => $recipient,
                        'message'        => $cleanedMessage,
                        'status'         => 'failed',
                        'event_type'     => $eventType,
                        'sender_id'      => $senderId,
                        'msg_id'         => '',
                        'points_charged' => 0,
                        'balance_after'  => 0,
                        'api_response'   => json_encode($result),
                        'error_code'     => $result['code'] ?? '',
                        'test_mode'      => (int)$testMode,
                    ]);
                    $failed++;
                }
            }

            return [
                'success'  => $sent > 0,
                'batch_id' => $batchId,
                'sent'     => $sent,
                'failed'   => $failed,
                'skipped'  => $skipped,
            ];
        } catch (\Exception $e) {
            $this->debugLog('error', 'send', 'Exception during send: ' . $e->getMessage());

            foreach ($normalized as $recipient) {
                $this->smsLog([
                    'batch_id'       => $batchId,
                    'order_id'       => $orderId,
                    'recipient'      => $recipient,
                    'message'        => $cleanedMessage,
                    'status'         => 'failed',
                    'event_type'     => $eventType,
                    'sender_id'      => $senderId,
                    'msg_id'         => '',
                    'points_charged' => 0,
                    'balance_after'  => 0,
                    'api_response'   => $e->getMessage(),
                    'error_code'     => 'EXCEPTION',
                    'test_mode'      => (int)$testMode,
                ]);
            }

            return [
                'success'  => false,
                'batch_id' => $batchId,
                'sent'     => 0,
                'failed'   => count($normalized),
                'skipped'  => $skipped,
                'error'    => $e->getMessage(),
            ];
        }
    }

    /**
     * Test the gateway by sending a test SMS (forces test mode).
     */
    public function testGateway(string $phone, string $message): array {
        $username = $this->config->get('module_kwtsms_username');
        $password = $this->config->get('module_kwtsms_password');
        $senderId = $this->config->get('module_kwtsms_sender_id') ?: 'KWT-SMS';

        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Credentials not configured.'];
        }

        $norm = $this->normalize($phone);

        if (!$this->verify($norm)) {
            return ['success' => false, 'error' => 'Invalid phone number.'];
        }

        $cleanedMessage = $this->cleanMessage($message);
        if (trim($cleanedMessage) === '') {
            return ['success' => false, 'error' => 'Message is empty after cleaning.'];
        }

        $batchId = $this->generateBatchId();

        try {
            // Force test mode to true
            $client = new KwtSMSClient($username, $password, $senderId, true, '');
            $result = $client->send($norm, $cleanedMessage);

            $this->debugLog('debug', 'test_gateway', 'Test SMS API response', ['result' => $result]);

            $success = isset($result['result']) && $result['result'] === 'OK';

            $this->smsLog([
                'batch_id'       => $batchId,
                'order_id'       => null,
                'recipient'      => $norm,
                'message'        => $cleanedMessage,
                'status'         => $success ? 'sent' : 'failed',
                'event_type'     => 'test_gateway',
                'sender_id'      => $senderId,
                'msg_id'         => $result['msg-id'] ?? '',
                'points_charged' => (float)($result['points-charged'] ?? 0),
                'balance_after'  => (float)($result['balance-after'] ?? 0),
                'api_response'   => json_encode($result),
                'error_code'     => $success ? '' : ($result['code'] ?? ''),
                'test_mode'      => 1,
            ]);

            if ($success && isset($result['balance-after'])) {
                $this->updateBalance((float)$result['balance-after']);
            }

            return [
                'success'  => $success,
                'batch_id' => $batchId,
                'result'   => $result,
            ];
        } catch (\Exception $e) {
            $this->debugLog('error', 'test_gateway', 'Exception: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalize a phone number: strip non-digits, convert Arabic digits, add default country code if needed.
     */
    public function normalize(string $phone): string {
        $normalized = PhoneUtils::normalize_phone($phone);

        if ($normalized === '') {
            return '';
        }

        // Check if the number starts with a country prefix that is in our coverage
        $countryCode = PhoneUtils::find_country_code($normalized);
        $coverage = $this->getCoverage();
        $coveredPrefixes = is_array($coverage) ? $coverage : [];

        // If no country code found, or the detected country is not in our coverage,
        // treat it as a local number and prepend the default country code.
        // This handles cases like "98765432" (Kuwait local) which would otherwise
        // be misidentified as Iran (country code 98).
        if ($countryCode === null || !in_array($countryCode, $coveredPrefixes)) {
            $defaultCode = $this->config->get('module_kwtsms_country_code');
            if (empty($defaultCode)) {
                $defaultCode = '965';
            }
            $normalized = $defaultCode . $normalized;
        }

        return $normalized;
    }

    /**
     * Verify a phone number: check length and format.
     */
    public function verify(string $phone): bool {
        $length = strlen($phone);
        if ($length < 7 || $length > 15) {
            return false;
        }

        [$isValid, , ] = PhoneUtils::validate_phone_input($phone);
        return $isValid;
    }

    /**
     * Clean a message: strip emoji, HTML, hidden chars, convert Arabic digits.
     */
    public function cleanMessage(string $message): string {
        return MessageUtils::clean_message($message);
    }

    /**
     * Check if API credentials are configured.
     */
    public function isConfigured(): bool {
        return !empty($this->config->get('module_kwtsms_username'))
            && !empty($this->config->get('module_kwtsms_password'));
    }

    /**
     * Check if the extension is enabled.
     */
    public function isEnabled(): bool {
        return (bool)$this->config->get('module_kwtsms_status');
    }

    /**
     * Get cached balance from the gateway table.
     */
    public function getBalance(): mixed {
        return $this->getGatewayCache('balance');
    }

    /**
     * Get cached sender IDs from the gateway table.
     */
    public function getSenderIds(): mixed {
        return $this->getGatewayCache('senderids');
    }

    /**
     * Get cached coverage data from the gateway table.
     */
    public function getCoverage(): mixed {
        return $this->getGatewayCache('coverage');
    }

    // ---- Private methods ----

    /**
     * Send in batches of 200 with delay and ERR013 retry backoff.
     */
    private function bulkSend(array $phones, string $message, string $batchId, string $eventType, ?int $orderId): array {
        $username = $this->config->get('module_kwtsms_username');
        $password = $this->config->get('module_kwtsms_password');
        $senderId = $this->config->get('module_kwtsms_sender_id') ?: 'KWT-SMS';
        $testMode = (bool)$this->config->get('module_kwtsms_test_mode');

        $chunks = array_chunk($phones, 200);
        $totalSent = 0;
        $totalFailed = 0;

        foreach ($chunks as $index => $batch) {
            if ($index > 0) {
                usleep(200000); // 0.2s delay between batches
            }

            $attempt = 0;
            $maxRetries = 3;
            $backoffDelays = [30, 60, 120];
            $result = null;

            while ($attempt <= $maxRetries) {
                try {
                    $client = new KwtSMSClient($username, $password, $senderId, $testMode, '');
                    $result = $client->send(implode(',', $batch), $message);

                    // Check for ERR013 (queue full) and retry with backoff
                    if (
                        isset($result['result']) && $result['result'] === 'ERROR' &&
                        isset($result['code']) && $result['code'] === 'ERR013' &&
                        $attempt < $maxRetries
                    ) {
                        $delay = $backoffDelays[$attempt];
                        $this->debugLog('warning', 'bulk_send', 'Queue full (ERR013), retrying', [
                            'batch' => $index,
                            'attempt' => $attempt + 1,
                            'delay' => $delay,
                        ]);
                        sleep($delay);
                        $attempt++;
                        continue;
                    }

                    break;
                } catch (\Exception $e) {
                    $this->debugLog('error', 'bulk_send', 'Exception in batch ' . $index . ': ' . $e->getMessage());
                    $result = ['result' => 'ERROR', 'code' => 'EXCEPTION', 'description' => $e->getMessage()];
                    break;
                }
            }

            $success = isset($result['result']) && $result['result'] === 'OK';

            foreach ($batch as $recipient) {
                $this->smsLog([
                    'batch_id'       => $batchId,
                    'order_id'       => $orderId,
                    'recipient'      => $recipient,
                    'message'        => $message,
                    'status'         => $success ? 'sent' : 'failed',
                    'event_type'     => $eventType,
                    'sender_id'      => $senderId,
                    'msg_id'         => $result['msg-id'] ?? '',
                    'points_charged' => (float)($result['points-charged'] ?? 0),
                    'balance_after'  => (float)($result['balance-after'] ?? 0),
                    'api_response'   => json_encode($result),
                    'error_code'     => $success ? '' : ($result['code'] ?? ''),
                    'test_mode'      => (int)$testMode,
                ]);

                if ($success) {
                    $totalSent++;
                } else {
                    $totalFailed++;
                }
            }

            if ($success && isset($result['balance-after'])) {
                $this->updateBalance((float)$result['balance-after']);
            }
        }

        return [
            'success'  => $totalSent > 0,
            'batch_id' => $batchId,
            'sent'     => $totalSent,
            'failed'   => $totalFailed,
            'skipped'  => 0,
        ];
    }

    /**
     * Recursively mask sensitive keys (password, username) in an array.
     */
    private function maskCredentials(array $data): array {
        $sensitiveKeys = ['password', 'username'];
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $value = $this->maskCredentials($value);
            } elseif (is_string($key) && in_array(strtolower($key), $sensitiveKeys)) {
                $value = '***';
            }
        }
        unset($value);
        return $data;
    }

    /**
     * Log a debug entry to the kwtsms_debug_log table.
     * Errors are always logged. Other levels require debug mode to be enabled.
     */
    private function debugLog(string $level, string $context, string $message, ?array $data = null): void {
        if ($level !== 'error' && !$this->config->get('module_kwtsms_debug')) {
            return;
        }

        // Mask credentials in data
        if ($data !== null) {
            $data = $this->maskCredentials($data);
        }

        $dataJson = $data !== null ? json_encode($data) : '';

        $this->db->query("INSERT INTO `" . DB_PREFIX . "kwtsms_debug_log` SET
            `level` = '" . $this->db->escape($level) . "',
            `context` = '" . $this->db->escape($context) . "',
            `message` = '" . $this->db->escape($message) . "',
            `data` = '" . $this->db->escape($dataJson) . "',
            `created_at` = NOW()");
    }

    /**
     * Log an SMS entry to the kwtsms_sms_log table.
     */
    private function smsLog(array $data): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kwtsms_sms_log` SET
            `batch_id` = '" . $this->db->escape($data['batch_id'] ?? '') . "',
            `order_id` = " . (int)($data['order_id'] ?? 0) . ",
            `recipient` = '" . $this->db->escape($data['recipient'] ?? '') . "',
            `message` = '" . $this->db->escape($data['message'] ?? '') . "',
            `status` = '" . $this->db->escape($data['status'] ?? '') . "',
            `event_type` = '" . $this->db->escape($data['event_type'] ?? '') . "',
            `sender_id` = '" . $this->db->escape($data['sender_id'] ?? '') . "',
            `msg_id` = '" . $this->db->escape($data['msg_id'] ?? '') . "',
            `points_charged` = '" . (float)($data['points_charged'] ?? 0) . "',
            `balance_after` = '" . (float)($data['balance_after'] ?? 0) . "',
            `api_response` = '" . $this->db->escape($data['api_response'] ?? '') . "',
            `error_code` = '" . $this->db->escape($data['error_code'] ?? '') . "',
            `test_mode` = " . (int)($data['test_mode'] ?? 0) . ",
            `created_at` = NOW()");
    }

    /**
     * Read a value from the kwtsms_gateway cache table.
     */
    private function getGatewayCache(string $key): mixed {
        $query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "kwtsms_gateway` WHERE `key` = '" . $this->db->escape($key) . "'");

        if ($query->num_rows) {
            $decoded = json_decode($query->row['value'], true);
            return $decoded !== null ? $decoded : $query->row['value'];
        }

        return null;
    }

    /**
     * Write a value to the kwtsms_gateway cache table (upsert).
     */
    private function setGatewayCache(string $key, mixed $value): void {
        $jsonValue = json_encode($value);

        $this->db->query("INSERT INTO `" . DB_PREFIX . "kwtsms_gateway` SET
            `key` = '" . $this->db->escape($key) . "',
            `value` = '" . $this->db->escape($jsonValue) . "',
            `updated_at` = NOW()
            ON DUPLICATE KEY UPDATE
            `value` = '" . $this->db->escape($jsonValue) . "',
            `updated_at` = NOW()");
    }

    /**
     * Update the cached balance value.
     */
    private function updateBalance(float $balance): void {
        $this->setGatewayCache('balance', $balance);
    }

    /**
     * Generate a UUID-like batch identifier.
     */
    private function generateBatchId(): string {
        $data = random_bytes(16);

        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            ord($data[0]) << 8 | ord($data[1]),
            ord($data[2]) << 8 | ord($data[3]),
            ord($data[4]) << 8 | ord($data[5]),
            ord($data[6]) << 8 | ord($data[7]),
            ord($data[8]) << 8 | ord($data[9]),
            ord($data[10]) << 8 | ord($data[11]),
            ord($data[12]) << 8 | ord($data[13]),
            ord($data[14]) << 8 | ord($data[15])
        );
    }
}
