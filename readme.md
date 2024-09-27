# WP Email Settings

## Description

WP Email Settings is a comprehensive WordPress plugin designed to give administrators full control over the site's email functionality. It allows you to manage, log, and analyze email activities on your WordPress site, as well as validate email addresses during registration.

## Features

- **Email Control**: Selectively stop different types of WordPress emails or halt all emails.
- **Detailed Logging**: Keep track of all email activities on your site.
- **Comprehensive Statistics**: View daily, weekly, and monthly email statistics.
- **Email Type Distribution**: Analyze the distribution of different types of emails sent from your site.
- **Admin Bar Notification**: Quick visibility of email stopping status in the WordPress admin bar.
- **Email Validation**: Block registrations with email addresses containing too many numbers.
- **WooCommerce Integration**: Email validation for WooCommerce registration if WooCommerce is active.
- **Failed Registration Logging**: Log and analyze failed registration attempts due to email validation.
- **CSV Export**: Download logs of failed registration attempts, with options to filter by date range.

## Installation

1. Download the `wp-email-settings.php` file and the `admin-style.css` file.
2. Upload them to your WordPress plugins directory, typically `/wp-content/plugins/wp-email-settings/`.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Access the plugin settings via 'Settings' > 'Email Settings' in your WordPress admin panel.

## Usage

### Stopping Emails

1. Go to 'Settings' > 'Email Settings' in your WordPress admin panel.
2. Navigate to the 'Settings' tab.
3. Check the boxes next to the types of emails you want to stop.
4. Click 'Save Changes'.

### Viewing Logs

1. Go to 'Settings' > 'Email Settings' in your WordPress admin panel.
2. Navigate to the 'Logs' tab.
3. Here you can view detailed logs of all email activities.
4. Use the 'Clear Log' button to reset the log if needed.

### Analyzing Statistics

1. Go to 'Settings' > 'Email Settings' in your WordPress admin panel.
2. The 'Statistics' tab is the default view.
3. Here you can see:
   - Total emails logged
   - Active email stopping settings
   - Email type distribution
   - Daily, weekly, and monthly email statistics

### Configuring Email Validation

1. Go to 'Settings' > 'Email Settings' in your WordPress admin panel.
2. Navigate to the 'Validator' tab.
3. Set the maximum number of digits allowed in email addresses for registration.
4. Click 'Save Changes'.

### Managing Failed Registration Attempts

1. Go to 'Settings' > 'Email Settings' in your WordPress admin panel.
2. Navigate to the 'Validator' tab.
3. Use the 'Download All CSV' button to export all failed attempts.
4. Use the date range selector to download failed attempts for a specific period.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher

## Support

For support, feature requests, or bug reports, please open an issue on the plugin's GitHub repository.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL2 License.
