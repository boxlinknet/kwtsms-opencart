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
     * Replace template placeholders with order data values.
     *
     * @param string $template Template string with {placeholders}
     * @param array $data Order data from getOrderData()
     * @return string Message with placeholders replaced
     */
    public function replacePlaceholders(string $template, array $data): string {
        $customerName = trim(($data['firstname'] ?? '') . ' ' . ($data['lastname'] ?? ''));
        $orderTotal = ($data['currency_code'] ?? '') . ' ' . number_format((float)($data['total'] ?? 0), 2);
        $storeName = !empty($data['store_name']) ? $data['store_name'] : $this->config->get('config_name');

        $placeholders = [
            '{order_id}'       => $data['order_id'] ?? '',
            '{customer_name}'  => $customerName,
            '{order_status}'   => $data['order_status_name'] ?? '',
            '{order_total}'    => $orderTotal,
            '{store_name}'     => $storeName,
            '{date}'           => date('Y-m-d H:i'),
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }
}
