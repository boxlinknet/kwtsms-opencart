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
    }

    /**
     * Drop all kwtSMS database tables.
     */
    public function uninstall(): void {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_sms_log`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_debug_log`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_otp_attempts`");
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "kwtsms_gateway`");
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
}
