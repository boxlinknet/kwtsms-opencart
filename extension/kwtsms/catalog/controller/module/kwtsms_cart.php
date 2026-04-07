<?php
namespace Opencart\Catalog\Controller\Extension\Kwtsms\Module;

class KwtsmsCart extends \Opencart\System\Engine\Controller {
    /**
     * Cron handler: detect and send abandoned cart SMS reminders.
     */
    public function cron(): void {
        try {
            if (!$this->config->get('module_kwtsms_status') || !$this->config->get('module_kwtsms_username') || !$this->config->get('module_kwtsms_abandoned_cart_enabled')) {
                return;
            }

            $delay = (int)($this->config->get('module_kwtsms_abandoned_cart_delay') ?: 60);
            $maxPerRun = (int)($this->config->get('module_kwtsms_abandoned_cart_max_per_run') ?: 50);

            $this->load->model('extension/kwtsms/module/kwtsms');
            $abandonedCarts = $this->model_extension_kwtsms_module_kwtsms->getAbandonedCarts($delay);

            if (empty($abandonedCarts)) {
                return;
            }

            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
            $sentCount = 0;

            foreach ($abandonedCarts as $cart) {
                if ($sentCount >= $maxPerRun) {
                    break;
                }

                $customerId = (int)$cart['customer_id'];
                $cartHash = md5($customerId . '_' . ($cart['products_summary'] ?? '') . '_' . ($cart['cart_total'] ?? 0));

                // Skip if already tracked
                if ($this->model_extension_kwtsms_module_kwtsms->isCartAlreadyTracked($customerId, $cartHash)) {
                    continue;
                }

                $cartTotal = (float)($cart['cart_total'] ?? 0);
                $productsSummary = substr($cart['products_summary'] ?? '', 0, 200);

                // Track the abandoned cart
                $trackId = $this->model_extension_kwtsms_module_kwtsms->trackAbandonedCart($customerId, $cartHash, $cartTotal, $productsSummary);
                if (!$trackId) {
                    continue;
                }

                // Get template
                $langCode = $this->model_extension_kwtsms_module_kwtsms->getOrderLanguageCode((int)($cart['language_id'] ?? 0));
                $lang = str_starts_with($langCode, 'ar') ? 'ar' : 'en';
                $template = $this->model_extension_kwtsms_module_kwtsms->getTemplate('abandoned_cart', $lang);

                if (empty($template)) {
                    continue;
                }

                // Build message
                $currencyCode = $this->config->get('config_currency') ?: 'USD';
                $data = [
                    'firstname'        => $cart['firstname'],
                    'lastname'         => $cart['lastname'],
                    'store_name'       => $this->config->get('config_name'),
                    'total'            => $cartTotal,
                    'currency_code'    => $currencyCode,
                    'products_summary' => $productsSummary,
                ];

                $message = $this->model_extension_kwtsms_module_kwtsms->replacePlaceholders($template, $data);

                // Send SMS
                $result = $library->send($cart['telephone'], $message, 'abandoned_cart');

                if (!empty($result['success']) && $result['sent'] > 0) {
                    $this->model_extension_kwtsms_module_kwtsms->markAbandonedCartSent($trackId);
                    $sentCount++;
                }
            }
        } catch (\Throwable $e) {
            // Don't break cron
        }
    }
}
