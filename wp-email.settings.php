<?php

/**
 * Plugin Name: WP Email Stopper
 * Plugin URI: http://example.com/wp-email-stopper
 * Description: A plugin to stop and log WordPress emails with customizable settings
 * Version: 1.4
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
  private $log_file;

  public function __construct()
  {
    $this->log_file = WP_CONTENT_DIR . '/wp-email-stopper-log.txt';
    add_action('init', array($this, 'init'));
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
  }

  public function init()
  {
    $this->options = get_option('wp_email_stopper_options');

    // Always intercept emails for logging
    add_filter('wp_mail', array($this, 'intercept_email'), 10, 1);
  }

  public function intercept_email($args)
  {
    // Log the email
    $this->log_email($args);

    // Check if all emails should be stopped
    if (isset($this->options['stop_all_emails']) && $this->options['stop_all_emails']) {
      return array();
    }

    // Check individual email types
    $email_types = array(
      'update_emails' => array('auto_core_update_send_email', 'auto_plugin_update_send_email', 'auto_theme_update_send_email'),
      'password_emails' => array('send_password_change_email'),
      'email_change_emails' => array('send_email_change_email'),
      'new_user_emails' => array('wp_new_user_notification_email_admin', 'wp_new_user_notification_email'),
      'comment_emails' => array('comment_notification_recipients', 'comment_moderation_recipients')
    );

    foreach ($email_types as $option => $filters) {
      if (isset($this->options["stop_{$option}"]) && $this->options["stop_{$option}"]) {
        foreach ($filters as $filter) {
          add_filter($filter, '__return_false');
        }
      }
    }

    return $args;
  }

  public function log_email($args)
  {
    $to = is_array($args['to']) ? implode(', ', $args['to']) : $args['to'];
    $log_entry = date('Y-m-d H:i:s') . " - To: " . $to . " - Subject: " . $args['subject'] . "\n";
    file_put_contents($this->log_file, $log_entry, FILE_APPEND);
  }

  public function add_admin_menu()
  {
    // Add settings page under Settings menu
    add_options_page(
      'WP Email Stopper Settings',
      'Email Stopper',
      'manage_options',
      'wp-email-stopper',
      array($this, 'render_settings_page')
    );

    // Add top-level menu for logs
    add_menu_page(
      'Email Logs',
      'Email Logs',
      'manage_options',
      'wp-email-stopper-log',
      array($this, 'render_log_page'),
      'dashicons-email-alt'
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

    $email_options = array(
      'stop_all_emails' => 'Stop All Emails',
      'stop_update_emails' => 'Stop Update Emails',
      'stop_password_emails' => 'Stop Password Change Emails',
      'stop_email_change_emails' => 'Stop Email Change Notifications',
      'stop_new_user_emails' => 'Stop New User Emails',
      'stop_comment_emails' => 'Stop Comment Notification Emails'
    );

    foreach ($email_options as $option => $label) {
      add_settings_field(
        $option,
        $label,
        array($this, 'render_checkbox'),
        'wp-email-stopper',
        'wp_email_stopper_main',
        array($option)
      );
    }
  }

  public function section_text()
  {
    echo '<p>Choose which types of emails you want to stop:</p>';
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

  public function render_log_page()
  {
  ?>
    <div class="wrap">
      <h1>Email Logs</h1>
      <textarea readonly style="width: 100%; height: 600px;">
                <?php echo file_get_contents($this->log_file); ?>
            </textarea>
    </div>
<?php
  }
}

// Initialize the plugin
new WP_Email_Stopper();
