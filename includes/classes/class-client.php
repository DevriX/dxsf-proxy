<?php
/**
 * Pushes the inventory to the Stability Framework core.
 *
 * Twice a day from WP-Cron, or on demand from the settings page and WP-CLI.
 * The core answers with the site status (active, paused, revoked) and the
 * minimum and latest proxy version, in a response signed with the site key.
 *
 * @link       https://devrix.com
 * @since      3.0.0
 *
 * @package    Dxsf_Proxy
 * @subpackage Dxsf_Proxy/includes/classes
 * @author     DevriX <contact@devrix.com>
 */

namespace Dxsf_proxy;

use WP_Error;

/**
 * Client class.
 */
class Client {

	/**
	 * WP-Cron hook.
	 *
	 * @var string
	 */
	const HOOK = 'dxsf_proxy_push';

	/**
	 * Core route that takes the inventory.
	 *
	 * @var string
	 */
	const INGEST_PATH = '/api/v1/ingest';

	/**
	 * Schedule the push, unless the site is revoked or not connected.
	 *
	 * Spread over the first hour, so the sites do not all push at once.
	 */
	public static function schedule() {
		if ( ! Settings::connected() || 'revoked' === Settings::status() ) {
			return;
		}

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + wp_rand( 60, HOUR_IN_SECONDS ), 'twicedaily', self::HOOK );
		}
	}

	/**
	 * Stop the push.
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * WP-Cron callback.
	 */
	public static function scheduled_push() {
		self::push( 'schedule' );
	}

	/**
	 * Send the inventory to the core and act on its answer.
	 *
	 * @param string $reason schedule, manual or cli.
	 * @return array|WP_Error The decoded answer of the core, or the error.
	 */
	public static function push( $reason = 'manual' ) {
		if ( ! Settings::connected() ) {
			$error = new WP_Error( 'dxsf_not_connected', __( 'The core URL, site id or site key is missing.', 'dxsf-proxy' ) );
			Settings::record_push( false, $error->get_error_message() );
			return $error;
		}

		if ( 'schedule' === $reason && 'revoked' === Settings::status() ) {
			self::unschedule();
			return new WP_Error( 'dxsf_revoked', __( 'This site is revoked in the Stability Framework.', 'dxsf-proxy' ) );
		}

		$payload           = Inventory::collect();
		$payload['reason'] = $reason;
		$body              = wp_json_encode( $payload );
		$headers           = Signature::request_headers( Settings::site_id(), Settings::site_key(), 'POST', self::INGEST_PATH, array(), $body );
		$nonce             = $headers['X-DXSF-Nonce'];

		$response = wp_remote_post(
			Settings::core_url() . self::INGEST_PATH,
			array(
				'timeout'    => 20,
				'user-agent' => 'dxsf-proxy/' . DXSF_PROXY_VERSION . '; ' . home_url(),
				'headers'    => $headers + array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'       => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			Settings::record_push( false, $response->get_error_message() );
			return $response;
		}

		return self::handle_answer( $response, $nonce );
	}

	/**
	 * Read the answer of the core.
	 *
	 * Only a correctly signed answer may change the site status, so a forged
	 * response cannot pause or revoke the proxy.
	 *
	 * @param array  $response The wp_remote_post() response.
	 * @param string $nonce    The nonce of the request.
	 * @return array|WP_Error
	 */
	public static function handle_answer( $response, $nonce ) {
		$code   = (int) wp_remote_retrieve_response_code( $response );
		$raw    = (string) wp_remote_retrieve_body( $response );
		$answer = json_decode( $raw, true );
		$answer = is_array( $answer ) ? $answer : array();

		$signed = Signature::fresh( wp_remote_retrieve_header( $response, 'x-dxsf-timestamp' ) )
			&& Signature::verify(
				Signature::canonical_response( $nonce, wp_remote_retrieve_header( $response, 'x-dxsf-timestamp' ), $raw ),
				Settings::site_key(),
				wp_remote_retrieve_header( $response, 'x-dxsf-signature' )
			);

		$message = isset( $answer['message'] ) ? sanitize_text_field( $answer['message'] ) : '';

		if ( ! $signed ) {
			/* translators: %d: HTTP status code. */
			$error = new WP_Error( 'dxsf_unsigned_answer', sprintf( __( 'The core answered HTTP %d without a valid signature.', 'dxsf-proxy' ), $code ) . ( $message ? ' ' . $message : '' ) );
			Settings::record_push( false, $error->get_error_message() );
			return $error;
		}

		if ( isset( $answer['min_version'] ) ) {
			update_option( 'dxsf_min_version', sanitize_text_field( $answer['min_version'] ), false );
		}

		if ( isset( $answer['latest_version'] ) ) {
			update_option( 'dxsf_latest_version', sanitize_text_field( $answer['latest_version'] ), false );
		}

		if ( isset( $answer['status'] ) ) {
			Settings::set_status( $answer['status'] );
		}

		if ( 200 !== $code ) {
			/* translators: %d: HTTP status code. */
			$error = new WP_Error( 'dxsf_rejected', $message ? $message : sprintf( __( 'The core answered HTTP %d.', 'dxsf-proxy' ), $code ) );
			Settings::record_push( false, $error->get_error_message() );
			return $error;
		}

		/* translators: %s: site status. */
		Settings::record_push( true, sprintf( __( 'Accepted. Site status: %s.', 'dxsf-proxy' ), Settings::status() ) );

		return $answer;
	}
}
