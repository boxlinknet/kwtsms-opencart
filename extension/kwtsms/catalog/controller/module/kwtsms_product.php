<?php
namespace Opencart\Catalog\Controller\Extension\Kwtsms\Module;

class KwtsmsProduct extends \Opencart\System\Engine\Controller {
    /**
     * Event handler for catalog/model/checkout/order/addHistory/after.
     * Checks products in the order for low stock and alerts admins.
     */
    public function checkLowStock(string &$route, array &$args, mixed &$output): void {
        try {
            // 1. Check if extension is enabled
            if (!$this->config->get('module_kwtsms_status')) {
                return;
            }

            // 2. Check if credentials are configured
            if (!$this->config->get('module_kwtsms_username')) {
                return;
            }

            // 3. Check if low_stock event is enabled
            $adminEvents = json_decode((string)$this->config->get('module_kwtsms_admin_events'), true);

            if (!is_array($adminEvents) || !in_array('low_stock', $adminEvents)) {
                return;
            }

            // 4. Check admin phones not empty
            $adminPhones = (string)$this->config->get('module_kwtsms_admin_phones');

            if (empty($adminPhones)) {
                return;
            }

            // 5. Extract order_id from args
            $orderId = (int)($args[0] ?? 0);

            if ($orderId <= 0) {
                return;
            }

            // 6. Load catalog model
            $this->load->model('extension/kwtsms/module/kwtsms');

            // 7. Get threshold
            $threshold = (int)$this->config->get('module_kwtsms_low_stock_threshold');

            if ($threshold <= 0) {
                $threshold = 5;
            }

            // 8. Get all products in the order
            $products = $this->model_extension_kwtsms_module_kwtsms->getOrderProducts($orderId);

            if (empty($products)) {
                return;
            }

            // 9. Load library once
            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);

            // 10. Check each product for low stock
            foreach ($products as $product) {
                $productId = (int)$product['product_id'];
                $currentQty = $this->model_extension_kwtsms_module_kwtsms->getProductQuantity($productId);

                if ($currentQty > $threshold) {
                    continue;
                }

                if ($this->model_extension_kwtsms_module_kwtsms->hasActiveLowStockAlert($productId)) {
                    continue;
                }

                // 11. Get template
                $template = $this->model_extension_kwtsms_module_kwtsms->getTemplate('low_stock', 'en');

                if (empty($template)) {
                    continue;
                }

                // 12. Build placeholder data
                $data = [
                    'product_name'     => $product['name'],
                    'product_model'    => $product['model'],
                    'product_quantity' => $currentQty,
                    'stock_threshold'  => $threshold,
                    'store_name'       => $this->config->get('config_name'),
                ];

                // 13. Replace placeholders and send
                $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $data);
                $library->send($adminPhones, $message, 'low_stock');

                // 14. Mark alert so we don't re-alert
                $this->model_extension_kwtsms_module_kwtsms->setLowStockAlert($productId);
            }
        } catch (\Throwable $e) {
            // Never break the order flow
        }
    }

    /**
     * Event handler for catalog/model/catalog/review/addReview/after.
     * Sends an admin notification SMS when a new review is submitted.
     */
    public function reviewSubmitted(string &$route, array &$args, mixed &$output): void {
        try {
            // 1. Check if extension is enabled
            if (!$this->config->get('module_kwtsms_status')) {
                return;
            }

            // 2. Check if credentials are configured
            if (!$this->config->get('module_kwtsms_username')) {
                return;
            }

            // 3. Check if admin_new_review event is enabled
            $adminEvents = json_decode((string)$this->config->get('module_kwtsms_admin_events'), true);

            if (!is_array($adminEvents) || !in_array('admin_new_review', $adminEvents)) {
                return;
            }

            // 4. Check admin phones not empty
            $adminPhones = (string)$this->config->get('module_kwtsms_admin_phones');

            if (empty($adminPhones)) {
                return;
            }

            // 5. $output is the new review_id
            $reviewId = (int)$output;

            if ($reviewId <= 0) {
                return;
            }

            // 6. Load catalog model
            $this->load->model('extension/kwtsms/module/kwtsms');

            // 7. Get review data
            $reviewData = $this->model_extension_kwtsms_module_kwtsms->getReviewData($reviewId);

            if (empty($reviewData)) {
                return;
            }

            // 8. Get template (always English for admin)
            $template = $this->model_extension_kwtsms_module_kwtsms->getTemplate('admin_new_review', 'en');

            if (empty($template)) {
                return;
            }

            // 9. Build placeholder data
            $data = $reviewData;
            $data['store_name'] = $this->config->get('config_name');

            // 10. Replace placeholders and send
            $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $data);

            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
            $library->send($adminPhones, $message, 'admin_new_review');
        } catch (\Throwable $e) {
            // Never break the review submission flow
        }
    }
}
