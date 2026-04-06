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
}
