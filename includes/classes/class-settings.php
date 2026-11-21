<?php
/**
 * Connection settings and the status the core reported back.
 *
 * Every connection value can come from wp-config.php (a constant wins) or from
 * an option set on the settings page or with WP-CLI.
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
 * Settings class.
 */
class Settings {

	/**
	 * Options this plugin owns, removed on uninstall.
	 *
	 * @var string[]
	 */
	const OPTIONS = array(
		'dxsf_core_url',
		'dxsf_site_id',
		'dxsf_site_key',
		'dxsf_site_status',
		'dxsf_min_version',
		'dxsf_latest_version',
		'dxsf_last_push',
		'dxsf_error_log_file',
		'dxsf_date_format',
		'dxsf_remote_address',
		'dxsf_email_extensions',
	);

	/**
	 * Site states the core can answer with.
	 *
	 * @var string[]
	 */
	const STATUSES = array( 'active', 'paused', 'revoked' );

	/**
	 * Core URL without a trailing slash.
	 *
	 * @return string
	 */
	public static function core_url() {
		$url = defined( 'DXSF_CORE_URL' ) ? DXSF_CORE_URL : get_option( 'dxsf_core_url', '' );

		return untrailingslashit( (string) $url );
	}

	/**
	 * Site id issued by the core.
	 *
	 * @return string
	 */
	public static function site_id() {
		return (string) ( defined( 'DXSF_SITE_ID' ) ? DXSF_SITE_ID : get_option( 'dxsf_site_id', '' ) );
	}

	/**
	 * Site key issued by the core.
	 *
	 * @return string
	 */
	public static function site_key() {
		return (string) ( defined( 'DXSF_SITE_KEY' ) ? DXSF_SITE_KEY : get_option( 'dxsf_site_key', '' ) );
	}

	/**
	 * Whether a value is locked by a wp-config.php constant.
	 *
	 * @param string $name One of core_url, site_id, site_key.
	 * @return bool
	 */
	public static function locked( $name ) {
		return defined( 'DXSF_' . strtoupper( $name ) );
	}

	/**
	 * Whether the proxy has what it needs to talk to the core.
	 *
	 * @return bool
	 */
	public static function connected() {
		return '' !== self::core_url() && '' !== self::site_id() && '' !== self::site_key();
	}

	/**
	 * Status last reported by the core.
	 *
	 * @return string active, paused or revoked.
	 */
	public static function status() {
		$status = get_option( 'dxsf_site_status', 'active' );

		return in_array( $status, self::STATUSES, true ) ? $status : 'active';
	}

	/**
	 * Store the status the core answered with, and act on revoked.
	 *
	 * @param string $status active, paused or revoked.
	 */
	public static function set_status( $status ) {
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return;
		}

		update_option( 'dxsf_site_status', $status, false );

		if ( 'revoked' === $status ) {
			Client::unschedule();
		} else {
			Client::schedule();
		}
	}

	/**
	 * Save the connection, from the settings page or WP-CLI.
	 *
	 * A new key reactivates a revoked site: the status goes back to active and
	 * the schedule restarts, the core confirms on the next push.
	 *
	 * @param string $core_url Core URL.
	 * @param string $site_id  Site id.
	 * @param string $site_key Site key, empty to keep the current one.
	 */
	public static function save_connection( $core_url, $site_id, $site_key ) {
		if ( ! self::locked( 'core_url' ) ) {
			update_option( 'dxsf_core_url', esc_url_raw( untrailingslashit( trim( $core_url ) ) ), false );
		}

		if ( ! self::locked( 'site_id' ) ) {
			update_option( 'dxsf_site_id', sanitize_text_field( $site_id ), false );
		}

		$site_key = trim( (string) $site_key );

		if ( '' !== $site_key && ! self::locked( 'site_key' ) && self::site_key() !== $site_key ) {
			update_option( 'dxsf_site_key', sanitize_text_field( $site_key ), false );
			self::set_status( 'active' );
		}
	}

	/**
	 * Result of the last push, for the settings page.
	 *
	 * @return array{time:int,ok:bool,message:string}
	 */
	public static function last_push() {
		$last = get_option( 'dxsf_last_push', array() );

		return wp_parse_args(
			is_array( $last ) ? $last : array(),
			array(
				'time'    => 0,
				'ok'      => false,
				'message' => '',
			)
		);
	}

	/**
	 * Record the result of a push.
	 *
	 * @param bool   $ok      Whether the core accepted it.
	 * @param string $message What happened, shown on the settings page.
	 */
	public static function record_push( $ok, $message ) {
		update_option(
			'dxsf_last_push',
			array(
				'time'    => time(),
				'ok'      => (bool) $ok,
				'message' => (string) $message,
			),
			false
		);
	}
}
