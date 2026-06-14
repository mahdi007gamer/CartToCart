<?php
namespace ProfessionalCardToCard;

defined('ABSPATH') || exit;

class Admin {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // ایجاد منوهای مدیریت
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // ثبت کارهای فورس یا ذخیره سازی درون ساید‌بار ادمین
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    /**
     * افزودن منوی اصلی کارت به کارت و زیرمنوها
     */
    public function add_admin_menu() {
        // منوی اصلی
        add_menu_page(
            __('کارت به کارت', 'professional-card-to-card'),
            __('کارت به کارت', 'professional-card-to-card'),
            'manage_options',
            'p2p-gateway',
            array($this, 'render_transactions_page'),
            'dashicons-bank',
            56
        );

        // زیرمنوی اول: تراکنش‌ها (همان صفحه منوی اصلی)
        add_submenu_page(
            'p2p-gateway',
            __('تراکنش‌ها', 'professional-card-to-card'),
            __('تراکنش‌ها', 'professional-card-to-card'),
            'manage_options',
            'p2p-gateway',
            array($this, 'render_transactions_page')
        );

        // زیرمنوی دوم: حساب‌ها و کارت‌های بانکی
        add_submenu_page(
            'p2p-gateway',
            __('کارت‌های بانکی', 'professional-card-to-card'),
            __('کارت‌های بانکی', 'professional-card-to-card'),
            'manage_options',
            'p2p-cards',
            array($this, 'render_cards_page')
        );

        // زیرمنوی سوم: تنظیمات درگاه
        add_submenu_page(
            'p2p-gateway',
            __('تنظیمات', 'professional-card-to-card'),
            __('تنظیمات', 'professional-card-to-card'),
            'manage_options',
            'p2p-settings',
            array($this, 'render_settings_page')
        );

        // زیرمنوی چهارم: گزارشات و لاگ‌ها
        add_submenu_page(
            'p2p-gateway',
            __('لاگ‌های سیستم', 'professional-card-to-card'),
            __('لاگ‌های سیستم', 'professional-card-to-card'),
            'manage_options',
            'p2p-logs',
            array($this, 'render_logs_page')
        );

        // زیرمنوی پنجم: خروجی اکسل و CSV
        add_submenu_page(
            'p2p-gateway',
            __('خروجی گرفتن', 'professional-card-to-card'),
            __('خروجی گرفتن', 'professional-card-to-card'),
            'manage_options',
            'p2p-export',
            array($this, 'render_export_page')
        );
    }

