<?php
namespace Opencart\Admin\Model\Extension\Kwtsms\Module;

class KwtsmsGateway extends \Opencart\System\Engine\Model {
    /**
     * Insert or update a cache entry in the gateway table.
     */
    public function setCache(string $key, mixed $value): void {
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
     * Read a cache entry from the gateway table.
     *
     * @return mixed Decoded JSON value or null if not found
     */
    public function getCache(string $key): mixed {
        $query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "kwtsms_gateway` WHERE `key` = '" . $this->db->escape($key) . "'");

        if ($query->num_rows) {
            $decoded = json_decode($query->row['value'], true);
            return $decoded !== null ? $decoded : $query->row['value'];
        }

        return null;
    }

    /**
     * Clear all entries from the gateway cache table.
     */
    public function clearCache(): void {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "kwtsms_gateway`");
    }

    /**
     * Get the updated_at timestamp for a specific cache key.
     *
     * @return string|null ISO datetime string or null if not found
     */
    public function getCacheUpdatedAt(string $key): ?string {
        $query = $this->db->query("SELECT `updated_at` FROM `" . DB_PREFIX . "kwtsms_gateway` WHERE `key` = '" . $this->db->escape($key) . "'");

        if ($query->num_rows) {
            return $query->row['updated_at'];
        }

        return null;
    }
}
