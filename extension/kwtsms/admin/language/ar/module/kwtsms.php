<?php
// Heading
$_['heading_title']    = 'kwtSMS - بوابة الرسائل القصيرة';

// Tabs
$_['tab_dashboard']    = 'لوحة التحكم';
$_['tab_settings']     = 'الإعدادات';
$_['tab_gateway']      = 'البوابة';
$_['tab_templates']    = 'القوالب';
$_['tab_logs']         = 'السجلات';
$_['tab_help']         = 'المساعدة';

// Dashboard
$_['text_sms_sent']        = 'مرسلة';
$_['text_sms_failed']      = 'فشلت';
$_['text_sms_skipped']     = 'تخطت';
$_['text_today']           = 'اليوم';
$_['text_7_days']          = '7 أيام';
$_['text_30_days']         = '30 يوم';
$_['text_balance']         = 'الرصيد';
$_['text_sender_id']       = 'معرف المرسل';
$_['text_test_mode']       = 'وضع الاختبار';
$_['text_gateway_status']  = 'البوابة';
$_['text_global_status']   = 'مفعل';
$_['text_debug_status']    = 'سجل التصحيح';
$_['text_connected']       = 'متصل';
$_['text_disconnected']    = 'غير متصل';
$_['text_on']              = 'مفعل';
$_['text_off']             = 'معطل';
$_['text_enabled']         = 'مفعل';
$_['text_disabled']        = 'معطل';
$_['text_yes']             = 'نعم';
$_['text_no']              = 'لا';
$_['text_none']            = 'لا يوجد';
$_['text_credits']         = 'رصيد';

// Settings
$_['entry_status']                  = 'تفعيل الإضافة';
$_['entry_test_mode']               = 'وضع الاختبار';
$_['entry_admin_phones']            = 'أرقام هواتف المسؤولين';
$_['entry_customer_statuses']       = 'حالات إشعار العميل';
$_['entry_admin_paid_statuses']     = 'حالات الطلب المدفوع للمسؤول';
$_['entry_admin_problem_statuses']  = 'حالات المشاكل للمسؤول';
$_['help_status']                   = 'تفعيل أو تعطيل إرسال الرسائل القصيرة بشكل عام.';
$_['help_test_mode']                = 'عند التفعيل، يتم إرسال الرسائل بوضع الاختبار (لا توصيل للهواتف، الرصيد قابل للاسترداد).';
$_['help_admin_phones']             = 'أرقام الهواتف مفصولة بفواصل لاستقبال تنبيهات المسؤول (مثال: 96598765432,96512345678).';
$_['help_customer_statuses']        = 'حالات الطلب التي ترسل رسالة قصيرة للعميل.';
$_['help_admin_paid_statuses']      = 'حالات الطلب التي ترسل رسالة "طلب مدفوع جديد" للمسؤول.';
$_['help_admin_problem_statuses']   = 'حالات الطلب التي ترسل رسالة "مشكلة في الطلب" للمسؤول.';

// Gateway
$_['entry_username']        = 'اسم المستخدم للـ API';
$_['entry_password']        = 'كلمة المرور للـ API';
$_['entry_sender_id']       = 'معرف المرسل';
$_['entry_country_code']    = 'رمز الدولة الافتراضي';
$_['entry_debug']           = 'سجل التصحيح';
$_['text_login']            = 'تسجيل الدخول';
$_['text_logout']           = 'تسجيل الخروج';
$_['text_reload']           = 'تحديث';
$_['text_coverage']         = 'التغطية';
$_['text_senderids']        = 'معرفات المرسل';
$_['text_test_gateway']     = 'اختبار البوابة';
$_['text_send_test']        = 'إرسال رسالة اختبار';
$_['entry_test_phone']      = 'رقم الهاتف';
$_['entry_test_message']    = 'الرسالة';
$_['help_country_code']     = 'يضاف لأرقام الهواتف بدون رمز دولة (مثال: 965 للكويت).';
$_['help_debug']            = 'تسجيل التفاصيل الداخلية (التطبيع، التحقق، التنظيف، استدعاءات الـ API) للتصحيح.';
$_['text_login_success']    = 'تم الاتصال بالبوابة بنجاح.';
$_['text_logout_success']   = 'تم قطع الاتصال بالبوابة.';
$_['text_reload_success']   = 'تم تحديث بيانات البوابة.';
$_['text_test_sent']        = 'تم إرسال رسالة الاختبار بنجاح.';

