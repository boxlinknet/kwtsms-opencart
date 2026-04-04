<?php
namespace Opencart\Admin\Controller\Extension\Kwtsms\Module;

class KwtsmsGateway extends \Opencart\System\Engine\Controller {
    /**
     * Login to the kwtSMS gateway (AJAX, POST).
     * Validates credentials, connects to the gateway, saves them, and returns connection data.
     */
    public function login(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms_gateway')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $username = isset($this->request->post['username']) ? trim((string)$this->request->post['username']) : '';
        $password = isset($this->request->post['password']) ? trim((string)$this->request->post['password']) : '';

        if ($username === '' || $password === '') {
            $json['error'] = $this->language->get('error_login');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');

        $kwtsms = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
        $result = $kwtsms->login($username, $password);

        if (!empty($result['success'])) {
            $this->load->model('setting/setting');
            $existing = $this->model_setting_setting->getSetting('module_kwtsms');
            $existing['module_kwtsms_username'] = $username;
            $existing['module_kwtsms_password'] = $password;
            $this->model_setting_setting->editSetting('module_kwtsms', $existing);

            $json['success']   = $this->language->get('text_login_success');
            $json['balance']   = $result['balance'];
            $json['senderids'] = $result['senderids'];
            $json['coverage']  = $result['coverage'];
        } else {
            $json['error'] = !empty($result['error']) ? $result['error'] : $this->language->get('error_login');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Logout from the kwtSMS gateway (AJAX, POST).
     * Clears cached gateway data and removes stored credentials.
     */
    public function logout(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms_gateway')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');

        $kwtsms = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
        $kwtsms->logout();

        $this->load->model('setting/setting');
        $existing = $this->model_setting_setting->getSetting('module_kwtsms');
        $existing['module_kwtsms_username'] = '';
        $existing['module_kwtsms_password'] = '';
        $this->model_setting_setting->editSetting('module_kwtsms', $existing);

        $json['success'] = $this->language->get('text_logout_success');

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Reload gateway data from stored credentials (AJAX, POST).
     * Fetches fresh balance, sender IDs, and coverage from the API.
     */
    public function reload(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms_gateway')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        if (empty($this->config->get('module_kwtsms_username')) || empty($this->config->get('module_kwtsms_password'))) {
            $json['error'] = $this->language->get('error_not_configured');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');

        $kwtsms = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
        $result = $kwtsms->reload();

        if (!empty($result['success'])) {
            $json['success']   = $this->language->get('text_reload_success');
            $json['balance']   = $result['balance'];
            $json['senderids'] = $result['senderids'];
            $json['coverage']  = $result['coverage'];
        } else {
            $json['error'] = !empty($result['error']) ? $result['error'] : $this->language->get('error_not_configured');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Send a test SMS through the gateway (AJAX, POST).
     * Forces test mode regardless of the extension's current setting.
     */
    public function test(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms_gateway')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $phone   = isset($this->request->post['phone'])   ? trim((string)$this->request->post['phone'])   : '';
        $message = isset($this->request->post['message']) ? trim((string)$this->request->post['message']) : '';

        if ($phone === '') {
            $json['error'] = $this->language->get('error_phone_required');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        if ($message === '') {
            $json['error'] = $this->language->get('error_message_required');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');

        $kwtsms = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
        $result = $kwtsms->testGateway($phone, $message);

        if (!empty($result['success'])) {
            $json['success'] = $this->language->get('text_test_sent');
            $json['result']  = $result['result'] ?? [];
        } else {
            $json['error'] = !empty($result['error']) ? $result['error'] : $this->language->get('error_send_failed');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Save gateway settings (AJAX, POST).
     * Updates sender ID, default country code, and debug logging flag.
     */
    public function savesettings(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms_gateway')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        $senderId    = isset($this->request->post['sender_id'])    ? trim((string)$this->request->post['sender_id'])    : '';
        $countryCode = isset($this->request->post['country_code']) ? (string)$this->request->post['country_code']       : '';
        $debug       = isset($this->request->post['debug'])        ? (int)$this->request->post['debug']                  : 0;

        // Sanitize: country code must be digits only
        $countryCode = preg_replace('/[^0-9]/', '', $countryCode);

        // Sanitize: debug must be 0 or 1
        $debug = $debug ? 1 : 0;

        $this->load->model('setting/setting');
        $existing = $this->model_setting_setting->getSetting('module_kwtsms');
        $existing['module_kwtsms_sender_id']    = $senderId;
        $existing['module_kwtsms_country_code'] = $countryCode;
        $existing['module_kwtsms_debug']        = $debug;
        $this->model_setting_setting->editSetting('module_kwtsms', $existing);

        $json['success'] = $this->language->get('text_success');

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
