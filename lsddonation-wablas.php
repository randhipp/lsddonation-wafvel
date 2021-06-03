<?php
/**
 * @wordpress-plugin
 * Plugin Name:       LSDDonasi - WABLAS
 * Plugin URI:        https://lsdplugins.com/
 * Description:       Notifikasi Whatsapp WABLAS untuk LSDDonasi
 * Version:           1.0.4
 * Author:            LSD Plugins
 * Author URI:        https://lsdplugins.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       lsddonation-wablas
 * Domain Path:       /languages
 *
 * Build: Development
 * Lang : Indonesia
 */

// If this file is accessed directory, then abort.
if (!defined('ABSPATH')) {
    exit;
}

// Required LSDDonation Plugin
add_action('admin_init', 'lsdd_wablas_dependency');

// Define Constant
defined('LSDD_WABLAS_VERSION') or define('LSDD_WABLAS_VERSION', '1.0.4');
defined('LSDD_WABLAS_REQUIRED') or define('LSDD_WABLAS_REQUIRED', '4.0.4');
defined('LSDD_WABLAS_BASE') or define('LSDD_WABLAS_BASE', plugin_basename(__FILE__));
defined('LSDD_WABLAS_PATH') or define('LSDD_WABLAS_PATH', plugin_dir_path(__FILE__));
defined('LSDD_WABLAS_URL') or define('LSDD_WABLAS_URL', plugin_dir_url(__FILE__));

/*
 * LSDDonation admin notice and disable extension
 * Warning when the LSDDonation not Active or Core Version not Acceptable
 *
 * @since 1.0.3
 * @return void
 */
function lsdd_wablas_dependency()
{
    $core_active = true;
    $core_version = true;

    // Checking Core Active
    if (is_admin() && current_user_can('activate_plugins') && !is_plugin_active('lsddonation/lsddonation.php')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>' . __('LSDDonasi v' . LSDD_WABLAS_REQUIRED . ' dibutuhkan. aktifkan plugin utama sebelum ekstensi LSDDonation - WABLAS.', 'lsddonation-wablas') . '</p></div>';
        });
        $core_active = false;
    }

    // Checking Core Version
    $core_plugin = get_plugin_data(LSDD_PATH . 'lsddonation.php');
    if (!version_compare($core_plugin['Version'], LSDD_WABLAS_REQUIRED, '>=')) {
        add_action('admin_notices', 'lsdd_wablas_fail_version');
        $core_version = false;
    }

    // Deactive Extension
    if (!$core_version || !$core_active) {
        deactivate_plugins(plugin_basename(__FILE__));

        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}

// Acceptable -> Include WABLAS
$active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
if (in_array('lsddonation/lsddonation.php', $active_plugins)) {
    require_once LSDD_WABLAS_PATH . 'includes/plugin.php';
}

/**
 * LSDDonation admin notice for minimum LSDDonation version.
 *
 * Warning when the site doesn't have the minimum required LSDDonation version.
 *
 * @since 1.0.3
 * @return void
 */
function lsdd_wablas_fail_version()
{
    /* translators: %s: LSDDonation version */
    $message = sprintf(esc_html__('LSDDonasi - WABLAS membutuhkan plugin LSDDonasi versi %s+. Ekstensi tidak dapat berjalan, karena kamu menggunakan plugin versi lama.', 'lsddonation-wablas'), LSDD_WABLAS_REQUIRED);
    $html_message = sprintf('<div class="error">%s</div>', wpautop($message));
    echo wp_kses_post($html_message);
}
