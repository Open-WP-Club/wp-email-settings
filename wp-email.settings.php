<?php

/**
 * Plugin Name:             WP Email Settings
 * Plugin URI:              https://github.com/Open-WP-Club/wp-email-settings
 * Description:             A plugin to manage and log WordPress emails with customizable settings, comprehensive statistics, and email validation
 * Version:                 1.0.1
 * Author:                  Open WP Club
 * Author URI:              https://openwpclub.com
 * License:                 GPL-2.0 License
 * Requires at least:       6.0
 * Requires PHP:            7.4
 * Tested up to:            6.6.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
  exit;
}

class WP_Email_Settings
{
  private $options;
  private $log_file;

  public function __construct()
  {
    $this->log_file = WP_CONTENT_DIR . '/wp-email-settings-log.txt';
    add_action('init', array($this, 'init'));
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));
    add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
    add_action('register_post', array($this, 'validate_email_numbers'), 10, 3);
    add_action('plugins_loaded', array($this, 'check_for_woocommerce'));
  }

  public function init()
  {
    $this->options = get_option('wp_email_settings_options');
    add_filter('wp_mail', array($this, 'intercept_email'), 10, 1);
  }

  public function intercept_email($args)
  {
    $this->log_email($args);
    if (isset($this->options['stop_all_emails']) && $this->options['stop_all_emails']) {
      return array();
    }
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
    $headers = is_array($args['headers']) ? implode("\n", $args['headers']) : $args['headers'];
    $log_entry = date('Y-m-d H:i:s') . "|" .
      "To: " . $to . "|" .
      "Subject: " . $args['subject'] . "|" .
      "Headers: " . $headers . "|" .
      "Message: " . substr(wp_strip_all_tags($args['message']), 0, 100) . "...\n";
    file_put_contents($this->log_file, $log_entry, FILE_APPEND);
  }

  public function add_admin_menu()
  {
    add_options_page(
      'WP Email Settings',
      'Email Settings',
      'manage_options',
      'wp-email-settings',
      array($this, 'render_settings_page')
    );
  }

  public function register_settings()
  {
    register_setting('wp_email_settings_options', 'wp_email_settings_options');
    add_settings_section(
      'wp_email_settings_main',
      'Email Settings',
      array($this, 'section_text'),
      'wp-email-settings'
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
        'wp-email-settings',
        'wp_email_settings_main',
        array($option)
      );
    }

    add_settings_section(
      'wp_email_settings_validator',
      'Email Validator Settings',
      null,
      'wp-email-settings-validator'
    );

    add_settings_field(
      'email_number_limit',
      'Number Limit',
      array($this, 'render_number_limit_field'),
      'wp-email-settings-validator',
      'wp_email_settings_validator'
    );
  }

  public function section_text()
  {
    echo '<p>Choose which types of emails you want to stop:</p>';
  }

  public function render_checkbox($args)
  {
    $option_name = $args[0];
    $checked = isset($this->options[$option_name]) && $this->options[$option_name] ? 'checked' : '';
    echo "<input type='checkbox' id='$option_name' name='wp_email_settings_options[$option_name]' value='1' $checked />";
  }

  public function render_number_limit_field()
  {
    $number_limit = isset($this->options['email_number_limit']) ? $this->options['email_number_limit'] : 4;
    echo "<input type='number' name='wp_email_settings_options[email_number_limit]' value='{$number_limit}' min='1' max='10'>";
    echo "<p class='description'>Set the maximum number of digits allowed in the email address for registration.</p>";
  }

  public function render_settings_page()
  {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'statistics';
?>
    <div class="wrap">
      <h1>WP Email Settings</h1>
      <h2 class="nav-tab-wrapper">
        <a href="?page=wp-email-settings&tab=statistics" class="nav-tab <?php echo $active_tab == 'statistics' ? 'nav-tab-active' : ''; ?>">Statistics</a>
        <a href="?page=wp-email-settings&tab=validator" class="nav-tab <?php echo $active_tab == 'validator' ? 'nav-tab-active' : ''; ?>">Validator</a>
        <a href="?page=wp-email-settings&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
        <a href="?page=wp-email-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
      </h2>
      <div class="wp-email-settings-box">
        <?php
        if ($active_tab == 'statistics') {
          $this->render_statistics_page();
        } elseif ($active_tab == 'validator') {
          $this->render_validator_tab();
        } elseif ($active_tab == 'logs') {
          $this->render_log_page();
        } elseif ($active_tab == 'settings') {
          $this->render_settings_tab();
        }
        ?>
      </div>
    </div>
  <?php
  }

  public function render_settings_tab()
  {
  ?>
    <form method="post" action="options.php">
      <?php
      settings_fields('wp_email_settings_options');
      do_settings_sections('wp-email-settings');
      submit_button();
      ?>
    </form>
  <?php
  }

  public function render_log_page()
  {
    $log_content = file_get_contents($this->log_file);
    $log_entries = array_filter(explode("\n", $log_content));
  ?>
    <div class="wp-email-settings-log-header">
      <h2>Email Log</h2>
      <form method="post" class="wp-email-settings-clear-log-form">
        <?php wp_nonce_field('wp_email_settings_clear_log', 'wp_email_settings_clear_log_nonce'); ?>
        <input type="submit" name="wp_email_settings_clear_log" class="button button-secondary" value="Clear Log">
      </form>
    </div>
    <div class="wp-email-settings-log">
      <?php
      foreach ($log_entries as $index => $entry) {
        $parts = explode('|', $entry);
        $timestamp = array_shift($parts);
        echo '<div class="log-entry">';
        echo '<span class="log-number">' . ($index + 1) . '</span>';
        echo '<span class="log-timestamp">' . esc_html($timestamp) . '</span>';
        echo '<div class="log-details">';
        foreach ($parts as $part) {
          $key_value = explode(': ', $part, 2);
          if (count($key_value) == 2) {
            echo '<span class="log-' . sanitize_title($key_value[0]) . '">';
            echo '<strong>' . esc_html($key_value[0]) . ':</strong> ' . esc_html($key_value[1]);
            echo '</span>';
          }
        }
        echo '</div>';
        echo '</div>';
      }
      ?>
    </div>
  <?php
    $this->handle_log_actions();
  }

  public function render_statistics_page()
  {
    $log_content = file_get_contents($this->log_file);
    $log_entries = array_filter(explode("\n", $log_content));
    $total_emails = count($log_entries);

    $email_types = array(
      'update_emails' => 'Update Emails',
      'password_emails' => 'Password Emails',
      'email_change_emails' => 'Email Change Notifications',
      'new_user_emails' => 'New User Emails',
      'comment_emails' => 'Comment Notification Emails'
    );

    $daily_stats = array();
    $weekly_stats = array();
    $monthly_stats = array();

    $oldest_date = date('Y-m-d', strtotime(substr($log_entries[0], 0, 10)));
    $current_date = date('Y-m-d');

    for ($date = $oldest_date; $date <= $current_date; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
      $daily_stats[$date] = 0;
      $week = date('Y-W', strtotime($date));
      $month = date('Y-m', strtotime($date));
      $weekly_stats[$week] = isset($weekly_stats[$week]) ? $weekly_stats[$week] : 0;
      $monthly_stats[$month] = isset($monthly_stats[$month]) ? $monthly_stats[$month] : 0;
    }

    foreach ($log_entries as $entry) {
      $entry_date = substr($entry, 0, 10);
      if (isset($daily_stats[$entry_date])) {
        $daily_stats[$entry_date]++;
        $week = date('Y-W', strtotime($entry_date));
        $month = date('Y-m', strtotime($entry_date));
        $weekly_stats[$week]++;
        $monthly_stats[$month]++;
      }
    }

  ?>
    <h3>Email Statistics</h3>
    <p>Total emails logged: <?php echo $total_emails; ?></p>

    <h3>Active Email Stopping Settings</h3>
    <ul>
      <?php
      if (isset($this->options['stop_all_emails']) && $this->options['stop_all_emails']) {
        echo '<li><strong>All emails are currently stopped</strong></li>';
      } else {
        foreach ($email_types as $option => $label) {
          if (isset($this->options["stop_{$option}"]) && $this->options["stop_{$option}"]) {
            echo '<li>' . $label . ' are currently stopped</li>';
          }
        }
      }
      ?>
    </ul>

    <h3>Email Type Distribution</h3>
    <ul>
      <?php
      $type_count = array_fill_keys(array_keys($email_types), 0);
      foreach ($log_entries as $entry) {
        foreach ($email_types as $type => $label) {
          if (strpos($entry, $type) !== false) {
            $type_count[$type]++;
          }
        }
      }
      foreach ($email_types as $type => $label) {
        $count = $type_count[$type];
        $percentage = $total_emails > 0 ? round(($count / $total_emails) * 100, 2) : 0;
        echo "<li>{$label}: {$count} ({$percentage}%)</li>";
      }
      ?>
    </ul>

    <h3>Daily Statistics</h3>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>Date</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($daily_stats as $date => $count) {
          echo "<tr><td>{$date}</td><td>{$count}</td></tr>";
        }
        ?>
      </tbody>
    </table>

    <h3>Weekly Statistics</h3>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>Week</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($weekly_stats as $week => $count) {
          echo "<tr><td>{$week}</td><td>{$count}</td></tr>";
        }
        ?>
      </tbody>
    </table>

    <h3>Monthly Statistics</h3>
    <table class="wp-list-table widefat fixed striped">
      <thead>
        <tr>
          <th>Month</th>
          <th>Count</th>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($monthly_stats as $month => $count) {
          echo "<tr><td>{$month}</td><td>{$count}</td></tr>";
        }
        ?>
      </tbody>
    </table>
  <?php
  }

  public function render_validator_tab()
  {
  ?>
    <h2>Email Validator Settings</h2>
    <form method="post" action="options.php">
      <?php
      settings_fields('wp_email_settings_options');
      do_settings_sections('wp-email-settings-validator');
      submit_button();
      ?>
    </form>
    <h2>Failed Registration Attempts</h2>
    <div class="wp-email-settings-validator-actions">
      <form method="post" class="wp-email-settings-download-form">
        <?php wp_nonce_field('wp_email_settings_download_csv', 'wp_email_settings_download_nonce'); ?>
        <input type="submit" name="wp_email_settings_download_csv" class="button button-primary" value="Download All CSV">
      </form>
      <form method="post" class="wp-email-settings-download-date-form">
        <?php wp_nonce_field('wp_email_settings_download_csv_by_date', 'wp_email_settings_download_date_nonce'); ?>
        <label for="start_date">Start Date:</label>
        <input type="date" name="start_date" required>
        <label for="end_date">End Date:</label>
        <input type="date" name="end_date" required>
        <input type="submit" name="wp_email_settings_download_csv_by_date" class="button button-primary" value="Download CSV by Date">
      </form>
    </div>
<?php
    $this->handle_validator_actions();
  }

  private function handle_validator_actions()
  {
    if (isset($_POST['wp_email_settings_download_csv']) && check_admin_referer('wp_email_settings_download_csv', 'wp_email_settings_download_nonce')) {
      $this->download_failed_attempts_csv();
    }

    if (isset($_POST['wp_email_settings_download_csv_by_date']) && check_admin_referer('wp_email_settings_download_csv_by_date', 'wp_email_settings_download_date_nonce')) {
      $start_date = sanitize_text_field($_POST['start_date']);
      $end_date = sanitize_text_field($_POST['end_date']);
      $this->download_failed_attempts_csv_by_date($start_date, $end_date);
    }
  }

  private function handle_log_actions()
  {
    if (isset($_POST['wp_email_settings_clear_log']) && check_admin_referer('wp_email_settings_clear_log', 'wp_email_settings_clear_log_nonce')) {
      file_put_contents($this->log_file, '');
      add_settings_error('wp_email_settings_messages', 'wp_email_settings_message', __('Email log cleared.', 'wp-email-settings'), 'updated');
    }
  }

  private function download_failed_attempts_csv($start_date = null, $end_date = null)
  {
    $failed_attempts = get_option('wp_email_settings_failed_attempts', array());

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=failed_attempts.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, array('Username', 'Email', 'Time'));

    foreach ($failed_attempts as $attempt) {
      if ($start_date && $end_date) {
        $attempt_time = strtotime($attempt['time']);
        if ($attempt_time < strtotime($start_date) || $attempt_time > strtotime($end_date)) {
          continue;
        }
      }
      fputcsv($output, $attempt);
    }

    fclose($output);
    exit;
  }

  private function download_failed_attempts_csv_by_date($start_date, $end_date)
  {
    $this->download_failed_attempts_csv($start_date, $end_date);
  }

  public function add_admin_bar_menu($wp_admin_bar)
  {
    if ($this->are_emails_stopped()) {
      $wp_admin_bar->add_node(array(
        'id'    => 'wp-email-settings-notice',
        'title' => 'Emails Stopped',
        'href'  => admin_url('options-general.php?page=wp-email-settings'),
        'meta'  => array('class' => 'wp-email-settings-notice'),
      ));
    }
  }

  private function are_emails_stopped()
  {
    if (isset($this->options['stop_all_emails']) && $this->options['stop_all_emails']) {
      return true;
    }
    $stop_options = array('stop_update_emails', 'stop_password_emails', 'stop_email_change_emails', 'stop_new_user_emails', 'stop_comment_emails');
    foreach ($stop_options as $option) {
      if (isset($this->options[$option]) && $this->options[$option]) {
        return true;
      }
    }
    return false;
  }

  public function enqueue_admin_styles()
  {
    wp_enqueue_style('wp-email-settings-admin-style', plugins_url('admin-style.css', __FILE__));
  }

  public function validate_email_numbers($username, $email, $errors)
  {
    $number_limit = isset($this->options['email_number_limit']) ? $this->options['email_number_limit'] : 4;
    $number_count = preg_match_all('/\d/', $email);

    if ($number_count >= $number_limit) {
      $errors->add(
        'email_too_many_numbers',
        sprintf(
          __('Registration failed: The email address cannot contain %d or more numbers.', 'wp-email-settings'),
          $number_limit
        )
      );
      $this->log_failed_attempt($username, $email);
    }
  }

  public function check_for_woocommerce()
  {
    if (class_exists('WooCommerce')) {
      add_action('woocommerce_register_post', array($this, 'validate_woocommerce_email_numbers'), 10, 3);
    }
  }

  public function validate_woocommerce_email_numbers($username, $email, $errors)
  {
    $number_limit = isset($this->options['email_number_limit']) ? $this->options['email_number_limit'] : 4;
    $number_count = preg_match_all('/\d/', $email);

    if ($number_count >= $number_limit) {
      $errors->add(
        'email_too_many_numbers',
        sprintf(
          __('WooCommerce registration failed: The email address cannot contain %d or more numbers.', 'wp-email-settings'),
          $number_limit
        )
      );
      $this->log_failed_attempt($username, $email);
    }
  }

  private function log_failed_attempt($username, $email)
  {
    $failed_attempts = get_option('wp_email_settings_failed_attempts', array());
    $failed_attempts[] = array(
      'username' => $username,
      'email' => $email,
      'time' => current_time('mysql'),
    );
    update_option('wp_email_settings_failed_attempts', $failed_attempts);
  }
}

// Initialize the plugin
new WP_Email_Settings();
