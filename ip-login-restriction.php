<?php
/**
 * Plugin Name: IP Login Restriction
 * Description: Restrict access to the WordPress login screen by IP addresses. Only users from the stored IPs can log in, or they can whitelist themselves via a secret key.
 * Version: 1.9
 * Author: Dom Kapelewski
 */

if (  !  defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Add plugin options menu.
 */
add_action( 'admin_menu', 'redfrog_restrict_ip_menu' );
function redfrog_restrict_ip_menu() {
    add_options_page(
        'Restrict IP',
        'Redfrog Restrict IP',
        'manage_options',
        'restrict_ip',
        'restrict_ip_page'
    );
}

/**
 * Render the plugin settings page.
 */
function restrict_ip_page() {
    // Handle form submission.
    if ( isset( $_POST['submit'] ) ) {
        // Verify nonce first.
        if (
             !  isset( $_POST['redfrog_restrict_ip_nonce'] ) ||
            !  wp_verify_nonce( $_POST['redfrog_restrict_ip_nonce'], 'redfrog_restrict_ip_update' )
        ) {
            wp_die( 'Security check failed.' );
        }

        // Save Allowed IPs.
        $allowed_ips_text = sanitize_textarea_field( $_POST['allowed_ips'] );
        update_option( 'redfrog_allowed_admin_ips', $allowed_ips_text );

        // Check daily secret key change limit.
        $last_changed = get_option( 'redfrog_secret_key_last_changed', '' );
        $today        = date( 'Y-m-d' );

        if ( $last_changed !== $today ) {
            // Update the secret key and send emails.
            $new_secret_key = sanitize_text_field( $_POST['secret_key'] );
            update_option( 'redfrog_ip_add_secret_key', $new_secret_key );
            update_option( 'redfrog_secret_key_last_changed', $today );

            redfrog_send_admin_email_on_key_change( $new_secret_key );
        }
    }

    // Get existing settings.
    $allowed_ips  = get_option( 'redfrog_allowed_admin_ips', '' );
    $secret_key   = get_option( 'redfrog_ip_add_secret_key', '' );
    $last_changed = get_option( 'redfrog_secret_key_last_changed', '' );
    $today        = date( 'Y-m-d' );
    $is_editable  = ( $last_changed !== $today );
    $key_url      = esc_url( home_url( '/' ) ) . '?key=' . $secret_key;

    // Fetch admin emails to display in the form (all administrators).
    $admins = get_users( ['role' => 'administrator'] );
    ?>
<div class="wrap" style="font-family: Arial, sans-serif; max-width: 800px;">
    <h2 style="border-bottom: 1px solid #ccc; padding-bottom: 10px;">Restrict Admin Access by IP</h2>
    <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; border-radius: 5px; margin-bottom: 20px;">
        <p><strong>Instructions:</strong></p>
        <ul style="margin: 0; padding-left: 20px; color: #333;">
            <li>Add allowed IP addresses in the <strong>Allowed IPs</strong> field, one per line.</li>
            <li>The <strong>Secret Key</strong> can be used to add your current IP address via the query string.</li>
            <li>You can only change the secret key once per day.</li>
            <li>When the Secret Key is changed, emails will be sent to the selected admin users.</li>
            <li>
                Use the following link to whitelist your current IP:
                <div style="margin-top: 5px; padding: 5px; background: #f3f3f3; border: 1px solid #ddd; border-radius: 5px; display: inline-block;">
                    <code id="redfrog_dynamic_url"><?php echo esc_html( $key_url ); ?></code>
                    <button id="copy_to_clipboard" style="margin-left: 10px; padding: 5px 10px; background: #0073aa; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
                        Copy to Clipboard
                    </button>
                </div>
            </li>
        </ul>
    </div>
    <form method="post" action="" style="margin-bottom: 30px;">
        <?php wp_nonce_field( 'redfrog_restrict_ip_update', 'redfrog_restrict_ip_nonce' ); ?>
        <table class="form-table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <th scope="row" style="text-align: left; width: 200px; padding: 10px; vertical-align: top;">
                    Allowed IPs
                </th>
                <td style="padding: 10px;">
                    <textarea name="allowed_ips" rows="5" cols="50" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px;"><?php echo esc_textarea( $allowed_ips ); ?></textarea>
                    <p class="description" style="color: #555;">Enter one IP address per line.</p>
                </td>
            </tr>
            <tr>
                <th scope="row" style="text-align: left; width: 200px; padding: 10px; vertical-align: top;">
                    Secret Key
                </th>
                <td style="padding: 10px;">
                    <input type="text" name="secret_key" id="redfrog_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" <?php echo $is_editable ? '' : 'readonly'; ?> style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; background-color: <?php echo $is_editable ? '#fff' : '#e9e9e9'; ?>;">
                    <?php if ( $is_editable ): ?>
                    <button type="button" id="redfrog_generate_key" class="button" style="margin-top: 10px; background: #0073aa; color: #fff; border: none; padding: 8px 12px; border-radius: 5px; cursor: pointer;">
                        Generate Key
                    </button>
                    <?php else: ?>
                    <p class="description" style="color: #555;">You can only change the key once per day. Try again tomorrow.</p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row" style="text-align: left; width: 200px; padding: 10px; vertical-align: top;">
                    Admin Email Notifications
                </th>
                <td style="padding: 10px;">
                    <?php foreach ( $admins as $admin ): ?>
                    <label style="margin-bottom:10px; display:block">
                        <input type="checkbox" name="admin_emails[]" value="<?php echo esc_attr( $admin->user_email ); ?>" checked>
                        <?php echo esc_html( $admin->user_email ); ?>
                    </label>
                    <?php endforeach; ?>
                    <p class="description" style="color: #555; font-size:12px; margin-top:10px;">
                        Emails will be sent to the selected administrators when the secret key changes.
                    </p>
                </td>
            </tr>
        </table>
        <input type="submit" name="submit" id="redfrog_submit" class="button button-primary" value="Save Changes" style="background: #0073aa; color: #fff; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
    </form>
</div>

<!-- JavaScript for generating key and copying URL -->
<script>
// Generate a new key (front-end JS)
document.getElementById('redfrog_generate_key')?.addEventListener('click', function() {
    const randomKey = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    document.getElementById('redfrog_secret_key').value = randomKey;
    const dynamicUrl = '<?php echo esc_url( home_url( '/' ) ); ?>?key=' + randomKey;
    document.getElementById('redfrog_dynamic_url').textContent = dynamicUrl;
});

// Copy to clipboard functionality
document.getElementById('copy_to_clipboard')?.addEventListener('click', function() {
    const urlElement = document.getElementById('redfrog_dynamic_url');
    const url = urlElement.textContent;

    // Create a temporary textarea element
    const tempTextarea = document.createElement('textarea');
    tempTextarea.value = url;
    document.body.appendChild(tempTextarea);

    // Select the content and copy
    tempTextarea.select();
    document.execCommand('copy');

    // Remove the temporary element
    document.body.removeChild(tempTextarea);

    // Show confirmation
    alert('URL copied to clipboard!');
});
</script>
<?php
}

/**
 * Hook into the login process to restrict IPs.
 */
add_action( 'init', 'redfrog_check_ip_restriction' );
function redfrog_check_ip_restriction() {
    // Retrieve the stored IPs.
    $allowed_ips_text  = get_option( 'redfrog_allowed_admin_ips', '' );
    $allowed_ips_array = array_filter( array_map( 'trim', explode( "\n", $allowed_ips_text ) ) );

    // Get the current user's IP.
    $current_ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';

    // Get the existing secret key from the database.
    $secret_key = get_option( 'redfrog_ip_add_secret_key', '' );
    $get_key    = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';

    // If the query key matches our secret key, whitelist the current IP.
    if (  !  empty( $get_key ) && $get_key === $secret_key ) {
        if (  !  in_array( $current_ip, $allowed_ips_array, true ) ) {
            $allowed_ips_array[] = $current_ip;
            update_option( 'redfrog_allowed_admin_ips', implode( "\n", $allowed_ips_array ) );
        }

        // Redirect to the login page after adding the IP
        wp_redirect( wp_login_url() );
        exit;
    }

    // Avoid redirecting on the homepage or admin-ajax.php
    if ( is_front_page() || is_home() || defined( 'DOING_AJAX' ) ) {
        return;
    }

    // Block access to wp-admin and login if IP is not whitelisted
    if (  !  in_array( $current_ip, $allowed_ips_array, true ) ) {
        if ( is_admin() || in_array( $GLOBALS['pagenow'], ['wp-login.php', 'wp-admin'] ) ) {
            wp_redirect( home_url() );
            exit;
        }
    }
}

/**
 * Send an informative email on key change.
 */
function redfrog_send_admin_email_on_key_change( $new_key ) {
    // Sanitise admin emails from checkboxes (defensive).
    $admin_emails_raw = isset( $_POST['admin_emails'] ) ? $_POST['admin_emails'] : [];
    $admin_emails     = array_map( 'sanitize_email', $admin_emails_raw );

    $site_name = get_bloginfo( 'name' );
    $site_url  = home_url();
    // Create a URL to whitelist the IP (using the same logic we have for the plugin front-end).
    $full_url = add_query_arg( 'key', $new_key, home_url( '/' ) );

    $subject = 'Secret Key Changed';
    $message = "Hello Admin,\n\n"
        . "The secret key for IP restriction on '$site_name' has been updated. Below are the details:\n\n"
        . "• New Secret Key: $new_key\n"
        . "• Full Whitelist URL: $full_url\n\n"
        . "Please use the URL above if your IP address is not currently whitelisted.\n"
        . "Once visited, it will be added to the allowed IP list.\n\n"
        . "Best regards,\n"
        . "The Team at $site_name\n"
        . "$site_url\n";

    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    foreach ( $admin_emails as $email ) {
        $sent = wp_mail( $email, $subject, $message, $headers );
        if (  !  $sent ) {
            // If sending fails, send error notice to your debug address.
            $user_email  = wp_get_current_user()->user_email;
            $admin_email = 'info@redfrogstudio.co.uk'; // Your debug address

            $error_subject = 'Error Sending Secret Key Notification';
            $error_message = "There was an error sending the new secret key to the selected admin.\n\n"
                . "Website Name: $site_name\n"
                . "Website URL: $site_url\n"
                . "User Email: $user_email\n"
                . "Failed Recipient: $email\n"
                . "New Key: $new_key\n\n"
                . "Please check the email configuration.";

            wp_mail( $admin_email, $error_subject, $error_message, $headers );
        }
    }
}

/**
 * (Optional) Clean up database entries on uninstall.
 */
register_uninstall_hook( __FILE__, 'redfrog_uninstall' );
function redfrog_uninstall() {
    delete_option( 'redfrog_allowed_admin_ips' );
    delete_option( 'redfrog_ip_add_secret_key' );
    delete_option( 'redfrog_secret_key_last_changed' );
}

/**
 * Check GitHub plugin updates and populate the transient if a new version is available.
 */
add_filter( 'site_transient_update_plugins', 'check_github_plugin_update' );

function check_github_plugin_update( $transient ) {
    // Run only in the admin area to avoid frontend errors.
    if (  !  is_admin() ) {
        return $transient;
    }

    // Ensure the function get_plugin_data() is available.
    if (  !  function_exists( 'get_plugin_data' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    $plugin_slug     = plugin_basename( __FILE__ );
    $plugin_data     = get_plugin_data( __FILE__ );
    $current_version = $plugin_data['Version'];
    $github_username = 'dompl';
    $repo_name       = 'ip-login-restriction';
    $access_token    = defined( 'GITHUB_ACCESS_TOKEN' ) ? GITHUB_ACCESS_TOKEN : '';

    // Fetch the latest release info from GitHub.
    $remote_info = wp_remote_get(
        "https://api.github.com/repos/{$github_username}/{$repo_name}/releases/latest",
        [
            'headers' => [
                'Authorization' => 'token ' . $access_token
            ]
        ]
    );

    if ( is_wp_error( $remote_info ) ) {
        return $transient;
    }

    $remote_info = json_decode( wp_remote_retrieve_body( $remote_info ) );

    if (  !  is_object( $remote_info ) || !  isset( $remote_info->tag_name ) ) {
        return $transient;
    }

    if ( version_compare( $current_version, $remote_info->tag_name, '<' ) ) {
        $plugin = [
            'slug'        => $plugin_slug,
            'new_version' => $remote_info->tag_name,
            'url'         => $remote_info->html_url,
            'package'     => $remote_info->zipball_url
        ];

        $transient->response[$plugin_slug] = (object) $plugin;
    }

    return $transient;
}