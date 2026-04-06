<?php
namespace Opencart\Admin\Model\Extension\Kwtsms\Module;

class Kwtsms extends \Opencart\System\Engine\Model {
    /**
     * Create all required database tables for the kwtSMS extension.
     */
    public function install(): void {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kwtsms_sms_log` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `batch_id` VARCHAR(64) NOT NULL DEFAULT '',
            `order_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `recipient` VARCHAR(32) NOT NULL DEFAULT '',
            `message` TEXT NOT NULL,
            `status` VARCHAR(16) NOT NULL DEFAULT '',
            `event_type` VARCHAR(32) NOT NULL DEFAULT '',
            `sender_id` VARCHAR(32) NOT NULL DEFAULT '',
            `msg_id` VARCHAR(64) NOT NULL DEFAULT '',
            `points_charged` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `balance_after` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            `api_response` TEXT NOT NULL,
            `error_code` VARCHAR(32) NOT NULL DEFAULT '',
            `test_mode` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_batch_id` (`batch_id`),
            INDEX `idx_order_id` (`order_id`),
            INDEX `idx_status` (`status`),
            INDEX `idx_event_type` (`event_type`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kwtsms_debug_log` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `level` VARCHAR(16) NOT NULL DEFAULT '',
            `context` VARCHAR(64) NOT NULL DEFAULT '',
            `message` TEXT NOT NULL,
            `data` TEXT NOT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_level` (`level`),
            INDEX `idx_context` (`context`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kwtsms_otp_attempts` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `phone` VARCHAR(32) NOT NULL DEFAULT '',
            `ip_address` VARCHAR(45) NOT NULL DEFAULT '',
            `otp_code` VARCHAR(16) NOT NULL DEFAULT '',
            `status` VARCHAR(16) NOT NULL DEFAULT '',
            `attempts` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `created_at` DATETIME NOT NULL,
            `expires_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            INDEX `idx_phone` (`phone`),
            INDEX `idx_ip_address` (`ip_address`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kwtsms_gateway` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `key` VARCHAR(64) NOT NULL DEFAULT '',
            `value` TEXT NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `idx_key` (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kwtsms_templates` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `event_type` VARCHAR(50) NOT NULL,
            `name` VARCHAR(100) NOT NULL DEFAULT '',
            `body_en` TEXT NOT NULL,
            `body_ar` TEXT NOT NULL,
            `default_en` TEXT NOT NULL,
            `default_ar` TEXT NOT NULL,
            `placeholders` VARCHAR(255) NOT NULL DEFAULT '',
            `sort_order` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `idx_event_type` (`event_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "kwtsms_low_stock_alerts` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `product_id` INT(11) UNSIGNED NOT NULL,
            `alerted_at` DATETIME NOT NULL,
            `reset_at` DATETIME DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `idx_product_id` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    }

    /**
     * Drop all kwtSMS database tables.
     */
    public function uninstall(): void {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_sms_log`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_debug_log`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_otp_attempts`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_gateway`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_templates`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_low_stock_alerts`");
    }

    /**
     * Get SMS statistics grouped by status for a given period.
     *
     * @param string $period One of: today, 7days, 30days
     * @return array Stats array with keys: sent, failed, skipped, queued
     */
    public function getSmsStats(string $period): array {
        $where = '';

        switch ($period) {
            case 'today':
                $where = " WHERE `created_at` >= CURDATE()";
                break;
            case '7days':
                $where = " WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case '30days':
                $where = " WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
            default:
                $where = '';
                break;
        }

        $query = $this->db->query("SELECT `status`, COUNT(*) AS `total` FROM `" . DB_PREFIX . "kwtsms_sms_log`" . $where . " GROUP BY `status`");

        $stats = [
            'sent'    => 0,
            'failed'  => 0,
            'skipped' => 0,
            'queued'  => 0,
        ];

        foreach ($query->rows as $row) {
            $status = $row['status'];
            if (isset($stats[$status])) {
                $stats[$status] = (int)$row['total'];
            }
        }

        return $stats;
    }

    /**
     * Seed default SMS templates into the kwtsms_templates table.
     * Uses INSERT IGNORE to avoid duplicates on the UNIQUE event_type index.
     */
    public function seedTemplates(): void {
        $templates = [
            [
                'event_type'   => 'order_status_change',
                'name'         => 'Customer Order Status SMS (Default)',
                'body_en'      => 'Hi {customer_name}, your order #{order_id} status has been updated to: {order_status}. Thank you for shopping at {store_name}.',
                'body_ar'      => '{customer_name} مرحبا، تم تحديث حالة طلبك رقم #{order_id} الى: {order_status}. شكرا لتسوقك في {store_name}.',
                'placeholders' => '{order_id}, {customer_name}, {order_status}, {order_total}, {store_name}, {date}',
                'sort_order'   => 1,
            ],
            [
                'event_type'   => 'admin_new_order',
                'name'         => 'Admin New Paid Order SMS',
                'body_en'      => 'New paid order #{order_id} from {customer_name}. Total: {order_total}. Date: {date}.',
                'body_ar'      => 'طلب مدفوع جديد #{order_id} من {customer_name}. المبلغ: {order_total}. التاريخ: {date}.',
                'placeholders' => '{order_id}, {customer_name}, {order_status}, {order_total}, {store_name}, {date}',
                'sort_order'   => 50,
            ],
            [
                'event_type'   => 'admin_problem_order',
                'name'         => 'Admin Problem Status SMS',
                'body_en'      => 'Order #{order_id} status changed to {order_status}. Customer: {customer_name}. Total: {order_total}.',
                'body_ar'      => 'الطلب #{order_id} تغيرت حالته الى {order_status}. العميل: {customer_name}. المبلغ: {order_total}.',
                'placeholders' => '{order_id}, {customer_name}, {order_status}, {order_total}, {store_name}, {date}',
                'sort_order'   => 51,
            ],
            [
                'event_type'   => 'customer_registered',
                'name'         => 'Customer Welcome SMS',
                'body_en'      => 'Welcome {customer_name}! Thank you for registering at {store_name}. We are glad to have you.',
                'body_ar'      => '{customer_name} مرحبا بك! شكرا لتسجيلك في {store_name}. يسعدنا انضمامك.',
                'placeholders' => '{customer_name}, {store_name}, {date}',
                'sort_order'   => 100,
            ],
            [
                'event_type'   => 'admin_new_customer',
                'name'         => 'Admin New Customer Alert',
                'body_en'      => 'New customer registered: {customer_name}, Phone: {customer_phone}, Email: {customer_email}. Date: {date}.',
                'body_ar'      => 'عميل جديد: {customer_name}، هاتف: {customer_phone}، بريد: {customer_email}. التاريخ: {date}.',
                'placeholders' => '{customer_name}, {customer_email}, {customer_phone}, {store_name}, {date}',
                'sort_order'   => 101,
            ],
            [
                'event_type'   => 'low_stock',
                'name'         => 'Admin Low Stock Alert',
                'body_en'      => 'Low stock alert: {product_name} (Model: {product_model}) has {product_quantity} units remaining (threshold: {stock_threshold}). Store: {store_name}.',
                'body_ar'      => 'تنبيه مخزون منخفض: {product_name} (موديل: {product_model}) متبقي {product_quantity} وحدة (الحد: {stock_threshold}). متجر: {store_name}.',
                'placeholders' => '{product_name}, {product_model}, {product_quantity}, {stock_threshold}, {store_name}',
                'sort_order'   => 200,
            ],
            [
                'event_type'   => 'admin_new_review',
                'name'         => 'Admin New Review Alert',
                'body_en'      => 'New review for {product_name} by {customer_name}. Rating: {rating}/5. Store: {store_name}. Date: {date}.',
                'body_ar'      => 'تقييم جديد لـ {product_name} من {customer_name}. التقييم: {rating}/5. متجر: {store_name}. التاريخ: {date}.',
                'placeholders' => '{customer_name}, {product_name}, {rating}, {store_name}, {date}',
                'sort_order'   => 201,
            ],
            [
                'event_type'   => 'admin_return_request',
                'name'         => 'Admin Return Request Alert',
                'body_en'      => 'Return request for order #{order_id}. Product: {product_name}. Customer: {customer_name}. Reason: {return_reason}. Date: {date}.',
                'body_ar'      => 'طلب إرجاع للطلب #{order_id}. المنتج: {product_name}. العميل: {customer_name}. السبب: {return_reason}. التاريخ: {date}.',
                'placeholders' => '{customer_name}, {order_id}, {product_name}, {return_reason}, {store_name}, {date}',
                'sort_order'   => 300,
            ],
        ];

        foreach ($templates as $t) {
            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "kwtsms_templates` SET
                `event_type`   = '" . $this->db->escape($t['event_type']) . "',
                `name`         = '" . $this->db->escape($t['name']) . "',
                `body_en`      = '" . $this->db->escape($t['body_en']) . "',
                `body_ar`      = '" . $this->db->escape($t['body_ar']) . "',
                `default_en`   = '" . $this->db->escape($t['body_en']) . "',
                `default_ar`   = '" . $this->db->escape($t['body_ar']) . "',
                `placeholders` = '" . $this->db->escape($t['placeholders']) . "',
                `sort_order`   = '" . (int)$t['sort_order'] . "'");
        }
    }

    /**
     * Seed per-status template override rows from oc_order_status.
     * Each row allows customizing the SMS body for a specific order status.
     * Empty body_en/body_ar means the default order_status_change template is used.
     */
    public function seedPerStatusTemplates(): void {
        $query = $this->db->query("SELECT os.`order_status_id`, os.`name`
            FROM `" . DB_PREFIX . "order_status` os
            INNER JOIN `" . DB_PREFIX . "language` l ON (os.`language_id` = l.`language_id`)
            WHERE l.`code` = 'en-gb'
            ORDER BY os.`order_status_id` ASC");

        $sort = 10;

        foreach ($query->rows as $row) {
            $eventType = 'order_status_' . (int)$row['order_status_id'];
            $name = 'Customer Order Status: ' . $row['name'];

            $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "kwtsms_templates` SET
                `event_type`   = '" . $this->db->escape($eventType) . "',
                `name`         = '" . $this->db->escape($name) . "',
                `body_en`      = '',
                `body_ar`      = '',
                `default_en`   = '',
                `default_ar`   = '',
                `placeholders` = '{order_id}, {customer_name}, {order_status}, {order_total}, {store_name}, {date}',
                `sort_order`   = '" . (int)$sort . "'");

            $sort++;
        }
    }

    /**
     * Migrate legacy templates stored in oc_setting to the kwtsms_templates table.
     * Reads old config keys, updates the matching template rows, then removes the old keys.
     */
    public function migrateTemplatesFromSettings(): void {
        $mapping = [
            'module_kwtsms_template_customer_order_en' => ['event_type' => 'order_status_change', 'column' => 'en'],
            'module_kwtsms_template_customer_order_ar' => ['event_type' => 'order_status_change', 'column' => 'ar'],
            'module_kwtsms_template_admin_paid_en'     => ['event_type' => 'admin_new_order',     'column' => 'en'],
            'module_kwtsms_template_admin_problem_en'  => ['event_type' => 'admin_problem_order', 'column' => 'en'],
        ];

        foreach ($mapping as $configKey => $target) {
            $value = $this->config->get($configKey);

            if ($value) {
                $escaped = $this->db->escape($value);
                $eventType = $this->db->escape($target['event_type']);

                if ($target['column'] === 'en') {
                    $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_templates` SET
                        `body_en`    = '" . $escaped . "',
                        `default_en` = '" . $escaped . "'
                        WHERE `event_type` = '" . $eventType . "'");
                } else {
                    $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_templates` SET
                        `body_ar`    = '" . $escaped . "',
                        `default_ar` = '" . $escaped . "'
                        WHERE `event_type` = '" . $eventType . "'");
                }
            }
        }

        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting`
            WHERE `code` = 'module_kwtsms'
            AND `key` LIKE 'module_kwtsms_template_%'");
    }

    /**
     * Get all SMS templates ordered by sort_order.
     *
     * @return array
     */
    public function getTemplates(): array {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kwtsms_templates`
            ORDER BY `sort_order` ASC, `id` ASC");

        return $query->rows;
    }

    /**
     * Get a single template by its event_type.
     *
     * @param string $eventType
     * @return array|null
     */
    public function getTemplate(string $eventType): array|null {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "kwtsms_templates`
            WHERE `event_type` = '" . $this->db->escape($eventType) . "'
            LIMIT 1");

        return $query->num_rows ? $query->row : null;
    }

    /**
     * Update a template's body text.
     *
     * @param int    $id
     * @param string $bodyEn
     * @param string $bodyAr
     */
    public function updateTemplate(int $id, string $bodyEn, string $bodyAr): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_templates` SET
            `body_en` = '" . $this->db->escape($bodyEn) . "',
            `body_ar` = '" . $this->db->escape($bodyAr) . "'
            WHERE `id` = '" . (int)$id . "'");
    }

    /**
     * Reset a template back to its default body text.
     *
     * @param int $id
     */
    public function resetTemplate(int $id): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_templates` SET
            `body_en` = `default_en`,
            `body_ar` = `default_ar`
            WHERE `id` = '" . (int)$id . "'");
    }

    /**
     * Check if a product has an active (un-reset) low stock alert.
     *
     * @param int $productId
     * @return bool
     */
    public function hasActiveLowStockAlert(int $productId): bool {
        $query = $this->db->query("SELECT `id` FROM `" . DB_PREFIX . "kwtsms_low_stock_alerts`
            WHERE `product_id` = '" . (int)$productId . "'
            AND `reset_at` IS NULL
            LIMIT 1");

        return $query->num_rows > 0;
    }

    /**
     * Record a low stock alert for a product.
     * Uses INSERT ON DUPLICATE KEY UPDATE to upsert based on the UNIQUE product_id index.
     *
     * @param int $productId
     */
    public function setLowStockAlert(int $productId): void {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kwtsms_low_stock_alerts` SET
            `product_id` = '" . (int)$productId . "',
            `alerted_at` = NOW(),
            `reset_at`   = NULL
            ON DUPLICATE KEY UPDATE
            `alerted_at` = NOW(),
            `reset_at`   = NULL");
    }

    /**
     * Reset a low stock alert when the product is restocked.
     *
     * @param int $productId
     */
    public function resetLowStockAlert(int $productId): void {
        $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_low_stock_alerts` SET
            `reset_at` = NOW()
            WHERE `product_id` = '" . (int)$productId . "'
            AND `reset_at` IS NULL");
    }
}
