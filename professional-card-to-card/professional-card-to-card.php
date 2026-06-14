<?php
/**
 * Plugin Name: Professional Card To Card Payment Gateway
 * Plugin URI: https://wordpress.org/plugins/professional-card-to-card
 * Description: یک درگاه هوشمند، امن و حرفه‌ای برای کارت به کارت (کارت به کارت دستی) همراه با پنل مدیریت پیشرفته و ادغام کامل با ووکامرس.
 * Version: 1.0.0
 * Author: Senior Developer
 * Author URI: https://github.com/developer
 * Text Domain: professional-card-to-card
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.5
 *
 * @package ProfessionalCardToCard
 */

defined('ABSPATH') || exit;

// تعریف ثابت‌های عمومی افزونه
define('P2P_VERSION', '1.0.0');
define('P2P_FILE', __FILE__);
define('P2P_PATH', plugin_dir_path(__FILE__));
define('P2P_URL', plugin_dir_url(__FILE__));
define('P2P_UPLOAD_DIR', wp_upload_dir()['basedir'] . '/p2p-receipts');
define('P2P_UPLOAD_URL', wp_upload_dir()['baseurl'] . '/p2p-receipts');

// اتولودر اختصاصی فایل‌های کلاس افزونه
spl_autoload_register(function ($class) {
    $prefix = 'ProfessionalCardToCard\\';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $parts = explode('\\', $relative_class);
    
    // تبدیل نام کلاس به قالب فایل وردپرس (مثال: class-database.php)
    $class_base = strtolower(str_replace('_', '-', array_pop($parts)));
    if (strpos($class_base, 'class-') === 0) {
        $class_name = $class_base . '.php';
    } else {
        $class_name = 'class-' . $class_base . '.php';
    }
    
    // مسیر فرعی
    $subpath = '';
    if (!empty($parts)) {
        $subpath = implode('/', array_map('strtolower', $parts)) . '/';
    }

    $file = P2P_PATH . 'includes/' . $subpath . $class_name;

    if (file_exists($file)) {
        require_once $file;
    }
});

// اینیشیالایز کردن ماژول‌های اصلی
use ProfessionalCardToCard\Database;
use ProfessionalCardToCard\Admin;
use ProfessionalCardToCard\Payment_Handler;
use ProfessionalCardToCard\Security;
use ProfessionalCardToCard\Logger;

class Professional_Card_To_Card_Plugin {
    
    /**
     * سینگلتون نمونه
     */
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    /**
     * لود کردن هوک‌ها
     */
    private function init_hooks() {
        // هوک فعالسازی و غیرفعالسازی
        register_activation_hook(P2P_FILE, array($this, 'activate'));
        register_deactivation_hook(P2P_FILE, array($this, 'deactivate'));

        // لود کردن تکست دامین ترجمه
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // مقداردهی کلاس‌های اصلی پس از بارگذاری افزونه‌ها
        add_action('plugins_loaded', array($this, 'init_components'));

        // ثبت شورتکد
        add_shortcode('card_to_card_form', array($this, 'render_form_shortcode'));
    }

    /**
     * فعال‌سازی افزونه
     */
    public function activate() {
        // ایجاد پایگاه داده
        Database::create_tables();

        // ایجاد پوشه آپلود رسید
        if (!file_exists(P2P_UPLOAD_DIR)) {
            wp_mkdir_p(P2P_UPLOAD_DIR);
            
            // تولید فایل .htaccess جهت امنیت
            $htaccess_file = P2P_UPLOAD_DIR . '/.htaccess';
            if (!file_exists($htaccess_file)) {
                $rules = "Options -Indexes\n<Files *>\n  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n  <IfModule !mod_authz_core.c>\n    Order Allow,Deny\n    Deny from all\n  </IfModule>\n</Files>\n<FilesMatch \"\\.(jpe?g|png|webp)$\">\n  <IfModule mod_authz_core.c>\n    Require all granted\n  </IfModule>\n  <IfModule !mod_authz_core.c>\n    Order Deny,Allow\n    Allow from all\n  </IfModule>\n</FilesMatch>";
                file_put_contents($htaccess_file, $rules);
            }
        }

        // تنظیمات پیشفرت
        if (!get_option('p2p_settings')) {
            update_option('p2p_settings', array(
                'enable_receipt' => 'yes',
                'require_last4' => 'required',
                'enable_sms' => 'no',
                'enable_telegram' => 'no',
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => '',
                'theme' => 'glassmorphism',
                'email_template' => "با سلام،\nیک پرداخت کارت به کارت جدید ثبت شده است.\nنام خریدار: {full_name}\nشماره موبایل: {mobile}\nمبلغ: {amount} تومان\nشماره سفارش: {order_id}\nلطفا جهت تایید به پنل مدیریت مراجعه فرمایید.",
                'delete_tables_on_uninstall' => 'no'
            ));
        }

        Logger::log('Plugin activated successfully.');
    }

    /**
     * غیرفعالسازی افزونه
     */
    public function deactivate() {
        wp_clear_scheduled_hook('p2p_cleanup_expired_sessions');
        Logger::log('Plugin deactivated.');
    }

    /**
     * لود تکست دامین زبان
     */
    public function load_textdomain() {
        load_plugin_textdomain('professional-card-to-card', false, dirname(plugin_basename(P2P_FILE)) . '/languages');
    }

    /**
     * اینیشیالایز کامپوننت‌ها
     */
    public function init_components() {
        // ایجاد نمونه‌های تکین کلاس‌ها
        Database::get_instance();
        Security::get_instance();
        
        if (is_admin()) {
            Admin::get_instance();
        }

        Payment_Handler::get_instance();

        // بررسی فعال بودن ووکامرس جهت ادغام به عنوان روش پرداخت
        if (class_exists('WooCommerce')) {
            add_filter('woocommerce_payment_gateways', array($this, 'add_wc_gateway'));
        }
    }

    /**
     * اضافه کردن به درگاه‌های ووکامرس
     */
    public function add_wc_gateway($methods) {
        $methods[] = 'ProfessionalCardToCard\\class_woocommerce_gateway'; // توجه: ووکامرس نام مستقیم شی یا نام کلاس را با دابل اسلش تحویل می‌گیرد
        return $methods;
    }

    /**
     * رندر کوتاه کد فرم پرداخت مستقل
     */
    public function render_form_shortcode($atts) {
        return Payment_Handler::get_instance()->render_payment_form($atts);
    }
}

// ثبت راه‌اندازی فایل اصلی
function run_professional_card_to_card() {
    return Professional_Card_To_Card_Plugin::get_instance();
}

run_professional_card_to_card();
