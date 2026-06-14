<?php
namespace ProfessionalCardToCard;

defined('ABSPATH') || exit;

class Logger {

    /**
     * ایجاد جدول لاگ‌ها
     */
    public static function create_log_table() {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'c2c_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event varchar(100) NOT NULL,
            message text NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * درج لاگ جدید
     */
    public static function log($message, $event = 'GENERAL') {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'c2c_logs';

        // تلاش برای ساخت خودکار جدول در صورت نبودن
        self::create_log_table();

        $ip = !empty($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
        $user_id = get_current_user_id() ? get_current_user_id() : 0;

        $wpdb->insert($table_logs, array(
            'event' => sanitize_text_field($event),
            'message' => sanitize_textarea_field($message),
            'ip_address' => $ip,
            'user_id' => $user_id
        ));

        // نوشتن در فایل فیزیکی لاگ وردپرس جهت امنیت مضاعف
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log(sprintf('[CardToCard - %s] %s (IP: %s)', $event, $message, $ip));
        }
    }

    /**
     * دریافت لیست لاگ‌ها
     */
    public static function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'c2c_logs';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_logs ORDER BY id DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    /**
     * دریافت تعداد کل لاگ‌ها
     */
    public static function get_total_count() {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'c2c_logs';
        return $wpdb->get_var("SELECT COUNT(*) FROM $table_logs");
    }

    /**
     * پاکسازی لاگ‌ها
     */
    public static function clear() {
        global $wpdb;
        $table_logs = $wpdb->prefix . 'c2c_logs';
        $wpdb->query("TRUNCATE TABLE $table_logs");
        self::log('تمامی لاگ‌های سیستمی پاکسازی شدند.', 'SYSTEM_CLEANUP');
    }
}
