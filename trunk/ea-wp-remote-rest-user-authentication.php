<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://BrianHenry.ie
 * @since             1.0.0
 * @package           EA_WP_Remote_REST_User_Authentication
 *
 * @wordpress-plugin
 * Plugin Name:       EA WP – Remote REST User Authentication
 * Plugin URI:        https://github.com/EnhancedAthlete/ea-wp-remote-rest-user-authentication
 * Description:       Attempts to authenticate unsuccessful logins against other WordPress instances.
 * Version:           1.0.0
 * Author:            Brian Henry
 * Author URI:        https://BrianHenry.ie
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ea-wp-remote-rest-user-authentication
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
define( 'EA_WP_REMOTE_REST_USER_AUTHENTICATION_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-ea-wp-remote-rest-user-authentication-activator.php
 */
function activate_ea_wp_remote_rest_user_authentication() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ea-wp-remote-rest-user-authentication-activator.php';
	EA_WP_Remote_REST_User_Authentication_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-ea-wp-remote-rest-user-authentication-deactivator.php
 */
function deactivate_ea_wp_remote_rest_user_authentication() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-ea-wp-remote-rest-user-authentication-deactivator.php';
	EA_WP_Remote_REST_User_Authentication_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_ea_wp_remote_rest_user_authentication' );
register_deactivation_hook( __FILE__, 'deactivate_ea_wp_remote_rest_user_authentication' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-ea-wp-remote-rest-user-authentication.php';

$ea_wp_remote_rest_user_authentication = new EA_WP_Remote_REST_User_Authentication();
$ea_wp_remote_rest_user_authentication->run();

