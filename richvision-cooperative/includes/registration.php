<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode for the RichVision registration form.
 */
function richvision_registration_form() {
    ob_start();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['richvision_register'])) {
        richvision_handle_registration();
    }

    $referral_code = isset($_GET['referral_code']) ? sanitize_text_field($_GET['referral_code']) : '';

    ?>
    <form method="POST" action="">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <label for="referral_code">Referral Code (Optional)</label>
        <input type="text" id="referral_code" name="referral_code" value="<?php echo esc_attr($referral_code); ?>">

        <button type="submit" name="richvision_register">Register</button>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('richvision_registration', 'richvision_registration_form');

/**
 * Handle user registration.
 */
function richvision_handle_registration() {
    if (!isset($_POST['username'], $_POST['email'], $_POST['password'])) {
        return;
    }

    $username = sanitize_text_field($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = sanitize_text_field($_POST['password']);
    $referral_code = sanitize_text_field($_POST['referral_code']);

    // Check if the username or email already exists.
    if (username_exists($username) || email_exists($email)) {
        echo '<p>User already exists. Please try another username or email.</p>';
        return;
    }

    // Create the user.
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        echo '<p>Registration failed. Please try again.</p>';
        return;
    }

    // Handle referral logic.
    if (!empty($referral_code)) {
        global $wpdb;
        $referrer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} WHERE user_login = %s",
            $referral_code
        ));

        if ($referrer_id) {
            $wpdb->insert(
                $wpdb->prefix . 'richvision_referrals',
                [
                    'user_id' => $user_id,
                    'referrer_id' => $referrer_id
                ],
                ['%d', '%d']
            );
        }
    }

    // Redirect after successful registration.
    wp_redirect(home_url('/registration-success'));
    exit;
}
