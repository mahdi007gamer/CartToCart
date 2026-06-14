<?php
namespace ProfessionalCardToCard;

defined('ABSPATH') || exit;

class Payment_Handler {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // هوک پردازش پست مستقیم برای فرم‌های شورتکد
        add_action('admin_post_nopriv_p2p_submit_form', array($this, 'handle_form_submission'));
        add_action('admin_post_p2p_submit_form', array($this, 'handle_form_submission'));
    }

    /**
     * پیدا کردن و تزریق قالب‌ها با قابلیت رونویسی توسط قالب فعال سایت (Template Override)
     */
    public function get_template($template_name, $args = array()) {
        if (!empty($args)) {
            extract($args);
        }

        // جستجو در پوشه فرزند قالب یا قالب کنونی (مسیر: child-theme/card-to-card/form.php)
        $theme_file = locate_template('card-to-card/' . $template_name);
        
        if ($theme_file) {
            $file = $theme_file;
        } else {
            // مسیر پیشفرض افزونه
            $file = P2P_PATH . 'templates/' . $template_name;
        }

        if (file_exists($file)) {
            ob_start();
            include $file;
            return ob_get_clean();
        }

        return '';
    }

    /**
     * رندر نهایی وبجت به کمک فیلم شورتکد
     */
    public function render_payment_form($atts) {
        $active_cards = Database::get_active_cards();
        $settings = get_option('p2p_settings');
        
        if (empty($active_cards)) {
            return '<p style="color:red; text-align:center;">' . esc_html__('هیچ کارت بانکی فعالی توسط مدیریت تعریف نشده است.', 'professional-card-to-card') . '</p>';
        }

        // بررسی اینکه آیا قبلا پرداختی موفق با کوکی ثبت شده
        if (isset($_GET['p2p_status']) && $_GET['p2p_status'] === 'success' && !empty($_GET['p2p_tid'])) {
            $transaction_id = intval($_GET['p2p_tid']);
            $transaction = Database::get_transaction($transaction_id);
            if ($transaction) {
                return $this->get_template('success.php', array(
                    'transaction' => $transaction,
                    'settings' => $settings
                ));
            }
        }

        return $this->get_template('form.php', array(
            'active_cards' => $active_cards,
            'settings' => $settings,
            'nonce' => wp_create_nonce('p2p_standalone_payment')
        ));
    }

    /**
     * پردازش سابمیت‌های فرم شورتکد غیراجباری ووکامرس
     */
    public function handle_form_submission() {
        global $wpdb;

        if (empty($_POST['p2p_nonce']) || !wp_verify_nonce($_POST['p2p_nonce'], 'p2p_standalone_payment')) {
            wp_die(__('اعتبارسنجی امنیتی منقضی شده است.', 'professional-card-to-card'));
        }

        $full_name    = sanitize_text_field($_POST['full_name']);
        $mobile       = sanitize_text_field($_POST['mobile']);
        $amount       = floatval(sanitize_text_field($_POST['amount']));
        $bank_card_id = intval($_POST['bank_card_id']);
        $last4        = isset($_POST['last4digits']) ? sanitize_text_field($_POST['last4digits']) : '';
        
        $data = array(
            'full_name'    => $full_name,
            'mobile'       => $mobile,
            'amount'       => $amount,
            'bank_card_id' => $bank_card_id,
            'last4digits'  => $last4,
            'g-recaptcha-response' => isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : ''
        );

        // اعتبارسنجی ورودی‌ها
        $errors = Security::validate_submission($data);
        if (!empty($errors)) {
            wp_die(implode('<br>', $errors));
        }

        $mobile = Security::validate_mobile($mobile);

        // آپلود رسید رسید فیزیکی فیش بانکی
        $receipt_url = '';
        if (isset($_FILES['receipt']) && !empty($_FILES['receipt']['name'])) {
            $upload_res = Security::handle_receipt_upload($_FILES['receipt']);
            if (isset($upload_res['error'])) {
                wp_die($upload_res['error']);
            }
            $receipt_url = $upload_res['url'];
        }

        // ثبت در دیتابیس
        $table_transactions = $wpdb->prefix . 'c2c_transactions';
        $insert_res = $wpdb->insert($table_transactions, array(
            'order_id'      => null, // پرداخت ووکامرس نیست
            'user_id'       => get_current_user_id() ? get_current_user_id() : null,
            'full_name'     => $full_name,
            'mobile'        => $mobile,
            'amount'        => $amount,
            'bank_card_id'  => $bank_card_id,
            'last4digits'   => $last4,
            'receipt_url'   => $receipt_url,
            'status'        => 'pending',
            'admin_notes'   => '',
            'ip_address'    => sanitize_text_field($_SERVER['REMOTE_ADDR'])
        ));

        if ($insert_res === false) {
            wp_die(__('خطا در دخیره‌سازی اطلاعات پرداخت در دیتابیس.', 'professional-card-to-card'));
        }

        $transaction_id = $wpdb->insert_id;

        // وب هوک‌ها و ارسال ایمیل/پیامک
        Notifications::send_admin_email($transaction_id);
        Notifications::send_telegram_notification($transaction_id);
        Notifications::send_sms_notification($transaction_id, 'admin'); // به ادمین
        Notifications::send_sms_notification($transaction_id, 'user');  // به کاربر

        // هدایت کاربر به صفحه موفقیت آمیز
        $redirect_url = add_query_arg(array(
            'p2p_status' => 'success',
            'p2p_tid'    => $transaction_id
        ), wp_get_referer());

        wp_safe_redirect($redirect_url);
        exit;
    }
}
