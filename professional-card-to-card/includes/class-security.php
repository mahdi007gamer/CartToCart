<?php
namespace ProfessionalCardToCard;

defined('ABSPATH') || exit;

class Security {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // هوک امنیت کلی افزونه
    }

    /**
     * بررسی امنیت تراکنش و توکن نانس
     */
    public static function verify_nonce($nonce, $action) {
        if (!wp_verify_nonce($nonce, $action)) {
            wp_die(__('خطای امنیتی: توکن نانس نامعتبر است.', 'professional-card-to-card'));
        }
        return true;
    }

    /**
     * احرازهویت سطح دسترسی ادمین
     */
    public static function check_admin_capabilities() {
        if (!current_user_can('manage_options')) {
            wp_die(__('شما دسترسی کافی برای انجام این عملیات را ندارید.', 'professional-card-to-card'));
        }
    }

    /**
     * بررسی صحت قالب شماره موبایل ایران
     */
    public static function validate_mobile($mobile) {
        // حذف صفرهای اضافی و کاراکترهای نامعتبر
        $mobile = preg_replace('/[^0-9]/', '', $mobile);
        
        // همسان‌سازی شماره موبایل (تبدیل به قالب ۰۹xxxxxxxxx)
        if (preg_match('/^(09|9)[0-9]{9}$/', $mobile)) {
            if (strlen($mobile) === 10) {
                $mobile = '0' . $mobile;
            }
            return $mobile;
        }
        return false;
    }

    /**
     * اعتبارسنجی فیلدهای امنیتی پرداخت تکی
     */
    public static function validate_submission($data) {
        $errors = array();

        if (empty($data['full_name'])) {
            $errors[] = __('لطفا نام و نام خانوادگی خود را وارد کنید.', 'professional-card-to-card');
        }

        $mobile = self::validate_mobile($data['mobile']);
        if (!$mobile) {
            $errors[] = __('شماره موبایل وارد شده معتبر نمی‌باشد. نمونه صحیح: 09121234567', 'professional-card-to-card');
        }

        if (empty($data['amount']) || floatval($data['amount']) <= 0) {
            $errors[] = __('مبلغ وجه ارسالی نامعتبر است.', 'professional-card-to-card');
        }

        if (empty($data['bank_card_id'])) {
            $errors[] = __('لطفا یکی از کارت‌های بانکی مقصد را انتخاب نمایید.', 'professional-card-to-card');
        }

        $settings = get_option('p2p_settings');
        if (isset($settings['require_last4']) && $settings['require_last4'] === 'required') {
            if (empty($data['last4digits']) || !preg_match('/^[0-9]{4}$/', $data['last4digits'])) {
                $errors[] = __('لطفا ۴ رقم آخر کارت فرستنده خود را با دقت به صورت عددی وارد کنید.', 'professional-card-to-card');
            }
        }

        // احراز کپچا در صورت فعال بودن
        if (!empty($settings['recaptcha_secret_key'])) {
            if (empty($data['g-recaptcha-response'])) {
                $errors[] = __('کپچا گوگل تکمیل نشده است.', 'professional-card-to-card');
            } else {
                $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
                    'body' => array(
                        'secret'   => $settings['recaptcha_secret_key'],
                        'response' => sanitize_text_field($data['g-recaptcha-response']),
                        'remoteip' => $_SERVER['REMOTE_ADDR']
                    )
                ));

                if (!is_wp_error($response)) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    if (empty($body['success'])) {
                        $errors[] = __('پاسخ کپچا گوگل رد شد. لطفا مجددا تلاش کنید.', 'professional-card-to-card');
                    }
                } else {
                    $errors[] = __('برقراری ارتباط با سرور کپچا با خطا مواجه شد.', 'professional-card-to-card');
                }
            }
        }

        return $errors;
    }

    /**
     * آپلود فیلتر شده تصویر رسید
     */
    public static function handle_receipt_upload($file_array) {
        if (empty($file_array['name'])) {
            return array('error' => __('فایلی آپلود نشده است.', 'professional-card-to-card'));
        }

        // بررسی پسوند فایل تصویر
        $allowed_exts = array('jpg', 'jpeg', 'png', 'webp');
        $file_ext = strtolower(pathinfo($file_array['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_exts)) {
            return array('error' => __('پسوند فایل نامعتبر است. تنها تصاویر فرمت jpg، png، jpeg و webp مجاز هستند.', 'professional-card-to-card'));
        }

        // بررسی سایز فایل (حداکثر ۲ مگابایت)
        $max_size = 2 * 1024 * 1024; // 2MB
        if ($file_array['size'] > $max_size) {
            return array('error' => __('حجم فایل رسید نمی‌تواند بیشتر از 2 مگابایت باشد.', 'professional-card-to-card'));
        }

        // بررسی وب تایپ میم فایل
        $mime = mime_content_type($file_array['tmp_name']);
        if (!in_array($mime, array('image/jpeg', 'image/png', 'image/webp', 'image/jpg'))) {
            return array('error' => __('نوع فایل معتبر نیست. لطفا تصویر رسید واقعی آپلود کنید.', 'professional-card-to-card'));
        }

        // آماده سازی دایرکتوری در صورت نبودن
        $upload_dir = P2P_UPLOAD_DIR;
        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        // نام‌گذاری ایمن تصویر با هش منحصربفرد
        $filename = 'receipt_' . uniqid() . '.' . $file_ext;
        $dest_path = $upload_dir . '/' . $filename;

        if (move_uploaded_file($file_array['tmp_name'], $dest_path)) {
            return array('url' => P2P_UPLOAD_URL . '/' . $filename);
        }

        return array('error' => __('خطا در انتقال فایل در سرور آپلود.', 'professional-card-to-card'));
    }
}
