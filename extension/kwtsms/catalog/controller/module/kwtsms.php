<?php
namespace Opencart\Catalog\Controller\Extension\Kwtsms\Module;

class Kwtsms extends \Opencart\System\Engine\Controller {
    /**
     * Event handler for catalog/model/checkout/order/addHistory/after.
     * Sends SMS notifications to customers and admins on order status changes.
     */
    public function orderStatusChange(string &$route, array &$args, mixed &$output): void {
        try {
            // 1. Check if extension is enabled
            if (!$this->config->get('module_kwtsms_status')) {
                return;
            }

            // 2. Check if credentials are configured
            if (!$this->config->get('module_kwtsms_username')) {
                return;
            }

            // 3. Extract order_id and order_status_id
            $orderId = (int)($args[0] ?? 0);
            $orderStatusId = (int)($args[1] ?? 0);

            if ($orderId <= 0 || $orderStatusId <= 0) {
                return;
            }

            // 4. Load catalog model
            $this->load->model('extension/kwtsms/module/kwtsms');

            // 5. Get order data
            $orderData = $this->model_extension_kwtsms_module_kwtsms->getOrderData($orderId);

            if (empty($orderData)) {
                return;
            }

            // 6. Load Composer autoloader and instantiate library
            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);

            // 7. Determine template language from order's language
            $languageCode = $this->model_extension_kwtsms_module_kwtsms->getOrderLanguageCode((int)$orderData['language_id']);
            $lang = str_starts_with($languageCode, 'ar') ? 'ar' : 'en';

            // 8. Customer SMS
            $customerStatuses = json_decode((string)$this->config->get('module_kwtsms_customer_statuses'), true);

            if (is_array($customerStatuses) && in_array($orderStatusId, $customerStatuses) && !empty($orderData['telephone'])) {
                $template = $this->config->get('module_kwtsms_template_customer_order_' . $lang);

                if (empty($template)) {
                    $template = $this->config->get('module_kwtsms_template_customer_order_en');
                }

                if (!empty($template)) {
                    $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $orderData);
                    $library->send($orderData['telephone'], $message, 'order_status_change', $orderId);
                }
            }

            // 9. Admin SMS (paid order)
            $adminPhones = (string)$this->config->get('module_kwtsms_admin_phones');
            $adminPaidStatuses = json_decode((string)$this->config->get('module_kwtsms_admin_paid_statuses'), true);

            if (is_array($adminPaidStatuses) && in_array($orderStatusId, $adminPaidStatuses) && !empty($adminPhones)) {
                $template = $this->config->get('module_kwtsms_template_admin_paid_en');

                if (!empty($template)) {
                    $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $orderData);
                    $library->send($adminPhones, $message, 'admin_new_order', $orderId);
                }
            }

            // 10. Admin SMS (problem order)
            $adminProblemStatuses = json_decode((string)$this->config->get('module_kwtsms_admin_problem_statuses'), true);

            if (is_array($adminProblemStatuses) && in_array($orderStatusId, $adminProblemStatuses) && !empty($adminPhones)) {
                $template = $this->config->get('module_kwtsms_template_admin_problem_en');

                if (!empty($template)) {
                    $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $orderData);
                    $library->send($adminPhones, $message, 'admin_problem_order', $orderId);
                }
            }
        } catch (\Throwable $e) {
            // Log the exception but don't break the order flow
            if (class_exists('\Opencart\System\Library\Extension\Kwtsms\Kwtsms')) {
                try {
                    require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
                    $lib = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
                } catch (\Throwable $ignore) {
                    // Can't even instantiate the library
                }
            }
        }
    }
}
