<?php
/**
 * Uninstall file for the Card To Card payment gateway.
 *
 * This file is run when the plugin is deleted from the WordPress admin.
 *
 * @package ProfessionalCardToCard
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$settings = get_option('p2p_settings');

if (isset($settings['delete_tables_on_uninstall']) && $settings['delete_tables_on_uninstall'] === 'yes') {
    global $wpdb;

    // Delete custom table
    $table_name = $wpdb->prefix . 'c2c_transactions';
    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");

    $cards_table = $wpdb->prefix . 'c2c_bank_cards';
    $wpdb->query("DROP TABLE IF EXISTS {$cards_table}");

    $logs_table = $wpdb->prefix . 'c2c_logs';
    $wpdb->query("DROP TABLE IF EXISTS {$logs_table}");

    // Delete upload folder contents recursively
    $upload_dir = wp_upload_dir()['basedir'] . '/p2p-receipts';
    if (file_exists($upload_dir)) {
        array_map('unlink', glob("$upload_dir/*.*"));
        rmdir($upload_dir);
    }
}

// Delete options
delete_option('p2p_settings');
delete_option('woocommerce_professional_card_to_card_settings');