    /**
     * پیدا کردن روت ویوها
     */
    private function get_view($view_name, $args = array()) {
        if (!empty($args)) {
            extract($args);
        }
        $file = P2P_PATH . 'admin/views/' . $view_name . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="notice notice-error"><p>فایل مربوط به قالب صفحه مدیریت معتبر نیست.</p></div>';
        }
    }

    /* --- رندر صفحات زیر منو --- */

    public function render_transactions_page() {
        $this->get_view('transactions');
    }

    public function render_cards_page() {
        $this->get_view('bank-cards');
    }

    public function render_settings_page() {
        $this->get_view('settings');
    }

    public function render_logs_page() {
        $this->get_view('logs');
    }

    public function render_export_page() {
        $this->get_view('export');
    }

    /**
     * مدیریت پردازش کارهای دکمه‌ها و فرم‌های تنظیمات در پنل مدیریت
     */
    public function handle_admin_actions() {
        global $wpdb;

        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        // ۱. پردازش ثبت تغییرات وضعیت تراکنش (تایید / رد)
        if (isset($_GET['action']) && $_GET['action'] === 'p2p_update_status' && isset($_GET['tid']) && isset($_GET['nonce'])) {
            $trans_id = intval($_GET['tid']);
            $new_status = sanitize_text_field($_GET['status']);
            
            if (!wp_verify_nonce($_GET['nonce'], 'p2p_update_status_' . $trans_id)) {
                wp_die('ممیزی امنیتی فرم رد شد.');
            }

            $valid_statuses = array('approved', 'rejected', 'pending');
            if (in_array($new_status, $valid_statuses)) {
                $table_trans = $wpdb->prefix . 'c2c_transactions';
                
                $old_trans = Database::get_transaction($trans_id);

                $wpdb->update($table_trans, 
                    array('status' => $new_status), 
                    array('id' => $trans_id),
                    array('%s'),
                    array('%d')
                );

                Logger::log(sprintf('وضعیت تراکنش کد %d از %s به %s تغییر کرد.', $trans_id, $old_trans->status, $new_status), 'TRANSACTION_STATUS_UPDATE');

                // هماهنگی با سفارش ووکامرس در صورت متناظر بودن
                if ($old_trans && !empty($old_trans->order_id)) {
                    if (class_exists('WooCommerce')) {
                        $order = wc_get_order($old_trans->order_id);
                        if ($order) {
                            if ($new_status === 'approved') {
                                $order->update_status('processing', __('پرداخت کارت به کارت مشتری بررسی و تایید شد.', 'professional-card-to-card'));
                            } elseif ($new_status === 'rejected') {
                                $order->update_status('failed', __('پرداخت کارت به کارت مشتری رد شد. تراکنش نامعتبر است.', 'professional-card-to-card'));
                            } else {
                                $order->update_status('on-hold', __('تراکنش مجدداً به بررسی در انتظار منتقل شد.', 'professional-card-to-card'));
                            }
                        }
                    }
                }

                // هدایت مجدد جهت جلوگیری از تکرار صفحه با پیام
                wp_safe_redirect(add_query_arg(array('page' => 'p2p-gateway', 'p2p_msg' => 'status_updated'), admin_url('admin.php')));
                exit;
            }
        }

        // ۲. خروجی اکسل CSV از تراکنش‌ها
        if (isset($_POST['p2p_do_export_csv'])) {
            if (!isset($_POST['p2p_export_nonce']) || !wp_verify_nonce($_POST['p2p_export_nonce'], 'p2p_export_action')) {
                wp_die('ممیزی امنیتی خروجی مردود شد.');
            }

            $this->process_csv_export();
        }

        // ۳. پاکسازی کامل لاگ‌ها
        if (isset($_GET['action']) && $_GET['action'] === 'p2p_clear_logs') {
            if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'p2p_clear_logs_action')) {
                wp_die('خطای اعتبارسنجی نانس.');
            }

            Logger::clear();
            wp_safe_redirect(add_query_arg(array('page' => 'p2p-logs', 'p2p_msg' => 'logs_cleared'), admin_url('admin.php')));
            exit;
        }
    }

    /**
     * پردازش دانلود فایل اکسل CSV تراکنش ها با انکدینگ UTF-8 BOM
     */
    private function process_csv_export() {
        global $wpdb;
        $table_trans = $wpdb->prefix . 'c2c_transactions';

        // استخراج فیلدها و فیلترهای دلخواه
        $query = "SELECT * FROM $table_trans ORDER BY id DESC";
        $results = $wpdb->get_results($query, ARRAY_A);

        $filename = 'c2c-transactions-' . date('Y-m-d-H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // نوشتن BOM یونیکد در هدر جهت پیشگیری از بهم ریختگی فونت فارسی در اکسل
        fwrite($output, "\xEF\xBB\xBF");

        // عناوین ستون‌ها به زبان فارسی هماهنگ
        fputcsv($output, array(
            'شناسه',
            'شماره سفارش',
            'نام پرداخت کننده',
            'موبایل',
            'مبلغ (تومان)',
            'کد کارت مقصد',
            '۴ رقم آخر کارت فرستنده',
            'وضعیت پرداخت',
            'آدرس تصویر فیش',
            'IP کاربر',
            'تاریخ ثبت'
        ));

        if (!empty($results)) {
            foreach ($results as $row) {
                fputcsv($output, array(
                    $row['id'],
                    $row['order_id'] ? $row['order_id'] : 'خارج از سبد خرید',
                    $row['full_name'],
                    $row['mobile'],
                    $row['amount'],
                    $row['bank_card_id'],
                    $row['last4digits'],
                    $row['status'],
                    $row['receipt_url'],
                    $row['ip_address'],
                    $row['created_at']
                ));
            }
        }

        fclose($output);
        exit;
    }
}
