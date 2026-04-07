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
        $data['gateway_save_url'] = $this->url->link('extension/kwtsms/module/kwtsms_gateway.savesettings', 'user_token=' . $this->session->data['user_token']);
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

        // Templates from DB
        $data['templates'] = $this->model_extension_kwtsms_module_kwtsms->getTemplates();

        // Phase 2.1 settings
        $data['module_kwtsms_low_stock_threshold'] = $this->config->get('module_kwtsms_low_stock_threshold') ?: 5;

        $customerEventsJson = $this->config->get('module_kwtsms_customer_events');
        $data['module_kwtsms_customer_events'] = !empty($customerEventsJson) ? json_decode($customerEventsJson, true) : [];
        if (!is_array($data['module_kwtsms_customer_events'])) {
            $data['module_kwtsms_customer_events'] = [];
        }

        $adminEventsJson = $this->config->get('module_kwtsms_admin_events');
        $data['module_kwtsms_admin_events'] = !empty($adminEventsJson) ? json_decode($adminEventsJson, true) : [];
        if (!is_array($data['module_kwtsms_admin_events'])) {
            $data['module_kwtsms_admin_events'] = [];
        }

        // Abandoned cart settings
        $data['module_kwtsms_abandoned_cart_enabled'] = $this->config->get('module_kwtsms_abandoned_cart_enabled');
        $data['module_kwtsms_abandoned_cart_delay'] = $this->config->get('module_kwtsms_abandoned_cart_delay') ?: 60;
        $data['module_kwtsms_abandoned_cart_max_per_run'] = $this->config->get('module_kwtsms_abandoned_cart_max_per_run') ?: 50;

        // OTP settings
        $data['module_kwtsms_cod_otp_enabled'] = $this->config->get('module_kwtsms_cod_otp_enabled');
        $data['module_kwtsms_otp_length'] = $this->config->get('module_kwtsms_otp_length') ?: 6;
        $data['module_kwtsms_otp_expiry'] = $this->config->get('module_kwtsms_otp_expiry') ?: 5;
        $data['module_kwtsms_otp_max_per_phone'] = $this->config->get('module_kwtsms_otp_max_per_phone') ?: 5;
        $data['module_kwtsms_otp_max_per_ip'] = $this->config->get('module_kwtsms_otp_max_per_ip') ?: 10;
        $data['module_kwtsms_otp_resend_cooldown'] = $this->config->get('module_kwtsms_otp_resend_cooldown') ?: 60;

        // Reset template URL
        $data['reset_template_url'] = $this->url->link('extension/kwtsms/module/kwtsms.resetTemplate', 'user_token=' . $this->session->data['user_token']);

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

        // 2. Register events
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent([
            'code'        => 'kwtsms_order_status',
            'description' => 'kwtSMS: Send SMS on order status change',
            'trigger'     => 'catalog/model/checkout/order/addHistory/after',
            'action'      => 'extension/kwtsms/module/kwtsms.orderStatusChange',
            'status'      => 1,
            'sort_order'  => 0,
        ]);

        $this->model_setting_event->addEvent([
            'code'        => 'kwtsms_customer_register',
            'description' => 'kwtSMS: Send welcome SMS on customer registration',
            'trigger'     => 'catalog/model/account/customer/addCustomer/after',
            'action'      => 'extension/kwtsms/module/kwtsms_customer.customerRegistered',
            'status'      => 1,
            'sort_order'  => 0,
        ]);

        $this->model_setting_event->addEvent([
            'code'        => 'kwtsms_admin_new_customer',
            'description' => 'kwtSMS: Admin alert on new customer registration',
            'trigger'     => 'catalog/model/account/customer/addCustomer/after',
            'action'      => 'extension/kwtsms/module/kwtsms_customer.adminNewCustomer',
            'status'      => 1,
            'sort_order'  => 1,
        ]);

        $this->model_setting_event->addEvent([
            'code'        => 'kwtsms_low_stock',
            'description' => 'kwtSMS: Check low stock after order',
            'trigger'     => 'catalog/model/checkout/order/addHistory/after',
            'action'      => 'extension/kwtsms/module/kwtsms_product.checkLowStock',
            'status'      => 1,
            'sort_order'  => 1,
        ]);

        $this->model_setting_event->addEvent([
            'code'        => 'kwtsms_new_review',
            'description' => 'kwtSMS: Admin alert on new product review',
            'trigger'     => 'catalog/model/catalog/review/addReview/after',
            'action'      => 'extension/kwtsms/module/kwtsms_product.reviewSubmitted',
            'status'      => 1,
            'sort_order'  => 0,
        ]);

        $this->model_setting_event->addEvent([
            'code'        => 'kwtsms_return_request',
            'description' => 'kwtSMS: Admin alert on return request',
            'trigger'     => 'catalog/model/account/returns/addReturn/after',
            'action'      => 'extension/kwtsms/module/kwtsms_return.returnRequested',
            'status'      => 1,
            'sort_order'  => 0,
        ]);

        $this->model_setting_event->addEvent([
            'code'        => 'kwtsms_cod_otp',
            'description' => 'kwtSMS: Inject COD OTP verification into checkout',
            'trigger'     => 'catalog/view/checkout/checkout/after',
            'action'      => 'extension/kwtsms/module/kwtsms_otp.injectCheckout',
            'status'      => 1,
            'sort_order'  => 0,
        ]);

        // 3. Add permissions for sub-controllers
        $this->load->model('user/user_group');

        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_gateway');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_gateway');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_log');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_log');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_customer');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_customer');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_product');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_product');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_return');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_return');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_otp');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_otp');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/kwtsms/module/kwtsms_cart');
        $this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/kwtsms/module/kwtsms_cart');

        // 4. Register cron tasks
        $this->load->model('setting/cron');
        $this->model_setting_cron->addCron('kwtsms_sync', 'kwtSMS: Daily sync of balance, sender IDs, and coverage', 'day', 'extension/kwtsms/module/kwtsms.cron', true);
        $this->model_setting_cron->addCron('kwtsms_abandoned_cart', 'kwtSMS: Abandoned cart SMS reminders', 'hour', 'extension/kwtsms/module/kwtsms_cart.cron', true);

        // 5. Seed SMS templates into DB
        $this->model_extension_kwtsms_module_kwtsms->seedTemplates();
        $this->model_extension_kwtsms_module_kwtsms->seedPerStatusTemplates();

        // Seed OTP verification template
        $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "kwtsms_templates` SET
            `event_type` = 'otp_verification',
            `name` = 'OTP Verification Code',
            `body_en` = 'Your verification code for {store_name} is: {otp_code}. Valid for {expiry_minutes} minutes.',
            `body_ar` = 'رمز التحقق الخاص بك في {store_name} هو: {otp_code}. صالح لمدة {expiry_minutes} دقائق.',
            `default_en` = 'Your verification code for {store_name} is: {otp_code}. Valid for {expiry_minutes} minutes.',
            `default_ar` = 'رمز التحقق الخاص بك في {store_name} هو: {otp_code}. صالح لمدة {expiry_minutes} دقائق.',
            `placeholders` = '{otp_code}, {expiry_minutes}, {store_name}',
            `sort_order` = 400");

        // Seed abandoned cart reminder template
        $this->db->query("INSERT IGNORE INTO `" . DB_PREFIX . "kwtsms_templates` SET
            `event_type` = 'abandoned_cart',
            `name` = 'Abandoned Cart Reminder',
            `body_en` = 'Hi {customer_name}, you left items in your cart at {store_name}. Complete your order now! Cart total: {order_total}.',
            `body_ar` = '{customer_name} مرحبا، لديك منتجات في سلة التسوق في {store_name}. أكمل طلبك الان! المجموع: {order_total}.',
            `default_en` = 'Hi {customer_name}, you left items in your cart at {store_name}. Complete your order now! Cart total: {order_total}.',
            `default_ar` = '{customer_name} مرحبا، لديك منتجات في سلة التسوق في {store_name}. أكمل طلبك الان! المجموع: {order_total}.',
            `placeholders` = '{customer_name}, {store_name}, {order_total}, {products_summary}, {date}',
            `sort_order` = 500");

        // 6. Set default settings
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
            'module_kwtsms_low_stock_threshold'             => 5,
            'module_kwtsms_customer_events'                 => '["customer_registered"]',
            'module_kwtsms_admin_events'                    => '["admin_new_customer","low_stock","admin_new_review","admin_return_request"]',
            'module_kwtsms_cod_otp_enabled'                 => 0,
            'module_kwtsms_otp_length'                      => 6,
            'module_kwtsms_otp_expiry'                      => 5,
            'module_kwtsms_otp_max_per_phone'               => 5,
            'module_kwtsms_otp_max_per_ip'                  => 10,
            'module_kwtsms_otp_resend_cooldown'             => 60,
            'module_kwtsms_abandoned_cart_enabled'          => 0,
            'module_kwtsms_abandoned_cart_delay'            => 60,
            'module_kwtsms_abandoned_cart_max_per_run'      => 50,
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

        // 2. Delete events
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('kwtsms_order_status');
        $this->model_setting_event->deleteEventByCode('kwtsms_customer_register');
        $this->model_setting_event->deleteEventByCode('kwtsms_admin_new_customer');
        $this->model_setting_event->deleteEventByCode('kwtsms_low_stock');
        $this->model_setting_event->deleteEventByCode('kwtsms_new_review');
        $this->model_setting_event->deleteEventByCode('kwtsms_return_request');
        $this->model_setting_event->deleteEventByCode('kwtsms_cod_otp');

        // 3. Remove cron tasks
        $this->load->model('setting/cron');
        $this->model_setting_cron->deleteCronByCode('kwtsms_sync');
        $this->model_setting_cron->deleteCronByCode('kwtsms_abandoned_cart');

        // 4. Delete settings
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('module_kwtsms');
    }

    /**
     * Daily cron task: sync balance, sender IDs, and coverage from kwtSMS API.
     * Called by OpenCart's cron system.
     */
    public function cron(): void {
        if (empty($this->config->get('module_kwtsms_username')) || empty($this->config->get('module_kwtsms_password'))) {
            return;
        }

        require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
        $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
        $library->reload();
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

            // Low stock threshold
            $settings['module_kwtsms_low_stock_threshold'] = isset($this->request->post['module_kwtsms_low_stock_threshold'])
                ? max(0, (int)$this->request->post['module_kwtsms_low_stock_threshold']) : 5;

            // Customer events
            $customerEvents = isset($this->request->post['module_kwtsms_customer_events'])
                ? $this->request->post['module_kwtsms_customer_events'] : [];
            $settings['module_kwtsms_customer_events'] = json_encode(is_array($customerEvents) ? $customerEvents : []);

            // Admin events
            $adminEvents = isset($this->request->post['module_kwtsms_admin_events'])
                ? $this->request->post['module_kwtsms_admin_events'] : [];
            $settings['module_kwtsms_admin_events'] = json_encode(is_array($adminEvents) ? $adminEvents : []);

            // Abandoned cart settings
            $settings['module_kwtsms_abandoned_cart_enabled'] = isset($this->request->post['module_kwtsms_abandoned_cart_enabled']) ? (int)$this->request->post['module_kwtsms_abandoned_cart_enabled'] : 0;
            $settings['module_kwtsms_abandoned_cart_delay'] = isset($this->request->post['module_kwtsms_abandoned_cart_delay']) ? max(15, min(1440, (int)$this->request->post['module_kwtsms_abandoned_cart_delay'])) : 60;
            $settings['module_kwtsms_abandoned_cart_max_per_run'] = isset($this->request->post['module_kwtsms_abandoned_cart_max_per_run']) ? max(1, min(200, (int)$this->request->post['module_kwtsms_abandoned_cart_max_per_run'])) : 50;

            // OTP settings
            $settings['module_kwtsms_cod_otp_enabled'] = isset($this->request->post['module_kwtsms_cod_otp_enabled']) ? (int)$this->request->post['module_kwtsms_cod_otp_enabled'] : 0;
            $settings['module_kwtsms_otp_length'] = isset($this->request->post['module_kwtsms_otp_length']) ? max(4, min(8, (int)$this->request->post['module_kwtsms_otp_length'])) : 6;
            $settings['module_kwtsms_otp_expiry'] = isset($this->request->post['module_kwtsms_otp_expiry']) ? max(1, (int)$this->request->post['module_kwtsms_otp_expiry']) : 5;
            $settings['module_kwtsms_otp_max_per_phone'] = isset($this->request->post['module_kwtsms_otp_max_per_phone']) ? max(1, (int)$this->request->post['module_kwtsms_otp_max_per_phone']) : 5;
            $settings['module_kwtsms_otp_max_per_ip'] = isset($this->request->post['module_kwtsms_otp_max_per_ip']) ? max(1, (int)$this->request->post['module_kwtsms_otp_max_per_ip']) : 10;
            $settings['module_kwtsms_otp_resend_cooldown'] = isset($this->request->post['module_kwtsms_otp_resend_cooldown']) ? max(30, (int)$this->request->post['module_kwtsms_otp_resend_cooldown']) : 60;

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

            // Save templates from POST
            if (isset($this->request->post['templates']) && is_array($this->request->post['templates'])) {
                $this->load->model('extension/kwtsms/module/kwtsms');
                foreach ($this->request->post['templates'] as $id => $tpl) {
                    $bodyEn = isset($tpl['body_en']) ? htmlspecialchars((string)$tpl['body_en'], ENT_QUOTES, 'UTF-8') : '';
                    $bodyAr = isset($tpl['body_ar']) ? htmlspecialchars((string)$tpl['body_ar'], ENT_QUOTES, 'UTF-8') : '';
                    $this->model_extension_kwtsms_module_kwtsms->updateTemplate((int)$id, $bodyEn, $bodyAr);
                }
            }

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

    /**
     * Reset a single SMS template to its default body (AJAX, returns JSON).
     */
    public function resetTemplate(): void {
        $this->load->language('extension/kwtsms/module/kwtsms');
        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/kwtsms/module/kwtsms')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $templateId = isset($this->request->post['template_id']) ? (int)$this->request->post['template_id'] : 0;

            if ($templateId > 0) {
                $this->load->model('extension/kwtsms/module/kwtsms');
                $this->model_extension_kwtsms_module_kwtsms->resetTemplate($templateId);

                $json['success'] = $this->language->get('text_reset_success');

                // Return the reset template body so JS can update the textareas
                $templates = $this->model_extension_kwtsms_module_kwtsms->getTemplates();
                foreach ($templates as $t) {
                    if ((int)$t['id'] === $templateId) {
                        $json['body_en'] = $t['body_en'];
                        $json['body_ar'] = $t['body_ar'];
                        break;
                    }
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
