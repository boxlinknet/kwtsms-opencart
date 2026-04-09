<?php
// Heading
$_['heading_title']    = 'kwtSMS - SMS Gateway';

// Tabs
$_['tab_dashboard']    = 'Dashboard';
$_['tab_settings']     = 'Settings';
$_['tab_gateway']      = 'Gateway';
$_['tab_templates']    = 'Templates';
$_['tab_logs']         = 'Logs';
$_['tab_help']         = 'Help';

// Dashboard
$_['text_sms_sent']        = 'Sent';
$_['text_sms_failed']      = 'Failed';
$_['text_sms_skipped']     = 'Skipped';
$_['text_today']           = 'Today';
$_['text_7_days']          = '7 Days';
$_['text_30_days']         = '30 Days';
$_['text_balance']         = 'Balance';
$_['text_sender_id']       = 'Sender ID';
$_['text_test_mode']       = 'Test Mode';
$_['text_gateway_status']  = 'Gateway';
$_['text_global_status']   = 'SMS Module';
$_['text_debug_status']    = 'Debug Log';
$_['text_connected']       = 'Connected';
$_['text_disconnected']    = 'Disconnected';
$_['text_on']              = 'On';
$_['text_off']             = 'Off';
$_['text_enabled']         = 'Enabled';
$_['text_disabled']        = 'Disabled';
$_['text_yes']             = 'Yes';
$_['text_no']              = 'No';
$_['text_none']            = 'None';
$_['text_credits']         = 'credits';

// Settings
$_['entry_status']                  = 'Enable Extension';
$_['entry_test_mode']               = 'Test Mode';
$_['entry_admin_phones']            = 'Admin Phone Numbers';
$_['entry_customer_statuses']       = 'Customer Notification Statuses';
$_['entry_admin_paid_statuses']     = 'Admin Paid Order Statuses';
$_['entry_admin_problem_statuses']  = 'Admin Problem Statuses';
$_['help_status']                   = 'Enable or disable all SMS sending globally.';
$_['help_test_mode']                = 'When enabled, SMS messages are sent with test=1 (no delivery to handsets, credits recoverable).';
$_['help_admin_phones']             = 'Comma-separated phone numbers to receive admin SMS alerts (e.g. 96598765432,96512345678).';
$_['help_customer_statuses']        = 'Order statuses that trigger an SMS to the customer.';
$_['help_admin_paid_statuses']      = 'Order statuses that trigger a "new paid order" SMS to admin.';
$_['help_admin_problem_statuses']   = 'Order statuses that trigger a "problem order" SMS to admin.';

// Gateway
$_['entry_username']        = 'API Username';
$_['entry_password']        = 'API Password';
$_['entry_sender_id']       = 'Sender ID';
$_['entry_country_code']    = 'Default Country Code';
$_['entry_debug']           = 'Debug Logging';
$_['text_login']            = 'Login';
$_['text_logout']           = 'Logout';
$_['text_reload']           = 'Reload';
$_['text_coverage']         = 'Coverage';
$_['text_senderids']        = 'Sender IDs';
$_['text_test_gateway']     = 'Test Gateway';
$_['text_send_test']        = 'Send Test SMS';
$_['entry_test_phone']      = 'Phone Number';
$_['entry_test_message']    = 'Message';
$_['help_country_code']     = 'Prepended to phone numbers without a country prefix (e.g. 965 for Kuwait).';
$_['help_debug']            = 'Log detailed internal flow (normalize, verify, clean, API calls) for debugging.';
$_['text_login_success']    = 'Gateway connected successfully.';
$_['text_logout_success']   = 'Gateway disconnected.';
$_['text_reload_success']   = 'Gateway data refreshed.';
$_['text_test_sent']        = 'Test SMS sent successfully.';

// Templates
$_['text_template_customer_order']   = 'Customer Order Status SMS';
$_['text_template_admin_paid']       = 'Admin New Paid Order SMS';
$_['text_template_admin_problem']    = 'Admin Problem Status SMS';
$_['entry_template_en']              = 'English';
$_['entry_template_ar']              = 'Arabic';
$_['text_placeholders']              = 'Available placeholders';
$_['text_placeholder_list']          = '{order_id}, {customer_name}, {order_status}, {order_total}, {store_name}, {date}';

// Logs
$_['text_sms_log']         = 'SMS Log';
$_['text_debug_log']       = 'Debug Log';
$_['text_clear_all']       = 'Clear All';
$_['text_confirm_clear']   = 'Are you sure you want to clear all log entries?';
$_['column_date']          = 'Date';
$_['column_recipient']     = 'Recipient';
$_['column_status']        = 'Status';
$_['column_event_type']    = 'Event';
$_['column_order_id']      = 'Order ID';
$_['column_msg_id']        = 'Message ID';
$_['column_points']        = 'Points';
$_['column_batch_id']      = 'Batch ID';
$_['column_test_mode']     = 'Test';
$_['column_level']         = 'Level';
$_['column_context']       = 'Context';
$_['column_message']       = 'Message';
$_['text_no_results']      = 'No records found.';
$_['text_filter']          = 'Filter';
$_['text_clear_success']   = 'Log cleared successfully.';

