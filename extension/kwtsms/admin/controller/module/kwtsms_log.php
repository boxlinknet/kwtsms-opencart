<?php
namespace Opencart\Admin\Controller\Extension\Kwtsms\Module;

class KwtsmsLog extends \Opencart\System\Engine\Controller {
    /**
     * Get SMS log entries with filters and pagination (AJAX, GET).
     * Filters: status, event_type, date_from, date_to
     */
    public function smsLog(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('access', 'extension/kwtsms/module/kwtsms_log')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $filter = [];

        if (!empty($this->request->get['status'])) {
            $filter['status'] = $this->request->get['status'];
        }

        if (!empty($this->request->get['event_type'])) {
            $filter['event_type'] = $this->request->get['event_type'];
        }

        if (!empty($this->request->get['date_from'])) {
            $filter['date_from'] = $this->request->get['date_from'];
        }

        if (!empty($this->request->get['date_to'])) {
            $filter['date_to'] = $this->request->get['date_to'];
        }

        $page  = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $this->load->model('extension/kwtsms/module/kwtsms_log');

        $rows  = $this->model_extension_kwtsms_module_kwtsms_log->getSmsLogs($filter, $start, $limit);
        $total = $this->model_extension_kwtsms_module_kwtsms_log->getTotalSmsLogs($filter);
        $pages = $total > 0 ? (int)ceil($total / $limit) : 1;

        $json['rows']  = $rows;
        $json['total'] = $total;
        $json['pages'] = $pages;
        $json['page']  = $page;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Get debug log entries with filters and pagination (AJAX, GET).
     * Filters: level, context, date_from, date_to
     */
    public function debugLog(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('access', 'extension/kwtsms/module/kwtsms_log')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $filter = [];

        if (!empty($this->request->get['level'])) {
            $filter['level'] = $this->request->get['level'];
        }

        if (!empty($this->request->get['context'])) {
            $filter['context'] = $this->request->get['context'];
        }

        if (!empty($this->request->get['date_from'])) {
            $filter['date_from'] = $this->request->get['date_from'];
        }

        if (!empty($this->request->get['date_to'])) {
            $filter['date_to'] = $this->request->get['date_to'];
        }

        $page  = isset($this->request->get['page']) ? max(1, (int)$this->request->get['page']) : 1;
        $limit = 20;
        $start = ($page - 1) * $limit;

        $this->load->model('extension/kwtsms/module/kwtsms_log');

        $rows  = $this->model_extension_kwtsms_module_kwtsms_log->getDebugLogs($filter, $start, $limit);
        $total = $this->model_extension_kwtsms_module_kwtsms_log->getTotalDebugLogs($filter);
        $pages = $total > 0 ? (int)ceil($total / $limit) : 1;

        $json['rows']  = $rows;
        $json['total'] = $total;
        $json['pages'] = $pages;
        $json['page']  = $page;

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Clear all SMS log entries (AJAX, POST).
     */
    public function clearSmsLog(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms_log')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $this->load->model('extension/kwtsms/module/kwtsms_log');
        $this->model_extension_kwtsms_module_kwtsms_log->clearSmsLogs();

        $json['success'] = 'Log cleared successfully.';

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Clear all debug log entries (AJAX, POST).
     */
    public function clearDebugLog(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms_log')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $this->load->model('extension/kwtsms/module/kwtsms_log');
        $this->model_extension_kwtsms_module_kwtsms_log->clearDebugLogs();

        $json['success'] = 'Log cleared successfully.';

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
