<?php
/**
 * The plugin bootstrap file.
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://devrix.com
 * @since             1.0.0
 * @package           Dxsf_Proxy
 *
 * @wordpress-plugin
 * Plugin Name:       DXSF Proxy
 * Plugin URI:        https://devrix.com
 * Description:       Stability Framework Proxy Plugin
 * Version:           2.3.3
 * Author:            DevriX
 * Author URI:        https://devrix.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       plugin-name
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version. Start at version 1.0.0
 * For the versioning of the plugin is used SemVer - https://semver.org
 * Rename this for every new plugin and update it as you release new versions.
 */
define( 'DXSF_PROXY_VERSION', '2.3.3' );

if ( ! defined( 'DXSF_PROXY_DIR' ) ) {
	define( 'DXSF_PROXY_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DXSF_PROXY_URL' ) ) {
	define( 'DXSF_PROXY_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'DXSF_DEBUG' ) ) {
	define( 'DXSF_DEBUG', false );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/classes/class-dxsf-proxy-activator.php
 */
function dx_activate_dxsf_proxy() {
	require_once DXSF_PROXY_DIR . 'includes/classes/class-activator.php';
	Dxsf_proxy\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/classes/class-dxsf-proxy-deactivator.php
 */
function dx_deactivate_dxsf_proxy() {
	require_once DXSF_PROXY_DIR . 'includes/classes/class-deactivator.php';
	Dxsf_proxy\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'dx_activate_dxsf_proxy' );
register_deactivation_hook( __FILE__, 'dx_deactivate_dxsf_proxy' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once DXSF_PROXY_DIR . 'includes/classes/class-dxsf-proxy.php';

/**
 * The plugin functions file that is used to define general functions, shortcodes etc.
 */
require_once DXSF_PROXY_DIR . 'includes/functions.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function dx_run_dxsf_proxy() {
	$plugin = new Dxsf_proxy\Dxsf_Proxy();
	$plugin->run();
}

dx_run_dxsf_proxy();
