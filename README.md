# IP Login Restriction

A WordPress plugin that restricts access to the login page by IP addresses. Administrators can whitelist their own IP via a secret key, which can only be changed once a day, ensuring an additional layer of security.

## Overview

- **WordPress Compatibility:** 5.0+
- **Requires PHP:** 7.0+
- **Version:** 1.5

This plugin blocks all login attempts from IP addresses not listed in the allowed IPs. Administrators can automatically whitelist their IP by following a unique URL with a secret key. This key can be rotated once per day, and a notification email will be sent to admin users when changed, containing detailed information and a direct link to whitelist IP addresses.

## Features

- **IP Whitelisting:** Only users from specific IP addresses can access the WordPress login page.
- **Secret Key for Whitelisting:** A secret key allows authorised users to whitelist their own IP by visiting a special URL.
- **Limited Daily Key Changes:** The secret key can only be changed once per day to prevent abuse.
- **Email Notifications:** Administrators receive email notifications when the secret key changes, with a full whitelist URL included.
- **Customisable Admin Settings:** Manage the allowed IP addresses, secret key, and notification recipients from the WordPress admin dashboard.

## Installation

1. **Upload the Plugin:**  
   Upload the plugin files to the `/wp-content/plugins/` directory, or install via the WordPress admin by navigating to **Plugins > Add New > Upload Plugin**.

2. **Activate the Plugin:**  
   In the WordPress admin, go to **Plugins**, find **IP Login Restriction**, and click **Activate**.

3. **Configure Settings:**  
   - Navigate to **Settings > Redfrog Restrict IP**.
   - Enter or modify the allowed IP addresses (one per line).
   - Enter or generate a secret key (only once per day).
   - Select which administrators should receive email notifications if the key changes.
   - Save your changes.

## Usage

### Whitelisting IPs

- Go to **Settings > Redfrog Restrict IP** and add your IPs in the **Allowed IPs** field. Make sure to save.

### Using the Secret Key

- The plugin generates (or you provide) a secret key used to whitelist IP addresses.
- Copy the link displayed in the plugin settings; this link is in the format:

  ```
  https://example.com/?key=YOUR_SECRET_KEY
  ```

- Anyone who has this link can whitelist their current IP by simply visiting it.

### Changing the Key

- You can change the secret key once per day. If you attempt to change it again the same day, the plugin will prompt you to try again tomorrow.

## How It Works

1. When a user or bot tries to load the `/wp-login.php` (or equivalent login URL), the plugin checks their IP against the allowed IPs.
2. If the IP is not on the list, access is denied.
3. If the IP is on the list, the user can proceed to the normal WordPress login flow.
4. If a user has the secret key (appended to the site URL as `?key=...`) and visits the site, their IP is automatically added to the allow list.

## Troubleshooting

### Email Notifications Not Working

- Make sure your WordPress installation can send emails.
- Check **Settings > General** for your site email, and verify your hosting/SMTP configuration.

### Locked Out?

- If you accidentally remove your own IP and can no longer access the site, update the `redfrog_allowed_admin_ips` option via the database (e.g., using phpMyAdmin) to add your IP back, or temporarily deactivate the plugin by renaming its folder.

### Multiple Admins

- Any admin user with access to **Settings > Redfrog Restrict IP** can edit the whitelist and secret key.

## Contributing

If you have suggestions or encounter any issues, please open an issue or submit a pull request on the GitHub repository. Your contributions are welcome!

## License

This plugin is licensed under the **GPLv2** or later.