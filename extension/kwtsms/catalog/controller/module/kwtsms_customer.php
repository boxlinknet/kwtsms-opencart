<?php
namespace Opencart\Catalog\Controller\Extension\Kwtsms\Module;

class KwtsmsCustomer extends \Opencart\System\Engine\Controller {
    /**
     * Event handler for catalog/model/account/customer/addCustomer/after.
     * Sends a welcome SMS to the newly registered customer.
     */
    public function customerRegistered(string &$route, array &$args, mixed &$output): void {
        try {
            // 1. Check if extension is enabled
            if (!$this->config->get('module_kwtsms_status')) {
                return;
            }

            // 2. Check if credentials are configured
            if (!$this->config->get('module_kwtsms_username')) {
                return;
            }

            // 3. Check if customer_registered event is enabled
            $customerEvents = json_decode((string)$this->config->get('module_kwtsms_customer_events'), true);

            if (!is_array($customerEvents) || !in_array('customer_registered', $customerEvents)) {
                return;
            }

            // 4. $output is the new customer_id
            $customerId = (int)$output;

            if ($customerId <= 0) {
                return;
            }

            // 5. Load catalog model
            $this->load->model('extension/kwtsms/module/kwtsms');

            // 6. Get customer data
            $customerData = $this->model_extension_kwtsms_module_kwtsms->getCustomerData($customerId);

            if (empty($customerData) || empty($customerData['telephone'])) {
                return;
            }

            // 7. Determine template language from customer's language_id
            $languageCode = $this->model_extension_kwtsms_module_kwtsms->getOrderLanguageCode((int)$customerData['language_id']);
            $lang = str_starts_with($languageCode, 'ar') ? 'ar' : 'en';

            // 8. Get template
            $template = $this->model_extension_kwtsms_module_kwtsms->getTemplate('customer_registered', $lang);

            if (empty($template)) {
                return;
            }

            // 9. Build placeholder data
            $data = $customerData;
            $data['store_name'] = $this->config->get('config_name');

            // 10. Replace placeholders and send
            $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $data);

            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
            $library->send($customerData['telephone'], $message, 'customer_registered');
        } catch (\Throwable $e) {
            // Never break the customer registration flow
        }
    }

    /**
     * Event handler for catalog/model/account/customer/addCustomer/after.
     * Sends an admin notification SMS when a new customer registers.
     */
    public function adminNewCustomer(string &$route, array &$args, mixed &$output): void {
        try {
            // 1. Check if extension is enabled
            if (!$this->config->get('module_kwtsms_status')) {
                return;
            }

            // 2. Check if credentials are configured
            if (!$this->config->get('module_kwtsms_username')) {
                return;
            }

            // 3. Check if admin_new_customer event is enabled
            $adminEvents = json_decode((string)$this->config->get('module_kwtsms_admin_events'), true);

            if (!is_array($adminEvents) || !in_array('admin_new_customer', $adminEvents)) {
                return;
            }

            // 4. Check admin phones not empty
            $adminPhones = (string)$this->config->get('module_kwtsms_admin_phones');

            if (empty($adminPhones)) {
                return;
            }

            // 5. $output is the new customer_id
            $customerId = (int)$output;

            if ($customerId <= 0) {
                return;
            }

            // 6. Load catalog model
            $this->load->model('extension/kwtsms/module/kwtsms');

            // 7. Get customer data
            $customerData = $this->model_extension_kwtsms_module_kwtsms->getCustomerData($customerId);

            if (empty($customerData)) {
                return;
            }

            // 8. Get template (always English for admin)
            $template = $this->model_extension_kwtsms_module_kwtsms->getTemplate('admin_new_customer', 'en');

            if (empty($template)) {
                return;
            }

            // 9. Build placeholder data
            $data = $customerData;
            $data['store_name'] = $this->config->get('config_name');

            // 10. Replace placeholders and send
            $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $data);

            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
            $library->send($adminPhones, $message, 'admin_new_customer');
        } catch (\Throwable $e) {
            // Never break the customer registration flow
        }
    }
}
