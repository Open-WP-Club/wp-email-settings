<?php
// If uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
  die;
}

// Delete options
delete_option('wp_email_settings_options');
delete_option('wp_email_settings_failed_attempts');

// Remove the log file
$log_file = WP_CONTENT_DIR . '/wp-email-settings-log.txt';
if (file_exists($log_file)) {
  unlink($log_file);
}

// Clear any transients (if you decide to use them in the future)
delete_transient('wp_email_settings_transient');
