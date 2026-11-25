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
 * Version:           3.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Tested up to:      7.1
 * Author:            DevriX
 * Author URI:        https://devrix.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       dxsf-proxy
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
define( 'DXSF_PROXY_VERSION', '3.0.1' );

if ( ! defined( 'DXSF_PROXY_DIR' ) ) {
	define( 'DXSF_PROXY_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DXSF_PROXY_URL' ) ) {
	define( 'DXSF_PROXY_URL', plugin_dir_url( __FILE__ ) );
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

/**
 * Keep the folder the plugin is installed in when it updates.
 *
 * The release zip holds a dxsf-wordpress-proxy folder, the name the 2.x admin uploads gave most sites.
 * A site installed under another name (dxsf-proxy from git) would otherwise get a second folder,
 * and WordPress would deactivate the plugin it can no longer find.
 *
 * @param string|WP_Error $source        Unpacked folder of the new version.
 * @param string          $remote_source Its parent folder.
 * @param WP_Upgrader     $upgrader      The upgrader.
 * @param array           $hook_extra    Which plugin is updated.
 * @return string|WP_Error
 */
function dx_dxsf_proxy_keep_folder( $source, $remote_source, $upgrader, $hook_extra = array() ) {
	global $wp_filesystem;

	if ( is_wp_error( $source ) || empty( $hook_extra['plugin'] ) || plugin_basename( __FILE__ ) !== $hook_extra['plugin'] ) {
		return $source;
	}

	$folder = dirname( $hook_extra['plugin'] );

	if ( '.' === $folder || basename( untrailingslashit( $source ) ) === $folder ) {
		return $source;
	}

	$wanted = trailingslashit( $remote_source ) . $folder . '/';

	if ( $wp_filesystem && $wp_filesystem->move( untrailingslashit( $source ), untrailingslashit( $wanted ), true ) ) {
		return $wanted;
	}

	return new WP_Error( 'dxsf_proxy_folder', 'DXSF Proxy: could not keep the plugin folder name, update stopped.' );
}
add_filter( 'upgrader_source_selection', 'dx_dxsf_proxy_keep_folder', 10, 4 );

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
