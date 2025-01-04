<?php
/*
Plugin Name: RichVision Cooperative
Description: A plugin to manage RichVision Cooperative functionalities, including registration, savings, wallet, MLM, loans, vouchers, and rankings.
Version: 3.0
Author: Fidean Technologies
Author URI: https://fideantech.com
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Activation Hook
function richvision_activate() {
    global $wpdb;

    $vouchers_table = $wpdb->prefix . 'richvision_vouchers';
    $savings_table = $wpdb->prefix . 'richvision_savings';
    $referrals_table = $wpdb->prefix . 'richvision_referrals';
    $commissions_table = $wpdb->prefix . 'richvision_commissions';
    $charset_collate = $wpdb->get_charset_collate();

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create Vouchers Table
    $sql_vouchers = "CREATE TABLE IF NOT EXISTS $vouchers_table (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(20) NOT NULL UNIQUE,
        type ENUM('registration', 'savings') NOT NULL,
        plan VARCHAR(20) NOT NULL,
        value DECIMAL(10, 2) NOT NULL,
        status ENUM('unused', 'used') DEFAULT 'unused',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) $charset_collate;";
    dbDelta($sql_vouchers);

    // Create Other Tables (Savings, Referrals, Commissions)
    // Placeholder for additional table creation
}
register_activation_hook(__FILE__, 'richvision_activate');

// Admin Menu for Settings
function richvision_admin_menu() {
    add_menu_page(
        'RichVision Settings',
        'RichVision Settings',
        'manage_options',
        'richvision-settings',
        'richvision_settings_page',
        'dashicons-admin-generic',
        100
    );

    add_submenu_page(
        'richvision-settings',
        'Voucher Generation',
        'Voucher Generation',
        'manage_options',
        'richvision-vouchers',
        'richvision_voucher_page'
    );

    add_submenu_page(
        'richvision-settings',
        'WooCommerce Mapping',
        'WooCommerce Mapping',
        'manage_options',
        'richvision-mapping',
        'richvision_mapping_page'
    );
}
add_action('admin_menu', 'richvision_admin_menu');

// Admin Settings Page
function richvision_settings_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registration_fee'])) {
        update_option('richvision_registration_fee', sanitize_text_field($_POST['registration_fee']));
    }

    $registration_fee = get_option('richvision_registration_fee', 2500);

    echo '<h2>RichVision Settings</h2>';
    echo '<form method="post">';
    echo '<label for="registration_fee">Registration Fee (₦):</label>';
    echo '<input type="number" name="registration_fee" id="registration_fee" value="' . esc_attr($registration_fee) . '" required>';
    echo '<button type="submit">Save Settings</button>';
    echo '</form>';
}



// Admin Menu for Voucher Management
function richvision_admin_voucher_menu() {
    add_submenu_page(
        'richvision-settings',
        'Voucher Management',
        'Vouchers',
        'manage_options',
        'richvision-vouchers',
        'richvision_voucher_page'
    );
}
add_action('admin_menu', 'richvision_admin_voucher_menu');

// Voucher Management Page
function richvision_voucher_page() {
    global $wpdb;
    $vouchers_table = $wpdb->prefix . 'richvision_vouchers';

    // Handle Voucher Generation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['voucher_type'])) {
        $voucher_type = sanitize_text_field($_POST['voucher_type']);
        $voucher_value = floatval($_POST['voucher_value']);
        $voucher_quantity = intval($_POST['voucher_quantity']);
        $generated_vouchers = [];

        for ($i = 0; $i < $voucher_quantity; $i++) {
            $code = strtoupper(wp_generate_password(10, false, false));
            $wpdb->insert(
                $vouchers_table,
                [
                    'code' => $code,
                    'type' => $voucher_type,
                    'value' => $voucher_value,
                    'status' => 'unused',
                ]
            );
            $generated_vouchers[] = $code;
        }

        echo '<div class="notice notice-success"><p>' . $voucher_quantity . ' vouchers successfully generated!</p></div>';
        if (!empty($generated_vouchers)) {
            echo '<p><strong>Generated Voucher Codes:</strong></p><ul>';
            foreach ($generated_vouchers as $code) {
                echo '<li>' . $code . '</li>';
            }
            echo '</ul>';
        }
    }

    // Handle Voucher Filtering
    $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';

    // Build query to fetch vouchers based on filters
    $query = "SELECT * FROM $vouchers_table WHERE 1=1";
    if (!empty($filter_type)) {
        $query .= $wpdb->prepare(" AND type = %s", $filter_type);
    }
    if (!empty($filter_status)) {
        $query .= $wpdb->prepare(" AND status = %s", $filter_status);
    }

    // Execute query
    $vouchers = $wpdb->get_results($query);

    // Admin Page Layout
    ?>
    <div class="wrap">
        <h1>Voucher Management</h1>

        <!-- Voucher Generation Form -->
        <h2>Generate Vouchers</h2>
        <form method="post" style="margin-bottom: 30px;">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="voucher_type">Voucher Type:</label></th>
                    <td>
                        <select name="voucher_type" id="voucher_type" required>
                            <option value="registration">Registration</option>
                            <option value="savings">Savings</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="voucher_value">Voucher Value (₦):</label></th>
                    <td><input type="number" name="voucher_value" id="voucher_value" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="voucher_quantity">Quantity:</label></th>
                    <td><input type="number" name="voucher_quantity" id="voucher_quantity" required></td>
                </tr>
            </table>
            <p><button type="submit" class="button button-primary">Generate Vouchers</button></p>
        </form>

        <!-- Voucher History -->
        <h2>Voucher History</h2>
        <form method="get" style="margin-bottom: 20px;">
            <input type="hidden" name="page" value="richvision-vouchers">
            <label for="filter_type">Filter by Type:</label>
            <select name="filter_type" id="filter_type">
                <option value="">All</option>
                <option value="registration" <?= $filter_type === 'registration' ? 'selected' : ''; ?>>Registration</option>
                <option value="savings" <?= $filter_type === 'savings' ? 'selected' : ''; ?>>Savings</option>
            </select>
            <label for="filter_status">Filter by Status:</label>
            <select name="filter_status" id="filter_status">
                <option value="">All</option>
                <option value="unused" <?= $filter_status === 'unused' ? 'selected' : ''; ?>>Unused</option>
                <option value="used" <?= $filter_status === 'used' ? 'selected' : ''; ?>>Used</option>
            </select>
            <button type="submit" class="button">Filter</button>
        </form>

        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value (₦)</th>
                    <th>Status</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($vouchers)) : ?>
                    <?php foreach ($vouchers as $voucher) : ?>
                        <tr>
                            <td><?= esc_html($voucher->id); ?></td>
                            <td><?= esc_html($voucher->code); ?></td>
                            <td><?= ucfirst(esc_html($voucher->type)); ?></td>
                            <td>₦<?= number_format($voucher->value, 2); ?></td>
                            <td><?= ucfirst(esc_html($voucher->status)); ?></td>
                            <td><?= esc_html($voucher->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6">No vouchers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}


// WooCommerce Product Mapping Page
function richvision_mapping_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_mapping'])) {
        update_option('richvision_woocommerce_mapping', $_POST['product_mapping']);
    }

    $mapping = get_option('richvision_woocommerce_mapping', []);
    $plans = ['alpha', 'bravo', 'delta', 'gold', 'platinum'];

    echo '<h2>WooCommerce Product Mapping</h2>';
    echo '<form method="post">';
    
    echo '<h3>Savings Plan Product IDs</h3>';
    foreach ($plans as $plan) {
        echo '<label for="savings_product_' . $plan . '">' . ucfirst($plan) . ' Plan Product ID:</label>';
        echo '<input type="number" name="product_mapping[savings][' . $plan . ']" id="savings_product_' . $plan . '" value="' . esc_attr($mapping['savings'][$plan] ?? '') . '">';
    }

    echo '<h3>Registration Plan Product IDs</h3>';
    foreach ($plans as $plan) {
        echo '<label for="registration_product_' . $plan . '">' . ucfirst($plan) . ' Registration Plan Product ID:</label>';
        echo '<input type="number" name="product_mapping[registration][' . $plan . ']" id="registration_product_' . $plan . '" value="' . esc_attr($mapping['registration'][$plan] ?? '') . '">';
    }

    echo '<button type="submit">Save Mapping</button>';
    echo '</form>';
}

// Shortcode to Display Registration Form
function richvision_registration_shortcode() {
    ob_start();
    richvision_registration_page();
    return ob_get_clean();
}
add_shortcode('richvision_registration', 'richvision_registration_shortcode');

// Registration Page Logic
function richvision_registration_page() {
    global $wpdb;

    // Constants
    $registration_fee = 2500; // Default registration fee
    $savings_plans = [
        ['id' => 1, 'alpha' => 'Plan A', 'amount' => 5000],
        ['id' => 2, 'bravo' => 'Plan B', 'amount' => 10000],
        ['id' => 3, 'delta' => 'Plan C', 'amount' => 20000],
        ['id' => 4, 'gold' => 'Plan D', 'amount' => 50000],
        ['id' => 5, 'platinum' => 'Plan E', 'amount' => 100000],
    ];

    $success = false;
    $errors = [];

    // Handle Form Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['richvision_register'])) {
        // Sanitize Inputs
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $password = sanitize_text_field($_POST['password']);
        $confirm_password = sanitize_text_field($_POST['confirm_password']);
        $savings_plan_id = intval($_POST['savings_plan']);
        $referral_code = sanitize_text_field($_POST['referral_code']);
        $payment_method = sanitize_text_field($_POST['payment_method']);
        $voucher_code = sanitize_text_field($_POST['voucher_code'] ?? '');

        // Validate Inputs
        if (empty($username)) $errors[] = 'Username is required.';
        if (empty($email) || !is_email($email)) $errors[] = 'A valid email is required.';
        else {
            $existing_user = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}users WHERE user_email = %s", $email));
            if ($existing_user > 0) $errors[] = 'Email is already registered.';
        }
        if (empty($phone)) $errors[] = 'Phone number is required.';
        if (empty($password) || $password !== $confirm_password) $errors[] = 'Passwords must match.';
        if (empty($savings_plan_id)) $errors[] = 'Please select a savings plan.';
        if (empty($payment_method)) $errors[] = 'Please select a payment method.';
        if ($payment_method === 'voucher' && empty($voucher_code)) $errors[] = 'Voucher code is required for voucher payment.';

        // Validate Savings Plan
        $savings_plan = array_filter($savings_plans, function ($plan) use ($savings_plan_id) {
            return $plan['id'] === $savings_plan_id;
        });
        $savings_plan = reset($savings_plan);
        if (!$savings_plan) $errors[] = 'Invalid savings plan selected.';

        // Validate Voucher Code
        if ($payment_method === 'voucher' && !empty($voucher_code)) {
            $voucher = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}richvision_vouchers WHERE code = %s AND status = 'unused'", $voucher_code));
            if (!$voucher) $errors[] = 'Invalid or already used voucher code.';
        }

        // Calculate Total Fee
        $total_fee = $registration_fee + ($savings_plan ? $savings_plan['amount'] : 0);

        // Process Payment and Register User
        if (empty($errors)) {
            if ($payment_method === 'woocommerce') {
                // WooCommerce Redirect
                $product_id = 123; // Replace with WooCommerce product ID
                wp_redirect(add_query_arg(['add-to-cart' => $product_id], wc_get_cart_url()));
                exit;
            } elseif ($payment_method === 'voucher') {
                // Mark Voucher as Used
                $wpdb->update("{$wpdb->prefix}richvision_vouchers", ['status' => 'used'], ['code' => $voucher_code]);
            }

            // Register User
            $hashed_password = wp_hash_password($password);
            $wpdb->insert("{$wpdb->prefix}richvision_users", [
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'password' => $hashed_password,
                'savings_plan_id' => $savings_plan_id,
                'referral_code' => uniqid('ref_'),
                'referred_by' => $referral_code,
                'created_at' => current_time('mysql')
            ]);

            $success = true;
        }
    }

    // Registration Form
    ?>
    <div class="registration-form">
        <h1>Register</h1>
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <ul><?php foreach ($errors as $error) echo '<li>' . esc_html($error) . '</li>'; ?></ul>
            </div>
        <?php elseif ($success): ?>
            <div class="success">Registration successful! Please proceed to payment if required.</div>
        <?php endif; ?>

        <form method="POST">
            <label>Username:</label>
            <input type="text" name="username" required>

            <label>Email Address:</label>
            <input type="email" name="email" required>

            <label>Phone Number:</label>
            <input type="text" name="phone" required>

            <label>Password:</label>
            <input type="password" name="password" required>

            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>

            <label>Savings Plan:</label>
            <select name="savings_plan" required>
                <option value="">-- Select Plan --</option>
                <?php foreach ($savings_plans as $plan): ?>
                    <option value="<?= $plan['id']; ?>" data-amount="<?= $plan['amount']; ?>">
                        <?= esc_html($plan['name']); ?> (₦<?= number_format($plan['amount']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Referral Code (Optional):</label>
            <input type="text" name="referral_code">

            <label>Payment Method:</label>
            <select name="payment_method" id="payment_method" required>
                <option value="">-- Select Payment Method --</option>
                <option value="woocommerce">WooCommerce</option>
                <option value="voucher">Voucher</option>
            </select>

            <div id="voucher-field" style="display: none;">
                <label>Voucher Code:</label>
                <input type="text" name="voucher_code">
            </div>

            <div id="total-fee">
                <strong>Total Fee: ₦<?= number_format($registration_fee); ?></strong>
            </div>

            <button type="submit" name="richvision_register">Register</button>
        </form>
    </div>

    <script>
        // Update Total Fee Based on Savings Plan
        document.querySelector('select[name="savings_plan"]').addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            const planAmount = parseFloat(selectedOption.getAttribute('data-amount')) || 0;
            const registrationFee = <?= $registration_fee; ?>;
            const totalFee = registrationFee + planAmount;
            document.getElementById('total-fee').innerHTML = '<strong>Total Fee: ₦' + totalFee.toLocaleString() + '</strong>';
        });

        // Toggle Voucher Field
        document.querySelector('select[name="payment_method"]').addEventListener('change', function () {
            const isVoucher = this.value === 'voucher';
            document.getElementById('voucher-field').style.display = isVoucher ? 'block' : 'none';
        });
    </script>
    <?php
}



// Savings Plan Form (Shortcode)
function richvision_savings_form() {
    ob_start();

    // Fetch the current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        echo '<p>Please <a href="' . wp_login_url() . '">log in</a> to select a savings plan.</p>';
        return ob_get_clean();
    }

    // Fetch existing user savings plan
    $current_plan = get_user_meta($user_id, 'richvision_savings_plan', true);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['savings_package'])) {
        $savings_package = sanitize_text_field($_POST['savings_package']);
        $savings_plans = [
            'alpha' => 5000,
            'bravo' => 10000,
            'delta' => 20000,
            'gold' => 50000,
            'platinum' => 100000
        ];

        // Validate the selected package
        if (array_key_exists($savings_package, $savings_plans)) {
            // Save the selected savings plan for the user
            update_user_meta($user_id, 'richvision_savings_plan', $savings_package);

            echo '<p>Your savings plan has been updated to <strong>' . ucfirst($savings_package) . '</strong> (₦' . number_format($savings_plans[$savings_package]) . ').</p>';
        } else {
            echo '<p style="color: red;">Invalid savings plan selected. Please try again.</p>';
        }
    }

    ?>
    <form id="richvision-savings-form" method="post">
        <h2>Select Savings Plan</h2>
        <p>
            <label for="savings-package">Choose a Package:</label>
            <select name="savings_package" id="savings-package" required>
                <option value="">-- Select Savings Plan --</option>
                <option value="alpha" <?= $current_plan === 'alpha' ? 'selected' : ''; ?>>Alpha - ₦5,000</option>
                <option value="bravo" <?= $current_plan === 'bravo' ? 'selected' : ''; ?>>Bravo - ₦10,000</option>
                <option value="delta" <?= $current_plan === 'delta' ? 'selected' : ''; ?>>Delta - ₦20,000</option>
                <option value="gold" <?= $current_plan === 'gold' ? 'selected' : ''; ?>>Gold - ₦50,000</option>
                <option value="platinum" <?= $current_plan === 'platinum' ? 'selected' : ''; ?>>Platinum - ₦100,000</option>
            </select>
        </p>
        <p>
            <button type="submit" class="btn-submit">Save Plan</button>
        </p>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('richvision_savings', 'richvision_savings_form');

// Add CSS Styling
function richvision_custom_styles() {
    ?>
    <style>
        form {
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        form h2 {
            text-align: center;
            color: #333;
        }
        form label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        form input,
        form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        form button {
            display: block;
            width: 100%;
            padding: 10px;
            background: #0073aa;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-align: center;
        }
        form button:hover {
            background: #005f8c;
        }
    </style>
    <?php
}
add_action('wp_head', 'richvision_custom_styles');
