<?php
namespace Opencart\Catalog\Model\Extension\Kwtsms\Module;

class Kwtsms extends \Opencart\System\Engine\Model {
    /**
     * Get order data joined with order status name for SMS template population.
     *
     * @param int $orderId
     * @return array Order data row or empty array if not found
     */
    public function getOrderData(int $orderId): array {
        $query = $this->db->query("
            SELECT
                o.`order_id`,
                o.`order_status_id`,
                o.`firstname`,
                o.`lastname`,
                o.`telephone`,
                o.`total`,
                o.`currency_code`,
                o.`language_id`,
                o.`store_name`,
                o.`date_added`,
                IFNULL(os.`name`, '') AS `order_status_name`
            FROM `" . DB_PREFIX . "order` o
            LEFT JOIN `" . DB_PREFIX . "order_status` os
                ON os.`order_status_id` = o.`order_status_id`
                AND os.`language_id` = o.`language_id`
            WHERE o.`order_id` = " . (int)$orderId . "
            LIMIT 1
        ");

        if ($query->num_rows) {
            return $query->row;
        }

        return [];
    }

    /**
     * Get the language code for a given language ID.
     *
     * @param int $languageId
     * @return string Language code (e.g. 'en-gb', 'ar') or 'en-gb' as default
     */
    public function getOrderLanguageCode(int $languageId): string {
        $query = $this->db->query("
            SELECT `code`
            FROM `" . DB_PREFIX . "language`
            WHERE `language_id` = " . (int)$languageId . "
            LIMIT 1
        ");

        if ($query->num_rows) {
            return $query->row['code'];
        }

        return 'en-gb';
    }

    /**
     * Get a template body from the kwtsms_templates table by event type.
     *
     * Returns the Arabic body if lang is 'ar' and body_ar is not empty,
     * otherwise returns body_en. Returns empty string if no row found.
     *
     * @param string $eventType Event type key (e.g. 'order_status_change')
     * @param string $lang Language hint: 'ar' or 'en' (default 'en')
     * @return string Template body or empty string
     */
    public function getTemplate(string $eventType, string $lang = 'en'): string {
        $query = $this->db->query("
            SELECT `body_en`, `body_ar`
            FROM `" . DB_PREFIX . "kwtsms_templates`
            WHERE `event_type` = '" . $this->db->escape($eventType) . "'
            LIMIT 1
        ");

        if (!$query->num_rows) {
            return '';
        }

        if ($lang === 'ar' && !empty($query->row['body_ar'])) {
            return $query->row['body_ar'];
        }

        return $query->row['body_en'] ?? '';
    }

    /**
     * Get the template for an order status change event.
     *
     * Tries a per-status override first (order_status_{id}), then falls back
     * to the generic order_status_change template.
     *
     * @param int $orderStatusId
     * @param string $lang Language hint: 'ar' or 'en'
     * @return string Template body or empty string
     */
    public function getOrderTemplate(int $orderStatusId, string $lang = 'en'): string {
        $template = $this->getTemplate('order_status_' . (int)$orderStatusId, $lang);

        if ($template !== '') {
            return $template;
        }

        return $this->getTemplate('order_status_change', $lang);
    }

    /**
     * Replace template placeholders with data values.
     *
     * Supports all placeholders: {order_id}, {customer_name}, {order_status},
     * {order_total}, {store_name}, {date}, {customer_email}, {customer_phone},
     * {product_name}, {product_model}, {product_quantity}, {stock_threshold},
     * {rating}, {return_reason}.
     *
     * Any placeholder whose key is absent from $data is replaced with an empty string.
     *
     * @param string $template Template string with {placeholders}
     * @param array $data Associative data array
     * @return string Message with placeholders replaced
     */
    public function replacePlaceholders(string $template, array $data): string {
        $customerName = trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''));
        $storeName = !empty($data['store_name']) ? $data['store_name'] : $this->config->get('config_name');

        $orderTotal = '';
        if (isset($data['total'])) {
            $orderTotal = ($data['currency_code'] ?? '') . ' ' . number_format((float)$data['total'], 2);
        }

        $placeholders = [
            '{order_id}'          => $data['order_id'] ?? '',
            '{customer_name}'     => $customerName,
            '{order_status}'      => $data['order_status_name'] ?? '',
            '{order_total}'       => $orderTotal,
            '{store_name}'        => $storeName,
            '{date}'              => date('Y-m-d H:i'),
            '{customer_email}'    => $data['email'] ?? '',
            '{customer_phone}'    => $data['telephone'] ?? '',
            '{product_name}'      => $data['product_name'] ?? '',
            '{product_model}'     => $data['product_model'] ?? '',
            '{product_quantity}'  => $data['product_quantity'] ?? '',
            '{stock_threshold}'   => $data['stock_threshold'] ?? '',
            '{rating}'            => $data['rating'] ?? '',
            '{return_reason}'     => $data['return_reason'] ?? '',
            '{products_summary}'  => $data['products_summary'] ?? '',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Get customer data by customer ID.
     *
     * @param int $customerId
     * @return array Customer row or empty array
     */
    public function getCustomerData(int $customerId): array {
        $query = $this->db->query("
            SELECT `customer_id`, `firstname`, `lastname`, `email`, `telephone`, `language_id`
            FROM `" . DB_PREFIX . "customer`
            WHERE `customer_id` = " . (int)$customerId . "
            LIMIT 1
        ");

        if ($query->num_rows) {
            return $query->row;
        }

        return [];
    }

    /**
     * Get all products for a given order.
     *
     * @param int $orderId
     * @return array List of order product rows
     */
    public function getOrderProducts(int $orderId): array {
        $query = $this->db->query("
            SELECT `product_id`, `name`, `model`, `quantity`
            FROM `" . DB_PREFIX . "order_product`
            WHERE `order_id` = " . (int)$orderId . "
        ");

        return $query->rows;
    }

    /**
     * Get the current stock quantity for a product.
     *
     * @param int $productId
     * @return int Stock quantity, 0 if product not found
     */
    public function getProductQuantity(int $productId): int {
        $query = $this->db->query("
            SELECT `quantity`
            FROM `" . DB_PREFIX . "product`
            WHERE `product_id` = " . (int)$productId . "
            LIMIT 1
        ");

        if ($query->num_rows) {
            return (int)$query->row['quantity'];
        }

        return 0;
    }

    /**
     * Check whether a low-stock alert is currently active (not reset) for a product.
     *
     * @param int $productId
     * @return bool True if an active (non-reset) alert exists
     */
    public function hasActiveLowStockAlert(int $productId): bool {
        $query = $this->db->query("
            SELECT `id`
            FROM `" . DB_PREFIX . "kwtsms_low_stock_alerts`
            WHERE `product_id` = " . (int)$productId . "
              AND `reset_at` IS NULL
            LIMIT 1
        ");

        return $query->num_rows > 0;
    }

    /**
     * Record or refresh a low-stock alert for a product.
     *
     * Uses INSERT ... ON DUPLICATE KEY UPDATE so that re-alerting the same
     * product simply refreshes alerted_at and clears reset_at.
     *
     * @param int $productId
     * @return void
     */
    public function setLowStockAlert(int $productId): void {
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "kwtsms_low_stock_alerts`
            SET `product_id` = " . (int)$productId . ",
                `alerted_at` = NOW(),
                `reset_at` = NULL
            ON DUPLICATE KEY UPDATE
                `alerted_at` = NOW(),
                `reset_at` = NULL
        ");
    }

    /**
     * Get review data including product info for SMS placeholders.
     *
     * @param int $reviewId
     * @return array Review data row or empty array
     */
    public function getReviewData(int $reviewId): array {
        $query = $this->db->query("
            SELECT
                r.`review_id`,
                r.`author` AS `firstname`,
                '' AS `lastname`,
                r.`rating`,
                pd.`name` AS `product_name`,
                p.`model` AS `product_model`
            FROM `" . DB_PREFIX . "review` r
            LEFT JOIN `" . DB_PREFIX . "product_description` pd
                ON r.`product_id` = pd.`product_id`
                AND pd.`language_id` = (
                    SELECT `language_id`
                    FROM `" . DB_PREFIX . "language`
                    WHERE `code` = 'en-gb'
                    LIMIT 1
                )
            LEFT JOIN `" . DB_PREFIX . "product` p
                ON r.`product_id` = p.`product_id`
            WHERE r.`review_id` = " . (int)$reviewId . "
            LIMIT 1
        ");

        if ($query->num_rows) {
            return $query->row;
        }

        return [];
    }

    /**
     * Get return request data including the return reason for SMS placeholders.
     *
     * @param int $returnId
     * @return array Return data row or empty array
     */
    public function getReturnData(int $returnId): array {
        $query = $this->db->query("
            SELECT
                r.`return_id`,
                r.`order_id`,
                r.`firstname`,
                r.`lastname`,
                r.`email`,
                r.`telephone`,
                r.`product` AS `product_name`,
                rr.`name` AS `return_reason`
            FROM `" . DB_PREFIX . "return` r
            LEFT JOIN `" . DB_PREFIX . "return_reason` rr
                ON r.`return_reason_id` = rr.`return_reason_id`
                AND rr.`language_id` = (
                    SELECT `language_id`
                    FROM `" . DB_PREFIX . "language`
                    WHERE `code` = 'en-gb'
                    LIMIT 1
                )
            WHERE r.`return_id` = " . (int)$returnId . "
            LIMIT 1
        ");

        if ($query->num_rows) {
            return $query->row;
        }

        return [];
    }

    /**
     * Create a new OTP code for a phone number.
     *
     * Invalidates any existing unexpired codes for the same phone, generates
     * a random numeric code, and stores it in kwtsms_otp_attempts.
     *
     * @param string $phone Normalized phone number
     * @param string $ipAddress Requester IP address
     * @param int $codeLength Number of digits (default 6)
     * @param int $expiryMinutes Minutes until code expires (default 5)
     * @return array ['id' => int, 'code' => string]
     */
    public function createOtp(string $phone, string $ipAddress, int $codeLength = 6, int $expiryMinutes = 5): array {
        // Invalidate existing unexpired codes for this phone
        $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_otp_attempts` SET `status` = 'expired' WHERE `phone` = '" . $this->db->escape($phone) . "' AND `status` = 'sent' AND `expires_at` > NOW()");

        // Generate random code
        $max = (int)pow(10, $codeLength) - 1;
        $code = str_pad((string)random_int(0, $max), $codeLength, '0', STR_PAD_LEFT);

        $this->db->query("INSERT INTO `" . DB_PREFIX . "kwtsms_otp_attempts` SET
            `phone` = '" . $this->db->escape($phone) . "',
            `ip_address` = '" . $this->db->escape($ipAddress) . "',
            `otp_code` = '" . $this->db->escape($code) . "',
            `status` = 'sent',
            `attempts` = 0,
            `created_at` = NOW(),
            `expires_at` = DATE_ADD(NOW(), INTERVAL " . (int)$expiryMinutes . " MINUTE)");

        return [
            'id'   => $this->db->getLastId(),
            'code' => $code,
        ];
    }

    /**
     * Verify an OTP code for a phone number.
     *
     * Checks the most recent unexpired code for the phone. Increments attempt
     * counter on mismatch and marks as 'failed' after 3 wrong attempts.
     *
     * @param string $phone Normalized phone number
     * @param string $code User-supplied OTP code
     * @return array ['valid' => bool, 'error' => string|null, 'remaining' => int|null]
     */
    public function verifyOtp(string $phone, string $code): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kwtsms_otp_attempts`
            WHERE `phone` = '" . $this->db->escape($phone) . "'
            AND `status` = 'sent'
            AND `expires_at` > NOW()
            ORDER BY `id` DESC LIMIT 1");

        if (!$query->num_rows) {
            return ['valid' => false, 'error' => 'expired'];
        }

        $row = $query->row;

        if ($row['otp_code'] !== $code) {
            $attempts = (int)$row['attempts'] + 1;
            if ($attempts >= 3) {
                $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_otp_attempts` SET `status` = 'failed', `attempts` = " . $attempts . " WHERE `id` = " . (int)$row['id']);
                return ['valid' => false, 'error' => 'failed'];
            }
            $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_otp_attempts` SET `attempts` = " . $attempts . " WHERE `id` = " . (int)$row['id']);
            return ['valid' => false, 'error' => 'invalid', 'remaining' => 3 - $attempts];
        }

        $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_otp_attempts` SET `status` = 'verified' WHERE `id` = " . (int)$row['id']);
        return ['valid' => true];
    }

    /**
     * Check whether a phone number or IP address has exceeded the OTP rate limit.
     *
     * @param string $phone Normalized phone number
     * @param string $ip Requester IP address
     * @param int $maxPhone Max OTP requests per phone per hour (default 5)
     * @param int $maxIp Max OTP requests per IP per hour (default 10)
     * @return array ['allowed' => bool, 'reason' => string|null]
     */
    public function checkOtpRateLimit(string $phone, string $ip, int $maxPhone = 5, int $maxIp = 10): array {
        $phoneQuery = $this->db->query("SELECT COUNT(*) as `total` FROM `" . DB_PREFIX . "kwtsms_otp_attempts`
            WHERE `phone` = '" . $this->db->escape($phone) . "'
            AND `created_at` > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $phoneCount = (int)$phoneQuery->row['total'];

        if ($phoneCount >= $maxPhone) {
            return ['allowed' => false, 'reason' => 'phone_limit'];
        }

        $ipQuery = $this->db->query("SELECT COUNT(*) as `total` FROM `" . DB_PREFIX . "kwtsms_otp_attempts`
            WHERE `ip_address` = '" . $this->db->escape($ip) . "'
            AND `created_at` > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $ipCount = (int)$ipQuery->row['total'];

        if ($ipCount >= $maxIp) {
            return ['allowed' => false, 'reason' => 'ip_limit'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Find abandoned carts: customers with idle carts older than $delayMinutes
     * who have a phone number and have not placed an order in the delay period.
     *
     * @param int $delayMinutes Minutes of inactivity before a cart is considered abandoned
     * @return array List of abandoned cart rows
     */
    public function getAbandonedCarts(int $delayMinutes): array {
        $query = $this->db->query("
            SELECT c.customer_id, c.firstname, c.lastname, c.telephone, c.email, c.language_id,
                   MAX(ca.date_added) as last_cart_activity,
                   GROUP_CONCAT(DISTINCT pd.name SEPARATOR ', ') as products_summary,
                   SUM(ca.quantity * p.price) as cart_total
            FROM `" . DB_PREFIX . "cart` ca
            INNER JOIN `" . DB_PREFIX . "customer` c ON ca.customer_id = c.customer_id
            INNER JOIN `" . DB_PREFIX . "product` p ON ca.product_id = p.product_id
            LEFT JOIN `" . DB_PREFIX . "product_description` pd ON p.product_id = pd.product_id
                AND pd.language_id = (SELECT language_id FROM `" . DB_PREFIX . "language` WHERE code = 'en-gb' LIMIT 1)
            WHERE ca.customer_id > 0
                AND c.telephone != ''
                AND c.status = 1
            GROUP BY ca.customer_id
            HAVING last_cart_activity < DATE_SUB(NOW(), INTERVAL " . (int)$delayMinutes . " MINUTE)
                AND ca.customer_id NOT IN (
                    SELECT DISTINCT customer_id FROM `" . DB_PREFIX . "order`
                    WHERE date_added > DATE_SUB(NOW(), INTERVAL " . (int)$delayMinutes . " MINUTE)
                    AND order_status_id > 0
                )
        ");

        return $query->rows;
    }

    /**
     * Check if an abandoned cart is already tracked (detected or sent) for a customer/hash.
     *
     * @param int $customerId
     * @param string $cartHash
     * @return bool
     */
    public function isCartAlreadyTracked(int $customerId, string $cartHash): bool {
        $query = $this->db->query("SELECT id FROM `" . DB_PREFIX . "kwtsms_abandoned_carts`
            WHERE customer_id = " . (int)$customerId . "
            AND cart_hash = '" . $this->db->escape($cartHash) . "'
            AND status IN ('sent', 'detected')
            LIMIT 1");
        return $query->num_rows > 0;
    }

    /**
     * Record a newly detected abandoned cart.
     *
     * @param int $customerId
     * @param string $cartHash
     * @param float $cartTotal
     * @param string $productsSummary
     * @return int Inserted row ID, or 0 on failure
     */
    public function trackAbandonedCart(int $customerId, string $cartHash, float $cartTotal, string $productsSummary): int {
        $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "kwtsms_abandoned_carts` SET
            customer_id = " . (int)$customerId . ",
            cart_hash = '" . $this->db->escape($cartHash) . "',
            cart_total = " . (float)$cartTotal . ",
            products_summary = '" . $this->db->escape(substr($productsSummary, 0, 255)) . "',
            status = 'detected',
            detected_at = NOW(),
            created_at = NOW()");
        return $this->db->getLastId();
    }

    /**
     * Mark an abandoned cart record as SMS sent.
     *
     * @param int $id Row ID in kwtsms_abandoned_carts
     * @return void
     */
    public function markAbandonedCartSent(int $id): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_abandoned_carts`
            SET status = 'sent', sent_at = NOW()
            WHERE id = " . (int)$id);
    }
}
