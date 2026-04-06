<?php
namespace Opencart\Catalog\Controller\Extension\Kwtsms\Module;

class KwtsmsReturn extends \Opencart\System\Engine\Controller {
    /**
     * Event handler for catalog/model/account/returns/addReturn/after.
     * Sends an admin notification SMS when a return request is submitted.
     */
    public function returnRequested(string &$route, array &$args, mixed &$output): void {
        try {
            // 1. Check if extension is enabled
            if (!$this->config->get('module_kwtsms_status')) {
                return;
            }

            // 2. Check if credentials are configured
            if (!$this->config->get('module_kwtsms_username')) {
                return;
            }

            // 3. Check if admin_return_request event is enabled
            $adminEvents = json_decode((string)$this->config->get('module_kwtsms_admin_events'), true);

            if (!is_array($adminEvents) || !in_array('admin_return_request', $adminEvents)) {
                return;
            }

            // 4. Check admin phones not empty
            $adminPhones = (string)$this->config->get('module_kwtsms_admin_phones');

            if (empty($adminPhones)) {
                return;
            }

            // 5. $output is the new return_id
            $returnId = (int)$output;

            if ($returnId <= 0) {
                return;
            }

            // 6. Load catalog model
            $this->load->model('extension/kwtsms/module/kwtsms');

            // 7. Get return data
            $returnData = $this->model_extension_kwtsms_module_kwtsms->getReturnData($returnId);

            if (empty($returnData)) {
                return;
            }

            // 8. Get template (always English for admin)
            $template = $this->model_extension_kwtsms_module_kwtsms->getTemplate('admin_return_request', 'en');

            if (empty($template)) {
                return;
            }

            // 9. Build placeholder data
            $data = $returnData;
            $data['store_name'] = $this->config->get('config_name');

            // 10. Replace placeholders and send
            $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $data);

            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
            $library->send($adminPhones, $message, 'admin_return_request');
        } catch (\Throwable $e) {
            // Never break the return request flow
        }
    }
}
