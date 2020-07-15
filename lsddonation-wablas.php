<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://lsdplugins.com
 * @since             1.0.0
 * @package           Lsddonation_Wablas
 *
 * @wordpress-plugin
 * Plugin Name:       - LSDDonation - WABLAS
 * Plugin URI:        https://github.com/lsdplugins/lsddonation-wablas
 * Description:       WhatsApp Notification for LSDDonation
 * Version:           1.0.0
 * Author:            LSD Plugins
 * Author URI:        https://lsdplugins.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lsddonation-wablas
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LSDDONATION_WABLAS_VERSION', '1.0.0' );

add_action( 'plugins_loaded', function(){
	require_once plugin_dir_path( __FILE__ ) . 'class-wablas.php';
});
