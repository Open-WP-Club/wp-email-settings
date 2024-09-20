<?php

/**
 * Plugin Name: WP Email Stopper
 * Plugin URI: http://example.com/wp-email-stopper
 * Description: A plugin to stop typical WordPress email addresses
 * Version: 1.0
 * Author: Your Name
 * Author URI: http://example.com
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

class WP_Email_Stopper
{

  public function __construct()
  {
    add_action('init', array($this, 'init'));
  }

  public function init()
  {
    // Disable all emails
    add_filter('wp_mail', array($this, 'disable_all_emails'), 10, 1);

    // Disable specific email notifications
    add_filter('auto_core_update_send_email', '__return_false');
    add_filter('auto_plugin_update_send_email', '__return_false');
    add_filter('auto_theme_update_send_email', '__return_false');
    add_filter('send_password_change_email', '__return_false');
    add_filter('send_email_change_email', '__return_false');
    add_filter('wp_new_user_notification_email_admin', '__return_false');
    add_filter('wp_new_user_notification_email', '__return_false');

    // Disable comment notification emails
    add_filter('comment_notification_recipients', '__return_empty_array');
    add_filter('comment_moderation_recipients', '__return_empty_array');
  }

  public function disable_all_emails($args)
  {
    // Log the email attempt (optional)
    error_log('Email blocked: ' . print_r($args, true));

    // Return an empty array to prevent the email from being sent
    return array();
  }
}

// Initialize the plugin
new WP_Email_Stopper();
