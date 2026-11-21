<?php
/**
 * What the proxy reports about its site.
 *
 * One payload for the daily push. It carries everything the vulnerability
 * matching needs later (versions of core, PHP, database, web server, every
 * plugin and theme), so the scans need no second proxy release.
 *
 * @link       https://devrix.com
 * @since      3.0.0
 *
 * @package    Dxsf_Proxy
 * @subpackage Dxsf_Proxy/includes/classes
 * @author     DevriX <contact@devrix.com>
 */

namespace Dxsf_proxy;

/**
 * Inventory class.
 */
class Inventory {

	/**
	 * Collect the full inventory.
	 *
	 * @return array
	 */
	public static function collect() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return array(
			'schema'        => 1,
			'proxy_version' => DXSF_PROXY_VERSION,
			'collected_at'  => gmdate( 'c' ),
			'site'          => self::site(),
			'core'          => self::core(),
			'php'           => self::php(),
			'database'      => self::database(),
			'web_server'    => self::web_server(),
			'plugins'       => self::plugins(),
			'themes'        => self::themes(),
			'users'         => Handlers\Users::find( get_option( 'dxsf_email_extensions', '' ) ),
		);
	}

	/**
	 * Site facts.
	 *
	 * @return array
	 */
	public static function site() {
		return array(
			'home_url'          => home_url(),
			'site_url'          => site_url(),
			'multisite'         => is_multisite(),
			'locale'            => get_locale(),
			'timezone'          => wp_timezone_string(),
			'wp_debug'          => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'wp_debug_display'  => defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ),
			'file_edit_allowed' => ! ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ),
			'wp_cron_disabled'  => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON,
		);
	}

	/**
	 * WordPress core version and the update WordPress itself knows about.
	 *
	 * @return array
	 */
	public static function core() {
		$update  = null;
		$updates = get_site_transient( 'update_core' );

		if ( is_object( $updates ) && ! empty( $updates->updates ) ) {
			foreach ( $updates->updates as $offer ) {
				if ( isset( $offer->response, $offer->current ) && 'upgrade' === $offer->response && version_compare( $offer->current, get_bloginfo( 'version' ), '>' ) ) {
					$update = $offer->current;
					break;
				}
			}
		}

		return array(
			'version' => get_bloginfo( 'version' ),
			'update'  => $update,
		);
	}

	/**
	 * PHP runtime.
	 *
	 * @return array
	 */
	public static function php() {
		return array(
			'version'      => PHP_VERSION,
			'memory_limit' => ini_get( 'memory_limit' ),
			'sapi'         => PHP_SAPI,
		);
	}

	/**
	 * Database server type and version.
	 *
	 * @return array
	 */
	public static function database() {
		global $wpdb;

		$info = method_exists( $wpdb, 'db_server_info' ) ? (string) $wpdb->db_server_info() : '';
		$type = false !== stripos( $info, 'mariadb' ) ? 'mariadb' : 'mysql';

		// MariaDB reports "5.5.5-10.6.12-MariaDB" through the MySQL protocol.
		if ( preg_match( '/(\d+\.\d+\.\d+)-MariaDB/i', $info, $matches ) ) {
			$version = $matches[1];
		} elseif ( preg_match( '/^(\d+\.\d+\.\d+)/', $info, $matches ) ) {
			$version = $matches[1];
		} else {
			$version = method_exists( $wpdb, 'db_version' ) ? (string) $wpdb->db_version() : '';
		}

		return array(
			'type'    => $type,
			'version' => $version,
			'raw'     => $info,
		);
	}

	/**
	 * Web server software. Unknown when the push runs outside a web request.
	 *
	 * @return array
	 */
	public static function web_server() {
		$software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		$name     = '';
		$version  = '';

		if ( preg_match( '/^([a-z\-]+)(?:\/([\d.]+))?/i', $software, $matches ) ) {
			$name    = strtolower( $matches[1] );
			$version = isset( $matches[2] ) ? $matches[2] : '';
		}

		return array(
			'name'    => $name,
			'version' => $version,
			'raw'     => $software,
		);
	}

	/**
	 * Every installed plugin, must-use plugins included.
	 *
	 * @return array
	 */
	public static function plugins() {
		$updates = get_site_transient( 'update_plugins' );
		$updates = is_object( $updates ) && ! empty( $updates->response ) ? $updates->response : array();
		$list    = array();

		foreach ( get_plugins() as $file => $plugin ) {
			$list[] = array(
				'slug'           => self::plugin_slug( $file ),
				'file'           => $file,
				'name'           => $plugin['Name'],
				'version'        => $plugin['Version'],
				'kind'           => 'plugin',
				'active'         => is_plugin_active( $file ),
				'network_active' => is_multisite() && is_plugin_active_for_network( $file ),
				'update'         => isset( $updates[ $file ]->new_version ) ? $updates[ $file ]->new_version : null,
			);
		}

		foreach ( get_mu_plugins() as $file => $plugin ) {
			$list[] = array(
				'slug'           => self::plugin_slug( $file ),
				'file'           => $file,
				'name'           => $plugin['Name'],
				'version'        => $plugin['Version'],
				'kind'           => 'mu-plugin',
				'active'         => true,
				'network_active' => false,
				'update'         => null,
			);
		}

		return $list;
	}

	/**
	 * Every installed theme, with the active parent and child flagged.
	 *
	 * @return array
	 */
	public static function themes() {
		$updates    = get_site_transient( 'update_themes' );
		$updates    = is_object( $updates ) && ! empty( $updates->response ) ? $updates->response : array();
		$template   = get_template();
		$stylesheet = get_stylesheet();
		$list       = array();

		foreach ( wp_get_themes() as $slug => $theme ) {
			$list[] = array(
				'slug'    => $slug,
				'name'    => $theme->get( 'Name' ),
				'version' => $theme->get( 'Version' ),
				'active'  => $slug === $stylesheet || $slug === $template,
				'role'    => $slug === $stylesheet ? ( $stylesheet === $template ? 'theme' : 'child' ) : ( $slug === $template ? 'parent' : '' ),
				'update'  => isset( $updates[ $slug ]['new_version'] ) ? $updates[ $slug ]['new_version'] : null,
			);
		}

		return $list;
	}

	/**
	 * Plugin slug from its main file: the folder, or the file name for single-file plugins.
	 *
	 * @param string $file Plugin main file relative to the plugins folder.
	 * @return string
	 */
	public static function plugin_slug( $file ) {
		$dir = dirname( $file );

		return '.' === $dir ? basename( $file, '.php' ) : $dir;
	}
}