// Templates
$_['text_template_customer_order']   = 'رسالة حالة طلب العميل';
$_['text_template_admin_paid']       = 'رسالة طلب مدفوع جديد للمسؤول';
$_['text_template_admin_problem']    = 'رسالة مشكلة في الطلب للمسؤول';
$_['entry_template_en']              = 'الإنجليزية';
$_['entry_template_ar']              = 'العربية';
$_['text_placeholders']              = 'المتغيرات المتاحة';
$_['text_placeholder_list']          = '{order_id}, {customer_name}, {order_status}, {order_total}, {store_name}, {date}';

// Logs
$_['text_sms_log']         = 'سجل الرسائل';
$_['text_debug_log']       = 'سجل التصحيح';
$_['text_clear_all']       = 'مسح الكل';
$_['text_confirm_clear']   = 'هل أنت متأكد من مسح جميع السجلات؟';
$_['column_date']          = 'التاريخ';
$_['column_recipient']     = 'المستلم';
$_['column_status']        = 'الحالة';
$_['column_event_type']    = 'الحدث';
$_['column_order_id']      = 'رقم الطلب';
$_['column_msg_id']        = 'رقم الرسالة';
$_['column_points']        = 'النقاط';
$_['column_batch_id']      = 'رقم الدفعة';
$_['column_test_mode']     = 'اختبار';
$_['column_level']         = 'المستوى';
$_['column_context']       = 'السياق';
$_['column_message']       = 'الرسالة';
$_['text_no_results']      = 'لا توجد نتائج.';
$_['text_filter']          = 'تصفية';
$_['text_clear_success']   = 'تم مسح السجل بنجاح.';

// Help
$_['text_help_title']          = 'دليل إعداد kwtSMS';
$_['text_help_step1_title']    = '1. إنشاء حساب kwtSMS';
$_['text_help_step1_body']     = 'قم بزيارة kwtsms.com وسجل حسابا جديدا. ستحصل على بيانات الـ API (اسم المستخدم وكلمة المرور) بعد التسجيل.';
$_['text_help_step2_title']    = '2. تسجيل معرف المرسل';
$_['text_help_step2_body']     = 'اذهب الى لوحة تحكم kwtSMS وسجل معرف مرسل خاص. المعرف الافتراضي KWT-SMS للاختبار فقط. للاستخدام الفعلي تحتاج معرف مرسل خاص.';
$_['text_help_step3_title']    = '3. إعداد البوابة';
$_['text_help_step3_body']     = 'اذهب الى تبويب البوابة، أدخل اسم المستخدم وكلمة المرور، واضغط تسجيل الدخول. اختر معرف المرسل وحدد رمز الدولة الافتراضي.';
$_['text_help_step4_title']    = '4. تفعيل الإشعارات';
$_['text_help_step4_body']     = 'اذهب الى الإعدادات، فعل الإضافة، حدد حالات الطلب التي ترسل إشعارات، وأضف أرقام هواتف المسؤولين.';
$_['text_help_step5_title']    = '5. تخصيص القوالب';
$_['text_help_step5_body']     = 'اذهب الى القوالب لتعديل نص الرسالة لكل نوع إشعار. استخدم المتغيرات مثل {order_id} و {customer_name}.';
$_['text_help_step6_title']    = '6. الاختبار';
$_['text_help_step6_body']     = 'فعل وضع الاختبار في الإعدادات، ثم استخدم ميزة اختبار البوابة لإرسال رسالة اختبار. تحقق من السجلات للتأكيد.';
$_['text_help_links_title']    = 'روابط مفيدة';
$_['text_help_link_support']   = 'مركز دعم kwtSMS';
$_['text_help_link_faq']       = 'الأسئلة الشائعة';
$_['text_help_link_docs']      = 'وثائق الـ API';
$_['text_help_link_senderid']  = 'مساعدة معرف المرسل';

// Errors
$_['error_permission']     = 'ليس لديك صلاحية لتعديل هذه الإضافة.';
$_['error_login']          = 'فشل تسجيل الدخول. يرجى التحقق من بيانات الـ API.';
$_['error_not_configured'] = 'البوابة غير مهيأة. يرجى تسجيل الدخول أولا.';
$_['error_phone_required'] = 'رقم الهاتف مطلوب.';
$_['error_message_required'] = 'الرسالة مطلوبة.';
$_['error_send_failed']    = 'فشل إرسال الرسالة. تحقق من السجلات للتفاصيل.';

// Success
$_['text_success']         = 'تم حفظ الإعدادات بنجاح.';

