<?php

/**
 * Plugin Name: WP Email Settings
 * Plugin URI: http://example.com/wp-email-settings
 * Description: A plugin to manage and log WordPress emails with customizable settings and comprehensive statistics
 * Version: 2.4
 * Author: Your Name
 * Author URI: http://example.com
 * License: GPL2
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

  public function render_settings_page()
  {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'statistics';
?>
    <div class="wrap">
      <h1>WP Email Settings</h1>
      <h2 class="nav-tab-wrapper">
        <a href="?page=wp-email-settings&tab=statistics" class="nav-tab <?php echo $active_tab == 'statistics' ? 'nav-tab-active' : ''; ?>">Statistics</a>
        <a href="?page=wp-email-settings&tab=settings" class="nav-tab <?php echo $active_tab == 'settings' ? 'nav-tab-active' : ''; ?>">Settings</a>
        <a href="?page=wp-email-settings&tab=logs" class="nav-tab <?php echo $active_tab == 'logs' ? 'nav-tab-active' : ''; ?>">Logs</a>
      </h2>
      <div class="wp-email-settings-box">
        <?php
        if ($active_tab == 'statistics') {
          $this->render_statistics_page();
        } elseif ($active_tab == 'settings') {
          $this->render_settings_tab();
        } else {
          $this->render_log_page();
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

    // Initialize statistics arrays
    $daily_stats = array();
    $weekly_stats = array();
    $monthly_stats = array();

    // Get the oldest log entry date
    $oldest_date = date('Y-m-d', strtotime(substr($log_entries[0], 0, 10)));
    $current_date = date('Y-m-d');

    // Initialize all days, weeks, and months from the oldest log to current date
    for ($date = $oldest_date; $date <= $current_date; $date = date('Y-m-d', strtotime($date . ' +1 day'))) {
      $daily_stats[$date] = 0;
      $week = date('Y-W', strtotime($date));
      $month = date('Y-m', strtotime($date));
      $weekly_stats[$week] = isset($weekly_stats[$week]) ? $weekly_stats[$week] : 0;
      $monthly_stats[$month] = isset($monthly_stats[$month]) ? $monthly_stats[$month] : 0;
    }

    // Count emails for each day, week, and month
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
}

// Initialize the plugin
new WP_Email_Settings();
