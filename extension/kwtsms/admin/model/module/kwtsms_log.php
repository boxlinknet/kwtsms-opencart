<?php
namespace Opencart\Admin\Model\Extension\Kwtsms\Module;

class KwtsmsLog extends \Opencart\System\Engine\Model {
    /**
     * Get SMS log entries with optional filters and pagination.
     *
     * @param array $filter Keys: status, event_type, date_from, date_to
     * @param int $start Offset for pagination
     * @param int $limit Number of rows to return
     * @return array
     */
    public function getSmsLogs(array $filter, int $start, int $limit): array {
        $sql = "SELECT * FROM `" . DB_PREFIX . "kwtsms_sms_log`";
        $sql .= $this->buildSmsWhere($filter);
        $sql .= " ORDER BY `created_at` DESC";
        $sql .= " LIMIT " . (int)$start . ", " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Get total count of SMS log entries matching filters.
     */
    public function getTotalSmsLogs(array $filter): int {
        $sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "kwtsms_sms_log`";
        $sql .= $this->buildSmsWhere($filter);

        $query = $this->db->query($sql);

        return (int)$query->row['total'];
    }

    /**
     * Clear all SMS log entries.
     */
    public function clearSmsLogs(): void {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "kwtsms_sms_log`");
    }

    /**
     * Get debug log entries with optional filters and pagination.
     *
     * @param array $filter Keys: level, context, date_from, date_to
     * @param int $start Offset for pagination
     * @param int $limit Number of rows to return
     * @return array
     */
    public function getDebugLogs(array $filter, int $start, int $limit): array {
        $sql = "SELECT * FROM `" . DB_PREFIX . "kwtsms_debug_log`";
        $sql .= $this->buildDebugWhere($filter);
        $sql .= " ORDER BY `created_at` DESC";
        $sql .= " LIMIT " . (int)$start . ", " . (int)$limit;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    /**
     * Get total count of debug log entries matching filters.
     */
    public function getTotalDebugLogs(array $filter): int {
        $sql = "SELECT COUNT(*) AS `total` FROM `" . DB_PREFIX . "kwtsms_debug_log`";
        $sql .= $this->buildDebugWhere($filter);

        $query = $this->db->query($sql);

        return (int)$query->row['total'];
    }

    /**
     * Clear all debug log entries.
     */
    public function clearDebugLogs(): void {
        $this->db->query("TRUNCATE TABLE `" . DB_PREFIX . "kwtsms_debug_log`");
    }

    /**
     * Build WHERE clause for SMS log queries.
     */
    private function buildSmsWhere(array $filter): string {
        $conditions = [];

        if (!empty($filter['status'])) {
            $conditions[] = "`status` = '" . $this->db->escape($filter['status']) . "'";
        }

        if (!empty($filter['event_type'])) {
            $conditions[] = "`event_type` = '" . $this->db->escape($filter['event_type']) . "'";
        }

        if (!empty($filter['date_from'])) {
            $conditions[] = "`created_at` >= '" . $this->db->escape($filter['date_from']) . "'";
        }

        if (!empty($filter['date_to'])) {
            $conditions[] = "`created_at` <= '" . $this->db->escape($filter['date_to']) . " 23:59:59'";
        }

        if (!empty($conditions)) {
            return " WHERE " . implode(" AND ", $conditions);
        }

        return '';
    }

    /**
     * Build WHERE clause for debug log queries.
     */
    private function buildDebugWhere(array $filter): string {
        $conditions = [];

        if (!empty($filter['level'])) {
            $conditions[] = "`level` = '" . $this->db->escape($filter['level']) . "'";
        }

        if (!empty($filter['context'])) {
            $conditions[] = "`context` = '" . $this->db->escape($filter['context']) . "'";
        }

        if (!empty($filter['date_from'])) {
            $conditions[] = "`created_at` >= '" . $this->db->escape($filter['date_from']) . "'";
        }

        if (!empty($filter['date_to'])) {
            $conditions[] = "`created_at` <= '" . $this->db->escape($filter['date_to']) . " 23:59:59'";
        }

        if (!empty($conditions)) {
            return " WHERE " . implode(" AND ", $conditions);
        }

        return '';
    }
}