// Settings - Event toggles
$_['entry_low_stock_threshold']     = 'حد المخزون المنخفض';
$_['help_low_stock_threshold']      = 'إرسال تنبيه للمسؤول عندما ينخفض مخزون أي منتج الى هذا المستوى أو أقل.';
$_['entry_customer_events']         = 'إشعارات أحداث العملاء';
$_['entry_admin_events']            = 'إشعارات أحداث المسؤول';
$_['text_event_customer_registered'] = 'رسالة ترحيب العميل';
$_['text_event_admin_new_customer']  = 'تنبيه تسجيل عميل جديد';
$_['text_event_low_stock']           = 'تنبيه مخزون منخفض';
$_['text_event_admin_new_review']    = 'تنبيه تقييم منتج جديد';
$_['text_event_admin_return_request'] = 'تنبيه طلب إرجاع';

// Templates - Categories
$_['text_template_category_order']    = 'إشعارات الطلبات';
$_['text_template_category_customer'] = 'إشعارات العملاء';
$_['text_template_category_admin']    = 'تنبيهات المسؤول';
$_['text_template_overrides']         = 'تخصيص حسب الحالة';
$_['text_template_override_help']     = 'اتركه فارغا لاستخدام قالب حالة الطلب الافتراضي أعلاه.';
$_['text_reset']                      = 'إعادة تعيين';
$_['text_reset_confirm']              = 'إعادة تعيين هذا القالب للافتراضي؟';
$_['text_reset_success']              = 'تم إعادة تعيين القالب.';

// OTP Settings
$_['entry_cod_otp_enabled']      = 'التحقق بالرمز عند الدفع عند الاستلام';
$_['help_cod_otp_enabled']       = 'طلب التحقق من رقم الهاتف لطلبات الدفع عند الاستلام.';
$_['entry_otp_length']           = 'عدد أرقام الرمز';
$_['entry_otp_expiry']           = 'صلاحية الرمز (دقائق)';
$_['entry_otp_max_per_phone']    = 'الحد الأقصى للطلبات لكل رقم/ساعة';
$_['entry_otp_max_per_ip']       = 'الحد الأقصى للطلبات لكل IP/ساعة';
$_['entry_otp_resend_cooldown']  = 'فترة انتظار إعادة الإرسال (ثواني)';
$_['text_otp_sender_warning']    = 'لضمان توصيل رمز التحقق، استخدم معرف مرسل معاملات. معرفات المرسل الترويجية قد تحجب بفلتر عدم الإزعاج.';
$_['text_otp_settings']          = 'التحقق بالرمز عند الدفع عند الاستلام';

// Login & Registration OTP
$_['text_otp_auth_settings']         = 'التحقق بالرمز عند تسجيل الدخول والتسجيل';
$_['entry_otp_register_enabled']     = 'التحقق عند التسجيل';
$_['help_otp_register_enabled']      = 'طلب التحقق من رقم الهاتف عند تسجيل حساب جديد.';
$_['entry_otp_login_enabled']        = 'التحقق عند تسجيل الدخول';
$_['help_otp_login_enabled']         = 'طلب التحقق من رقم الهاتف عند تسجيل دخول العملاء.';

// Campaigns
$_['tab_campaigns']           = 'الحملات';
$_['text_send_campaign']      = 'إرسال حملة';
$_['entry_campaign_name']     = 'اسم الحملة';
$_['entry_audience']          = 'الجمهور';
$_['text_all_customers']      = 'جميع العملاء';
$_['text_customer_group']     = 'مجموعة العملاء';
$_['text_custom_numbers']     = 'أرقام مخصصة';
$_['entry_campaign_message']  = 'الرسالة';
$_['text_preview']            = 'معاينة';
$_['text_campaign_history']   = 'سجل الحملات';
$_['text_recipients']         = 'المستلمين';
$_['text_estimated_credits']  = 'الرصيد المقدر';
$_['text_campaign_sent']      = 'تم إرسال الحملة بنجاح.';
$_['text_campaign_no_recipients'] = 'لا يوجد مستلمين.';
$_['text_confirm_send']       = 'إرسال هذه الحملة الى %s مستلم؟';

// Abandoned Cart
$_['entry_abandoned_cart_enabled']   = 'رسائل السلة المتروكة';
$_['help_abandoned_cart_enabled']    = 'إرسال تذكيرات للعملاء الذين تركوا منتجات في سلة التسوق.';
$_['entry_abandoned_cart_delay']     = 'التأخير قبل الإرسال (دقائق)';
$_['entry_abandoned_cart_max']       = 'الحد الأقصى للرسائل لكل تشغيل';
$_['text_abandoned_cart_settings']   = 'استرجاع السلة المتروكة';
