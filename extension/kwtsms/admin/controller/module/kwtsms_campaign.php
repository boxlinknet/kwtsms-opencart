<?php
namespace Opencart\Admin\Controller\Extension\Kwtsms\Module;

class KwtsmsCampaign extends \Opencart\System\Engine\Controller {
    /**
     * Preview: count recipients and estimate credits for a campaign.
     * Returns JSON.
     */
    public function preview(): void {
        $json = [];

        if (!$this->user->hasPermission('access', 'extension/kwtsms/module/kwtsms_campaign')) {
            $json['error'] = 'Permission denied.';

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $audience = $this->request->post['audience'] ?? '';
        $groupId = (int)($this->request->post['group_id'] ?? 0);
        $customNumbers = trim($this->request->post['custom_numbers'] ?? '');

        $this->load->model('extension/kwtsms/module/kwtsms_campaign');

        if ($audience === 'custom' && $customNumbers) {
            $phones = array_filter(array_map('trim', explode(',', $customNumbers)));
            $count = count($phones);
        } else {
            $phones = $this->model_extension_kwtsms_module_kwtsms_campaign->getCustomerPhones($audience, $groupId);
            $count = count($phones);
        }

        $json['count'] = $count;
        $json['estimated_credits'] = $count;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Send: execute a bulk SMS campaign.
     * Returns JSON with send results.
     */
    public function send(): void {
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms_campaign')) {
            $json['error'] = 'Permission denied.';

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $audience = $this->request->post['audience'] ?? '';
        $groupId = (int)($this->request->post['group_id'] ?? 0);
        $customNumbers = trim($this->request->post['custom_numbers'] ?? '');
        $message = trim($this->request->post['message'] ?? '');
        $name = trim($this->request->post['name'] ?? 'Untitled Campaign');

        if (empty($message)) {
            $json['error'] = 'Message is required.';

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $this->load->model('extension/kwtsms/module/kwtsms_campaign');

        if ($audience === 'custom' && $customNumbers) {
            $phones = array_filter(array_map('trim', explode(',', $customNumbers)));
        } else {
            $phones = $this->model_extension_kwtsms_module_kwtsms_campaign->getCustomerPhones($audience, $groupId);
        }

        if (empty($phones)) {
            $json['error'] = 'No recipients found.';

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Create campaign record
        $campaignId = $this->model_extension_kwtsms_module_kwtsms_campaign->createCampaign([
            'name'             => $name,
            'audience'         => $audience === 'custom' ? 'custom' : ($audience === 'group' ? 'group_' . $groupId : 'all_customers'),
            'message'          => $message,
            'recipients_count' => count($phones),
        ]);

        // Send via library
        require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
        $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);

        // Join all phones as comma-separated and let the library handle batching
        $phoneString = implode(',', $phones);
        $result = $library->send($phoneString, $message, 'bulk_campaign');

        $sent = $result['sent'] ?? 0;
        $failed = $result['failed'] ?? 0;
        $skipped = $result['skipped'] ?? 0;

        $this->model_extension_kwtsms_module_kwtsms_campaign->updateCampaign($campaignId, [
            'sent_count'    => $sent,
            'failed_count'  => $failed,
            'skipped_count' => $skipped,
            'credits_used'  => $sent,
            'status'        => 'completed',
            'completed_at'  => true,
        ]);

        $json['success'] = true;
        $json['sent'] = $sent;
        $json['failed'] = $failed;
        $json['skipped'] = $skipped;
        $json['credits'] = $sent;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * History: return paginated campaign history.
     * Returns JSON.
     */
    public function history(): void {
        $json = [];

        if (!$this->user->hasPermission('access', 'extension/kwtsms/module/kwtsms_campaign')) {
            $json['error'] = 'Permission denied.';

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $page = (int)($this->request->get['page'] ?? 1);
        $limit = 10;
        $start = ($page - 1) * $limit;

        $this->load->model('extension/kwtsms/module/kwtsms_campaign');
        $rows = $this->model_extension_kwtsms_module_kwtsms_campaign->getCampaigns($start, $limit);
        $total = $this->model_extension_kwtsms_module_kwtsms_campaign->getTotalCampaigns();

        $json['rows'] = $rows;
        $json['total'] = $total;
        $json['pages'] = (int)ceil($total / $limit);
        $json['page'] = $page;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
