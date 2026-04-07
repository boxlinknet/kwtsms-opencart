<?php
namespace Opencart\Catalog\Controller\Extension\Kwtsms\Module;

class KwtsmsOtp extends \Opencart\System\Engine\Controller {

    public function send(): void {
        $json = [];

        try {
            // Check extension enabled + COD OTP enabled
            if (!$this->config->get('module_kwtsms_status') || !$this->config->get('module_kwtsms_username') || !$this->config->get('module_kwtsms_cod_otp_enabled')) {
                $json['error'] = 'OTP verification is not available.';
                $this->outputJson($json);
                return;
            }

            $phone = isset($this->request->post['phone']) ? trim((string)$this->request->post['phone']) : '';
            if (empty($phone)) {
                $json['error'] = 'Phone number is required.';
                $this->outputJson($json);
                return;
            }

            // Normalize phone
            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
            $normalized = $library->normalize($phone);

            if (!$library->verify($normalized)) {
                $json['error'] = 'Invalid phone number.';
                $this->outputJson($json);
                return;
            }

            $this->load->model('extension/kwtsms/module/kwtsms');

            // Rate limit check
            $maxPhone = (int)($this->config->get('module_kwtsms_otp_max_per_phone') ?: 5);
            $maxIp = (int)($this->config->get('module_kwtsms_otp_max_per_ip') ?: 10);
            $ip = $this->request->server['REMOTE_ADDR'] ?? '0.0.0.0';

            $rateCheck = $this->model_extension_kwtsms_module_kwtsms->checkOtpRateLimit($normalized, $ip, $maxPhone, $maxIp);
            if (!$rateCheck['allowed']) {
                $this->load->language('extension/kwtsms/module/kwtsms');
                $json['error'] = $this->language->get('text_otp_rate_limit');
                $this->outputJson($json);
                return;
            }

            // Create OTP
            $codeLength = (int)($this->config->get('module_kwtsms_otp_length') ?: 6);
            $expiryMinutes = (int)($this->config->get('module_kwtsms_otp_expiry') ?: 5);
            $otp = $this->model_extension_kwtsms_module_kwtsms->createOtp($normalized, $ip, $codeLength, $expiryMinutes);

            // Build message from template
            $template = $this->model_extension_kwtsms_module_kwtsms->getTemplate('otp_verification', 'en');
            if (empty($template)) {
                $template = 'Your verification code for {store_name} is: {otp_code}. Valid for {expiry_minutes} minutes.';
            }

            $message = str_replace(
                ['{otp_code}', '{expiry_minutes}', '{store_name}'],
                [$otp['code'], (string)$expiryMinutes, $this->config->get('config_name')],
                $template
            );

            // Send via library
            $result = $library->send($normalized, $message, 'otp_verification');

            if (!empty($result['success']) && $result['sent'] > 0) {
                // Mask phone for display: 96598***432
                $masked = substr($normalized, 0, 5) . '***' . substr($normalized, -3);
                $cooldown = (int)($this->config->get('module_kwtsms_otp_resend_cooldown') ?: 60);

                $json['success'] = true;
                $json['phone_masked'] = $masked;
                $json['resend_cooldown'] = $cooldown;
            } else {
                $this->load->language('extension/kwtsms/module/kwtsms');
                $json['error'] = $this->language->get('text_otp_send_failed');
            }
        } catch (\Throwable $e) {
            $json['error'] = 'An error occurred. Please try again.';
        }

        $this->outputJson($json);
    }

    public function verify(): void {
        $json = [];

        try {
            $phone = isset($this->request->post['phone']) ? trim((string)$this->request->post['phone']) : '';
            $code = isset($this->request->post['code']) ? trim((string)$this->request->post['code']) : '';

            if (empty($phone) || empty($code)) {
                $json['error'] = 'Phone and code are required.';
                $this->outputJson($json);
                return;
            }

            require_once(DIR_EXTENSION . 'kwtsms/vendor/autoload.php');
            $library = new \Opencart\System\Library\Extension\Kwtsms\Kwtsms($this->registry);
            $normalized = $library->normalize($phone);

            $this->load->model('extension/kwtsms/module/kwtsms');
            $result = $this->model_extension_kwtsms_module_kwtsms->verifyOtp($normalized, $code);
            $this->load->language('extension/kwtsms/module/kwtsms');

            if ($result['valid']) {
                $this->session->data['kwtsms_otp_verified'] = $normalized;
                $json['valid'] = true;
                $json['message'] = $this->language->get('text_otp_success');
            } else {
                $json['valid'] = false;
                if ($result['error'] === 'expired') {
                    $json['message'] = $this->language->get('text_otp_expired');
                } elseif ($result['error'] === 'failed') {
                    $json['message'] = $this->language->get('text_otp_failed');
                } else {
                    $remaining = $result['remaining'] ?? 0;
                    $json['message'] = sprintf($this->language->get('text_otp_invalid'), $remaining);
                    $json['remaining'] = $remaining;
                }
            }
        } catch (\Throwable $e) {
            $json['valid'] = false;
            $json['message'] = 'An error occurred.';
        }

        $this->outputJson($json);
    }

    public function status(): void {
        $json = [
            'verified' => !empty($this->session->data['kwtsms_otp_verified']),
            'enabled'  => (bool)$this->config->get('module_kwtsms_cod_otp_enabled'),
        ];

        $this->outputJson($json);
    }

    /**
     * View event handler: inject OTP modal into checkout page.
     * Trigger: catalog/view/checkout/checkout/after
     */
    public function injectCheckout(string &$route, array &$args, mixed &$output): void {
        if (!$this->config->get('module_kwtsms_status') || !$this->config->get('module_kwtsms_cod_otp_enabled')) {
            return;
        }

        $this->load->language('extension/kwtsms/module/kwtsms');

        $data = [
            'otp_send_url'     => $this->url->link('extension/kwtsms/module/kwtsms_otp.send'),
            'otp_verify_url'   => $this->url->link('extension/kwtsms/module/kwtsms_otp.verify'),
            'otp_status_url'   => $this->url->link('extension/kwtsms/module/kwtsms_otp.status'),
            'text_otp_title'   => $this->language->get('text_otp_title'),
            'text_otp_verify'  => $this->language->get('text_otp_verify'),
            'text_otp_resend'  => $this->language->get('text_otp_resend'),
            'text_otp_placeholder' => $this->language->get('text_otp_placeholder'),
            'resend_cooldown'  => (int)($this->config->get('module_kwtsms_otp_resend_cooldown') ?: 60),
        ];

        $otpHtml = $this->load->view('extension/kwtsms/module/kwtsms_otp', $data);
        $output .= $otpHtml;
    }

    private function outputJson(array $json): void {
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
