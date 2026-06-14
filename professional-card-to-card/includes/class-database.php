<?php
namespace ProfessionalCardToCard;

defined('ABSPATH') || exit;

class Database {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // باز گذاشتن کدهای سازنده در صورت لزوم جهت فیلترها
    }

    /**
     * متد ایجاد جدول‌های دیتابیس با dbDelta
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // ۱. جدول تراکنش‌ها
        $table_transactions = $wpdb->prefix . 'c2c_transactions';
        $sql1 = "CREATE TABLE $table_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            full_name varchar(100) NOT NULL,
            mobile varchar(15) NOT NULL,
            amount decimal(15,2) NOT NULL,
            bank_card_id bigint(20) NOT NULL,
            last4digits varchar(4) DEFAULT '',
            receipt_url varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'pending',
            admin_notes text DEFAULT '',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ip_address varchar(45) DEFAULT '',
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // ۲. جدول کارت‌های بانکی
        $table_cards = $wpdb->prefix . 'c2c_bank_cards';
        $sql2 = "CREATE TABLE $table_cards (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            card_number varchar(20) NOT NULL,
            bank_name varchar(50) NOT NULL,
            holder_name varchar(100) NOT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta($sql1);
        dbDelta($sql2);

        // درج پیش‌فرض کارت‌های بانکی برای خالی نبودن افزونه
        $cards_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_cards");
        if ($cards_count == 0) {
            $wpdb->insert($table_cards, array(
                'card_number' => '6037991122334455',
                'bank_name' => 'بانک ملی ایران',
                'holder_name' => 'ابوالفضل محمدی',
                'active' => 1
            ));
            $wpdb->insert($table_cards, array(
                'card_number' => '6104337788990011',
                'bank_name' => 'بانک ملت',
                'holder_name' => 'زهرا سادات مرعشی',
                'active' => 1
            ));
        }
    }

    /**
     * دریافت لیست کارت‌های فعال
     */
    public static function get_active_cards() {
        global $wpdb;
        $table = $wpdb->prefix . 'c2c_bank_cards';
        return $wpdb->get_results("SELECT * FROM $table WHERE active = 1 ORDER BY id DESC");
    }

    /**
     * دریافت نقشه کارت بر اساس شناسه
     */
    public static function get_card($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'c2c_bank_cards';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    /**
     * دریافت تک تراکنش بر اساس شناسه به همراه کارت بانکی
     */
    public static function get_transaction($id) {
        global $wpdb;
        $trans_table = $wpdb->prefix . 'c2c_transactions';
        $cards_table = $wpdb->prefix . 'c2c_bank_cards';
        
        $query = "SELECT t.*, c.card_number, c.bank_name, c.holder_name 
                  FROM $trans_table t 
                  LEFT JOIN $cards_table c ON t.bank_card_id = c.id 
                  WHERE t.id = %d";
                  
        return $wpdb->get_row($wpdb->prepare($query, $id));
    }
}
