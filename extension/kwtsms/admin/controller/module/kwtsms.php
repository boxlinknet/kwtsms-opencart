<?php
namespace Opencart\Admin\Controller\Extension\Kwtsms\Module;

class Kwtsms extends \Opencart\System\Engine\Controller {
    /**
     * Main page render. Loads all data for the 6-tab admin UI.
     */
    public function index(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $this->document->setTitle($this->language->get('heading_title'));

        // Breadcrumbs
        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module'),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/kwtsms/module/kwtsms', 'user_token=' . $this->session->data['user_token']),
        ];

        // Load models
        $this->load->model('extension/kwtsms/module/kwtsms');
        $this->load->model('extension/kwtsms/module/kwtsms_gateway');
        $this->load->model('localisation/order_status');

        // Action URLs
        $data['save'] = $this->url->link('extension/kwtsms/module/kwtsms.save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        // AJAX endpoint URLs
        $data['dashboard_url'] = $this->url->link('extension/kwtsms/module/kwtsms.dashboard', 'user_token=' . $this->session->data['user_token']);
        $data['gateway_login_url'] = $this->url->link('extension/kwtsms/module/kwtsms_gateway.login', 'user_token=' . $this->session->data['user_token']);
        $data['gateway_logout_url'] = $this->url->link('extension/kwtsms/module/kwtsms_gateway.logout', 'user_token=' . $this->session->data['user_token']);
        $data['gateway_reload_url'] = $this->url->link('extension/kwtsms/module/kwtsms_gateway.reload', 'user_token=' . $this->session->data['user_token']);
        $data['gateway_test_url'] = $this->url->link('extension/kwtsms/module/kwtsms_gateway.test', 'user_token=' . $this->session->data['user_token']);
        $data['gateway_save_url'] = $this->url->link('extension/kwtsms/module/kwtsms_gateway.save', 'user_token=' . $this->session->data['user_token']);
        $data['sms_log_url'] = $this->url->link('extension/kwtsms/module/kwtsms_log.smsLog', 'user_token=' . $this->session->data['user_token']);
        $data['debug_log_url'] = $this->url->link('extension/kwtsms/module/kwtsms_log.debugLog', 'user_token=' . $this->session->data['user_token']);
        $data['clear_sms_log_url'] = $this->url->link('extension/kwtsms/module/kwtsms_log.clearSmsLog', 'user_token=' . $this->session->data['user_token']);
        $data['clear_debug_log_url'] = $this->url->link('extension/kwtsms/module/kwtsms_log.clearDebugLog', 'user_token=' . $this->session->data['user_token']);

        // User token for JS URL building
        $data['user_token'] = $this->session->data['user_token'];

        // Dashboard stats
        $data['stats_today']  = $this->model_extension_kwtsms_module_kwtsms->getSmsStats('today');
        $data['stats_7days']  = $this->model_extension_kwtsms_module_kwtsms->getSmsStats('7days');
        $data['stats_30days'] = $this->model_extension_kwtsms_module_kwtsms->getSmsStats('30days');

        // Settings: current config values
        $data['module_kwtsms_status']                  = $this->config->get('module_kwtsms_status');
        $data['module_kwtsms_test_mode']               = $this->config->get('module_kwtsms_test_mode');
        $data['module_kwtsms_admin_phones']            = $this->config->get('module_kwtsms_admin_phones');
        $data['module_kwtsms_country_code']            = $this->config->get('module_kwtsms_country_code');
        $data['module_kwtsms_sender_id']               = $this->config->get('module_kwtsms_sender_id');
        $data['module_kwtsms_debug']                   = $this->config->get('module_kwtsms_debug');

        // Order status toggles (stored as JSON arrays)
        $customerStatuses = $this->config->get('module_kwtsms_customer_statuses');
        $data['module_kwtsms_customer_statuses'] = !empty($customerStatuses) ? json_decode($customerStatuses, true) : [];
        if (!is_array($data['module_kwtsms_customer_statuses'])) {
            $data['module_kwtsms_customer_statuses'] = [];
        }

        $adminPaidStatuses = $this->config->get('module_kwtsms_admin_paid_statuses');
        $data['module_kwtsms_admin_paid_statuses'] = !empty($adminPaidStatuses) ? json_decode($adminPaidStatuses, true) : [];
        if (!is_array($data['module_kwtsms_admin_paid_statuses'])) {
            $data['module_kwtsms_admin_paid_statuses'] = [];
        }

        $adminProblemStatuses = $this->config->get('module_kwtsms_admin_problem_statuses');
        $data['module_kwtsms_admin_problem_statuses'] = !empty($adminProblemStatuses) ? json_decode($adminProblemStatuses, true) : [];
        if (!is_array($data['module_kwtsms_admin_problem_statuses'])) {
            $data['module_kwtsms_admin_problem_statuses'] = [];
        }

        // Gateway: cached data
        $data['gateway_balance']    = $this->model_extension_kwtsms_module_kwtsms_gateway->getCache('balance');
        $data['gateway_senderids']  = $this->model_extension_kwtsms_module_kwtsms_gateway->getCache('senderids');
        $data['gateway_coverage']   = $this->model_extension_kwtsms_module_kwtsms_gateway->getCache('coverage');
        $data['gateway_configured'] = !empty($this->config->get('module_kwtsms_username')) && !empty($this->config->get('module_kwtsms_password'));

        // Templates: current template values
        $data['module_kwtsms_template_customer_order_en'] = $this->config->get('module_kwtsms_template_customer_order_en');
        $data['module_kwtsms_template_customer_order_ar'] = $this->config->get('module_kwtsms_template_customer_order_ar');
        $data['module_kwtsms_template_admin_paid_en']     = $this->config->get('module_kwtsms_template_admin_paid_en');
        $data['module_kwtsms_template_admin_problem_en']  = $this->config->get('module_kwtsms_template_admin_problem_en');

        // Order statuses: full list from localisation model
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        // Load layout components
        $data['header']      = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer']      = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/kwtsms/module/kwtsms', $data));
    }

    /**
     * Called by OpenCart when the extension is installed.
     * Creates DB tables, registers events, adds permissions, sets default settings.
     */
    public function install(): void {
        // 1. Create database tables
        $this->load->model('extension/kwtsms/module/kwtsms');
        $this->model_extension_kwtsms_module_kwtsms->install();

        // 2. Register event for order status change
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent([
            'code'        => 'kwtsms_order_status',
            'description' => 'kwtSMS: Send SMS on order status change',
            'trigger'     => 'catalog/model/checkout/order/addHistory/after',
            'action'      => 'extension/kwtsms/module/kwtsms.orderStatusChange',
            'status'      => 1,
            'sort_order'  => 0,
        ]);

        // 3. Add permissions for sub-controllers
        $this->load->model('user/user_group');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_gateway');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_gateway');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_log');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_log');

        // 4. Set default settings
        $defaults = [
            'module_kwtsms_status'                         => 0,
            'module_kwtsms_test_mode'                      => 1,
            'module_kwtsms_country_code'                   => '965',
            'module_kwtsms_sender_id'                      => 'KWT-SMS',
            'module_kwtsms_debug'                          => 0,
            'module_kwtsms_admin_phones'                   => '',
            'module_kwtsms_customer_statuses'              => '[]',
            'module_kwtsms_admin_paid_statuses'            => '[]',
            'module_kwtsms_admin_problem_statuses'         => '[]',
            'module_kwtsms_template_customer_order_en'     => 'Hi {customer_name}, your order #{order_id} status has been updated to: {order_status}. Thank you for shopping at {store_name}.',
            'module_kwtsms_template_customer_order_ar'     => '{customer_name} مرحبا، تم تحديث حالة طلبك رقم #{order_id} الى: {order_status}. شكرا لتسوقك في {store_name}.',
            'module_kwtsms_template_admin_paid_en'         => 'New paid order #{order_id} from {customer_name}. Total: {order_total}. Date: {date}.',
            'module_kwtsms_template_admin_problem_en'      => 'Order #{order_id} status changed to {order_status}. Customer: {customer_name}. Total: {order_total}.',
        ];

        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('module_kwtsms', $defaults);
    }

    /**
     * Called by OpenCart when the extension is uninstalled.
     * Drops DB tables, removes events and settings.
     */
    public function uninstall(): void {
        // 1. Drop database tables
        $this->load->model('extension/kwtsms/module/kwtsms');
        $this->model_extension_kwtsms_module_kwtsms->uninstall();

        // 2. Delete event
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('kwtsms_order_status');

        // 3. Delete settings
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_kwtsms');
    }

    /**
     * Save settings (AJAX, returns JSON).
     * Only saves settings managed by the Settings/Templates tabs.
     * Does NOT overwrite gateway-managed settings (username, password, sender_id, country_code, debug).
     */
    public function save(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        // Permission check
        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            // Read existing settings to preserve gateway-managed values
            $existingSettings = $this->model_setting_setting->getSetting('module_kwtsms');

            // Build settings array from POST data (Settings tab)
            $settings = [];

            // Status and test mode
            $settings['module_kwtsms_status']    = isset($this->request->post['module_kwtsms_status']) ? (int)$this->request->post['module_kwtsms_status'] : 0;
            $settings['module_kwtsms_test_mode'] = isset($this->request->post['module_kwtsms_test_mode']) ? (int)$this->request->post['module_kwtsms_test_mode'] : 0;

            // Admin phones (sanitize: strip non-digits and commas)
            $adminPhones = isset($this->request->post['module_kwtsms_admin_phones']) ? $this->request->post['module_kwtsms_admin_phones'] : '';
            $settings['module_kwtsms_admin_phones'] = preg_replace('/[^0-9,]/', '', (string)$adminPhones);

            // Order status toggles (JSON encode arrays of selected status IDs)
            $customerStatuses = isset($this->request->post['module_kwtsms_customer_statuses']) ? $this->request->post['module_kwtsms_customer_statuses'] : [];
            if (is_array($customerStatuses)) {
                $settings['module_kwtsms_customer_statuses'] = json_encode(array_map('intval', $customerStatuses));
            } else {
                $settings['module_kwtsms_customer_statuses'] = '[]';
            }

            $adminPaidStatuses = isset($this->request->post['module_kwtsms_admin_paid_statuses']) ? $this->request->post['module_kwtsms_admin_paid_statuses'] : [];
            if (is_array($adminPaidStatuses)) {
                $settings['module_kwtsms_admin_paid_statuses'] = json_encode(array_map('intval', $adminPaidStatuses));
            } else {
                $settings['module_kwtsms_admin_paid_statuses'] = '[]';
            }

            $adminProblemStatuses = isset($this->request->post['module_kwtsms_admin_problem_statuses']) ? $this->request->post['module_kwtsms_admin_problem_statuses'] : [];
            if (is_array($adminProblemStatuses)) {
                $settings['module_kwtsms_admin_problem_statuses'] = json_encode(array_map('intval', $adminProblemStatuses));
            } else {
                $settings['module_kwtsms_admin_problem_statuses'] = '[]';
            }

            // Template fields (sanitize with htmlspecialchars to prevent XSS, preserve placeholders)
            $settings['module_kwtsms_template_customer_order_en'] = isset($this->request->post['module_kwtsms_template_customer_order_en'])
                ? htmlspecialchars((string)$this->request->post['module_kwtsms_template_customer_order_en'], ENT_QUOTES, 'UTF-8')
                : '';
            $settings['module_kwtsms_template_customer_order_ar'] = isset($this->request->post['module_kwtsms_template_customer_order_ar'])
                ? htmlspecialchars((string)$this->request->post['module_kwtsms_template_customer_order_ar'], ENT_QUOTES, 'UTF-8')
                : '';
            $settings['module_kwtsms_template_admin_paid_en'] = isset($this->request->post['module_kwtsms_template_admin_paid_en'])
                ? htmlspecialchars((string)$this->request->post['module_kwtsms_template_admin_paid_en'], ENT_QUOTES, 'UTF-8')
                : '';
            $settings['module_kwtsms_template_admin_problem_en'] = isset($this->request->post['module_kwtsms_template_admin_problem_en'])
                ? htmlspecialchars((string)$this->request->post['module_kwtsms_template_admin_problem_en'], ENT_QUOTES, 'UTF-8')
                : '';

            // Preserve gateway-managed settings that should not be overwritten
            $gatewayKeys = [
                'module_kwtsms_username',
                'module_kwtsms_password',
                'module_kwtsms_sender_id',
                'module_kwtsms_country_code',
                'module_kwtsms_debug',
            ];

            foreach ($gatewayKeys as $key) {
                if (isset($existingSettings[$key])) {
                    $settings[$key] = $existingSettings[$key];
                }
            }

            $this->model_setting_setting->editSetting('module_kwtsms', $settings);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * Dashboard data endpoint (AJAX, returns JSON).
     * Returns SMS stats, gateway info, and current settings for the dashboard tab.
     */
    public function dashboard(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');

        $json = [];

        // Permission check
        if (!$this->user->hasPermission('access', 'extension/kwtsms/module/kwtsms')) {
            $json['error'] = $this->language->get('error_permission');

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
            return;
        }

        // Load models
        $this->load->model('extension/kwtsms/module/kwtsms');
        $this->load->model('extension/kwtsms/module/kwtsms_gateway');

        // SMS stats
        $json['stats'] = [
            'today'  => $this->model_extension_kwtsms_module_kwtsms->getSmsStats('today'),
            '7days'  => $this->model_extension_kwtsms_module_kwtsms->getSmsStats('7days'),
            '30days' => $this->model_extension_kwtsms_module_kwtsms->getSmsStats('30days'),
        ];

        // Gateway cached data
        $json['gateway'] = [
            'balance'    => $this->model_extension_kwtsms_module_kwtsms_gateway->getCache('balance'),
            'senderids'  => $this->model_extension_kwtsms_module_kwtsms_gateway->getCache('senderids'),
            'configured' => !empty($this->config->get('module_kwtsms_username')) && !empty($this->config->get('module_kwtsms_password')),
        ];

        // Current settings
        $json['settings'] = [
            'status'    => (int)$this->config->get('module_kwtsms_status'),
            'test_mode' => (int)$this->config->get('module_kwtsms_test_mode'),
            'debug'     => (int)$this->config->get('module_kwtsms_debug'),
            'sender_id' => $this->config->get('module_kwtsms_sender_id'),
        ];

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
