<?php
/**
 * WP-CLI commands: connect, push, status, inventory.
 *
 * @link       https://devrix.com
 * @since      3.0.0
 *
 * @package    Dxsf_Proxy
 * @subpackage Dxsf_Proxy/includes/classes
 * @author     DevriX <contact@devrix.com>
 */

namespace Dxsf_proxy;

use WP_CLI;

/**
 * Manage the connection to the Stability Framework core.
 */
class CLI {

	/**
	 * Connect this site to the core and send the first inventory.
	 *
	 * ## OPTIONS
	 *
	 * --core-url=<url>
	 * : Core URL, e.g. https://sf.devrix.com.
	 *
	 * --site-id=<id>
	 * : Site id from the dashboard.
	 *
	 * [--site-key=<key>]
	 * : Site key from the dashboard. Left out, it is asked for with the input hidden, or read from STDIN when piped, so it stays out of the shell history.
	 *
	 * ## EXAMPLES
	 *
	 *     wp dxsf connect --core-url=https://sf.devrix.com --site-id=01JD...
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function connect( $args, $assoc_args ) {
		if ( isset( $assoc_args['site-key'] ) ) {
			$key = $assoc_args['site-key'];
		} elseif ( function_exists( 'posix_isatty' ) && posix_isatty( STDIN ) ) {
			$key = trim( (string) \cli\prompt( 'Site key', false, ': ', true ) );
		} else {
			$key = trim( (string) fgets( STDIN ) );
		}

		if ( '' === $key ) {
			WP_CLI::error( 'No site key given.' );
		}

		Settings::save_connection( $assoc_args['core-url'], $assoc_args['site-id'], $key );
		Client::schedule();

		$this->push();
	}

	/**
	 * Send the inventory to the core now.
	 */
	public function push() {
		$result = Client::push( 'cli' );

		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}

		WP_CLI::success( Settings::last_push()['message'] );
	}

	/**
	 * Show the connection and the last push.
	 */
	public function status() {
		$last = Settings::last_push();
		$next = wp_next_scheduled( Client::HOOK );

		$rows = array(
			array(
				'field' => 'proxy_version',
				'value' => DXSF_PROXY_VERSION,
			),
			array(
				'field' => 'core_url',
				'value' => Settings::core_url(),
			),
			array(
				'field' => 'site_id',
				'value' => Settings::site_id(),
			),
			array(
				'field' => 'site_key',
				'value' => '' === Settings::site_key() ? 'missing' : 'set',
			),
			array(
				'field' => 'status',
				'value' => Settings::connected() ? Settings::status() : 'not connected',
			),
			array(
				'field' => 'last_push',
				'value' => $last['time'] ? gmdate( 'c', $last['time'] ) . ' ' . ( $last['ok'] ? 'ok' : 'failed' ) . ': ' . $last['message'] : 'never',
			),
			array(
				'field' => 'next_push',
				'value' => $next ? gmdate( 'c', $next ) : 'not scheduled',
			),
		);

		WP_CLI\Utils\format_items( 'table', $rows, array( 'field', 'value' ) );
	}

	/**
	 * Print the inventory the proxy would send, as JSON.
	 */
	public function inventory() {
		WP_CLI::line( wp_json_encode( Inventory::collect(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
	}
}
