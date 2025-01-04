<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create required database tables for the RichVision Cooperative plugin.
 */
function richvision_coop_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    // Table for referrals
    $table_referrals = $wpdb->prefix . 'richvision_referrals';
    $sql_referrals = "CREATE TABLE IF NOT EXISTS $table_referrals (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        referrer_id BIGINT UNSIGNED NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
        FOREIGN KEY (referrer_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
    ) $charset_collate;";

    // Table for savings
    $table_savings = $wpdb->prefix . 'richvision_savings';
    $sql_savings = "CREATE TABLE IF NOT EXISTS $table_savings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        package_name VARCHAR(50) NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
    ) $charset_collate;";

    // Table for wallets
    $table_wallets = $wpdb->prefix . 'richvision_wallets';
    $sql_wallets = "CREATE TABLE IF NOT EXISTS $table_wallets (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        wallet_type ENUM('savings', 'total') NOT NULL,
        balance DECIMAL(10, 2) NOT NULL DEFAULT 0,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
    ) $charset_collate;";

    // Table for commissions
    $table_commissions = $wpdb->prefix . 'richvision_commissions';
    $sql_commissions = "CREATE TABLE IF NOT EXISTS $table_commissions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        level INT NOT NULL,
        type ENUM('registration', 'savings') NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'paid') NOT NULL DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
    ) $charset_collate;";

    // Table for loans
    $table_loans = $wpdb->prefix . 'richvision_loans';
    $sql_loans = "CREATE TABLE IF NOT EXISTS $table_loans (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT UNSIGNED NOT NULL,
        loan_type ENUM('80%', '200%') NOT NULL,
        loan_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'approved', 'repaid') NOT NULL DEFAULT 'pending',
        repayment_start_date DATE DEFAULT NULL,
        repayment_end_date DATE DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
    ) $charset_collate;";

    // Execute SQL queries.
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta([$sql_referrals, $sql_savings, $sql_wallets, $sql_commissions, $sql_loans]);
}