// Help
$_['text_help_title']          = 'kwtSMS Setup Guide';
$_['text_help_step1_title']    = '1. Create a kwtSMS Account';
$_['text_help_step1_body']     = 'Visit kwtsms.com and sign up for an account. You will receive API credentials (username and password) after registration.';
$_['text_help_step2_title']    = '2. Register a Sender ID';
$_['text_help_step2_body']     = 'Go to your kwtSMS dashboard and register a private Sender ID. The default KWT-SMS is for testing only. For production, you need your own Sender ID.';
$_['text_help_step3_title']    = '3. Configure the Gateway';
$_['text_help_step3_body']     = 'Go to the Gateway tab, enter your API username and password, and click Login. Select your Sender ID and set the default country code.';
$_['text_help_step4_title']    = '4. Enable Notifications';
$_['text_help_step4_body']     = 'Go to Settings, enable the extension, configure which order statuses trigger SMS notifications, and add admin phone numbers.';
$_['text_help_step5_title']    = '5. Customize Templates';
$_['text_help_step5_body']     = 'Go to Templates to edit the SMS message text for each notification type. Use placeholders like {order_id} and {customer_name}.';
$_['text_help_step6_title']    = '6. Test';
$_['text_help_step6_body']     = 'Enable Test Mode in Settings, then use the Test Gateway feature to send a test SMS. Check the Logs tab to verify.';
$_['text_help_links_title']    = 'Useful Links';
$_['text_help_link_support']   = 'kwtSMS Support Center';
$_['text_help_link_faq']       = 'Frequently Asked Questions';
$_['text_help_link_docs']      = 'API Documentation';
$_['text_help_link_senderid']  = 'Sender ID Help';

// Errors
$_['error_permission']     = 'You do not have permission to modify this extension.';
$_['error_login']          = 'Login failed. Please check your API credentials.';
$_['error_not_configured'] = 'Gateway is not configured. Please login first.';
$_['error_phone_required'] = 'Phone number is required.';
$_['error_message_required'] = 'Message is required.';
$_['error_send_failed']    = 'Failed to send SMS. Check the logs for details.';

// Success
$_['text_success']         = 'Settings saved successfully.';

// Settings - Event toggles
$_['entry_low_stock_threshold']     = 'Low Stock Threshold';
$_['help_low_stock_threshold']      = 'Send admin alert when any product stock falls to this level or below.';
$_['entry_customer_events']         = 'Customer Event Notifications';
$_['entry_admin_events']            = 'Admin Event Notifications';
$_['text_event_customer_registered'] = 'Customer Welcome SMS';
$_['text_event_admin_new_customer']  = 'New Customer Registration Alert';
$_['text_event_low_stock']           = 'Low Stock Alert';
$_['text_event_admin_new_review']    = 'New Product Review Alert';
$_['text_event_admin_return_request'] = 'Return Request Alert';

// Templates - Categories
$_['text_template_category_order']    = 'Order Notifications';
$_['text_template_category_customer'] = 'Customer Notifications';
$_['text_template_category_admin']    = 'Admin Alerts';
$_['text_template_overrides']         = 'Per-Status Overrides';
$_['text_template_override_help']     = 'Leave empty to use the default customer order template above.';
$_['text_reset']                      = 'Reset';
$_['text_reset_confirm']              = 'Reset this template to default?';
$_['text_reset_success']              = 'Template reset to default.';

// OTP Settings
$_['entry_cod_otp_enabled']      = 'COD OTP Verification';
$_['help_cod_otp_enabled']       = 'Require phone verification for Cash on Delivery orders.';
$_['entry_otp_length']           = 'OTP Code Length';
$_['entry_otp_expiry']           = 'OTP Expiry (minutes)';
$_['entry_otp_max_per_phone']    = 'Max Requests per Phone/Hour';
$_['entry_otp_max_per_ip']       = 'Max Requests per IP/Hour';
$_['entry_otp_resend_cooldown']  = 'Resend Cooldown (seconds)';
$_['text_otp_sender_warning']    = 'For reliable OTP delivery, use a Transactional Sender ID. Promotional sender IDs may be blocked by DND filtering.';
$_['text_otp_settings']          = 'COD OTP Verification';

// Login & Registration OTP
$_['text_otp_auth_settings']         = 'Login & Registration OTP';
$_['entry_otp_register_enabled']     = 'Registration OTP';
$_['help_otp_register_enabled']      = 'Require phone verification via OTP when customers register a new account.';
$_['entry_otp_login_enabled']        = 'Login OTP';
$_['help_otp_login_enabled']         = 'Require phone verification via OTP when customers log in.';

// Campaigns
$_['tab_campaigns']           = 'Campaigns';
$_['text_send_campaign']      = 'Send Campaign';
$_['entry_campaign_name']     = 'Campaign Name';
$_['entry_audience']          = 'Audience';
$_['text_all_customers']      = 'All Customers';
$_['text_customer_group']     = 'Customer Group';
$_['text_custom_numbers']     = 'Custom Numbers';
$_['entry_campaign_message']  = 'Message';
$_['text_preview']            = 'Preview';
$_['text_campaign_history']   = 'Campaign History';
$_['text_recipients']         = 'Recipients';
$_['text_estimated_credits']  = 'Estimated credits';
$_['text_campaign_sent']      = 'Campaign sent successfully.';
$_['text_campaign_no_recipients'] = 'No recipients found.';
$_['text_confirm_send']       = 'Send this campaign to %s recipients?';

// Abandoned Cart
$_['entry_abandoned_cart_enabled']   = 'Abandoned Cart SMS';
$_['help_abandoned_cart_enabled']    = 'Send SMS reminders to customers who left items in their cart.';
$_['entry_abandoned_cart_delay']     = 'Delay Before Sending (minutes)';
$_['entry_abandoned_cart_max']       = 'Max SMS per Cron Run';
$_['text_abandoned_cart_settings']   = 'Abandoned Cart Recovery';
