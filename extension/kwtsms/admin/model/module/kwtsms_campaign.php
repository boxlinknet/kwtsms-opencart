<?php
namespace Opencart\Admin\Model\Extension\Kwtsms\Module;

class KwtsmsCampaign extends \Opencart\System\Engine\Model {
    /**
     * Get phone numbers of active customers, optionally filtered by group.
     */
    public function getCustomerPhones(string $audience, int $groupId = 0): array {
        $sql = "SELECT DISTINCT `telephone` FROM `" . DB_PREFIX . "customer` WHERE `status` = 1 AND `telephone` != ''";

        if ($audience === 'group' && $groupId > 0) {
            $sql .= " AND `customer_group_id` = " . (int)$groupId;
        }

        $query = $this->db->query($sql);

        return array_column($query->rows, 'telephone');
    }

    /**
     * Get all customer groups with their names.
     */
    public function getCustomerGroups(): array {
        $query = $this->db->query("SELECT cg.`customer_group_id`, cgd.`name`
            FROM `" . DB_PREFIX . "customer_group` cg
            LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd
                ON cg.`customer_group_id` = cgd.`customer_group_id`
                AND cgd.`language_id` = (SELECT `language_id` FROM `" . DB_PREFIX . "language` WHERE `code` = 'en-gb' LIMIT 1)
            ORDER BY cgd.`name`");

        return $query->rows;
    }

    /**
     * Create a new campaign record.
     */
    public function createCampaign(array $data): int {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "kwtsms_campaigns` SET
            `name` = '" . $this->db->escape($data['name'] ?? '') . "',
            `audience` = '" . $this->db->escape($data['audience'] ?? '') . "',
            `message` = '" . $this->db->escape($data['message'] ?? '') . "',
            `recipients_count` = " . (int)($data['recipients_count'] ?? 0) . ",
            `sent_count` = 0,
            `failed_count` = 0,
            `skipped_count` = 0,
            `credits_used` = 0,
            `status` = 'sending',
            `created_at` = NOW()");

        return $this->db->getLastId();
    }

    /**
     * Update a campaign record with send results.
     */
    public function updateCampaign(int $id, array $data): void {
        $sets = [];

        foreach (['sent_count', 'failed_count', 'skipped_count'] as $k) {
            if (isset($data[$k])) {
                $sets[] = "`" . $k . "` = " . (int)$data[$k];
            }
        }

        if (isset($data['credits_used'])) {
            $sets[] = "`credits_used` = " . (float)$data['credits_used'];
        }

        if (isset($data['status'])) {
            $sets[] = "`status` = '" . $this->db->escape($data['status']) . "'";
        }

        if (isset($data['completed_at'])) {
            $sets[] = "`completed_at` = NOW()";
        }

        if ($sets) {
            $this->db->query("UPDATE `" . DB_PREFIX . "kwtsms_campaigns` SET " . implode(', ', $sets) . " WHERE `id` = " . (int)$id);
        }
    }

    /**
     * Get campaign history with pagination.
     */
    public function getCampaigns(int $start = 0, int $limit = 20): array {
        return $this->db->query("SELECT * FROM `" . DB_PREFIX . "kwtsms_campaigns` ORDER BY `created_at` DESC LIMIT " . (int)$start . "," . (int)$limit)->rows;
    }

    /**
     * Get total number of campaigns.
     */
    public function getTotalCampaigns(): int {
        return (int)$this->db->query("SELECT COUNT(*) as `total` FROM `" . DB_PREFIX . "kwtsms_campaigns`")->row['total'];
    }
}
