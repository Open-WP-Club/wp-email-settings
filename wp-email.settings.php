<?php

/**
 * Plugin Name: WP Email Stopper
 * Plugin URI: http://example.com/wp-email-stopper
 * Description: A plugin to stop typical WordPress email addresses with customizable settings
 * Version: 1.1
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
  private $options;

  public function __construct()
  {
    add_action('init', array($this, 'init'));
    add_action('admin_menu', array($this, 'add_settings_page'));
    add_action('admin_init', array($this, 'register_settings'));
  }

  public function init()
  {
    $this->options = get_option('wp_email_stopper_options');

    // Disable all emails if the option is set
    if (isset($this->options['disable_all_emails']) && $this->options['disable_all_emails']) {
      add_filter('wp_mail', array($this, 'disable_all_emails'), 10, 1);
    } else {
      // Disable specific email notifications based on settings
      if (isset($this->options['disable_update_emails']) && $this->options['disable_update_emails']) {
        add_filter('auto_core_update_send_email', '__return_false');
        add_filter('auto_plugin_update_send_email', '__return_false');
        add_filter('auto_theme_update_send_email', '__return_false');
      }
      if (isset($this->options['disable_password_emails']) && $this->options['disable_password_emails']) {
        add_filter('send_password_change_email', '__return_false');
      }
      if (isset($this->options['disable_email_change_emails']) && $this->options['disable_email_change_emails']) {
        add_filter('send_email_change_email', '__return_false');
      }
      if (isset($this->options['disable_new_user_emails']) && $this->options['disable_new_user_emails']) {
        add_filter('wp_new_user_notification_email_admin', '__return_false');
        add_filter('wp_new_user_notification_email', '__return_false');
      }
      if (isset($this->options['disable_comment_emails']) && $this->options['disable_comment_emails']) {
        add_filter('comment_notification_recipients', '__return_empty_array');
        add_filter('comment_moderation_recipients', '__return_empty_array');
      }
    }
  }

  public function disable_all_emails($args)
  {
    // Log the email attempt (optional)
    error_log('Email blocked: ' . print_r($args, true));

    // Return an empty array to prevent the email from being sent
    return array();
  }

  public function add_settings_page()
  {
    add_options_page(
      'WP Email Stopper Settings',
      'Email Stopper',
      'manage_options',
      'wp-email-stopper',
      array($this, 'render_settings_page')
    );
  }

  public function register_settings()
  {
    register_setting('wp_email_stopper_options', 'wp_email_stopper_options');

    add_settings_section(
      'wp_email_stopper_main',
      'Email Settings',
      array($this, 'section_text'),
      'wp-email-stopper'
    );

    add_settings_field(
      'disable_all_emails',
      'Disable All Emails',
      array($this, 'render_checkbox'),
      'wp-email-stopper',
      'wp_email_stopper_main',
      array('disable_all_emails')
    );

    add_settings_field(
      'disable_update_emails',
      'Disable Update Emails',
      array($this, 'render_checkbox'),
      'wp-email-stopper',
      'wp_email_stopper_main',
      array('disable_update_emails')
    );

    add_settings_field(
      'disable_password_emails',
      'Disable Password Change Emails',
      array($this, 'render_checkbox'),
      'wp-email-stopper',
      'wp_email_stopper_main',
      array('disable_password_emails')
    );

    add_settings_field(
      'disable_email_change_emails',
      'Disable Email Change Notifications',
      array($this, 'render_checkbox'),
      'wp-email-stopper',
      'wp_email_stopper_main',
      array('disable_email_change_emails')
    );

    add_settings_field(
      'disable_new_user_emails',
      'Disable New User Emails',
      array($this, 'render_checkbox'),
      'wp-email-stopper',
      'wp_email_stopper_main',
      array('disable_new_user_emails')
    );

    add_settings_field(
      'disable_comment_emails',
      'Disable Comment Notification Emails',
      array($this, 'render_checkbox'),
      'wp-email-stopper',
      'wp_email_stopper_main',
      array('disable_comment_emails')
    );
  }

  public function section_text()
  {
    echo '<p>Choose which types of emails you want to disable:</p>';
  }

  public function render_checkbox($args)
  {
    $option_name = $args[0];
    $checked = isset($this->options[$option_name]) && $this->options[$option_name] ? 'checked' : '';
    echo "<input type='checkbox' id='$option_name' name='wp_email_stopper_options[$option_name]' value='1' $checked />";
  }

  public function render_settings_page()
  {
?>
    <div class="wrap">
      <h1>WP Email Stopper Settings</h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('wp_email_stopper_options');
        do_settings_sections('wp-email-stopper');
        submit_button();
        ?>
      </form>
    </div>
<?php
  }
}

// Initialize the plugin
new WP_Email_Stopper();
