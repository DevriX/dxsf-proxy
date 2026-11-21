<?php
/**
 * The REST endpoints the core pulls from.
 *
 * @link       https://devrix.com
 * @since      1.0.0
 *
 * @package    Dxsf_Proxy
 * @subpackage Dxsf_Proxy/includes/classes
 * @author     DevriX <contact@devrix.com>
 */

namespace Dxsf_proxy;

use WP_Error;
use WP_REST_Request;

/**
 * API class.
 */
class API {

	/**
	 * The base endpoint.
	 *
	 * @var string
	 */
	private $endpoint_base = 'dxsf-proxy/v1';

	/**
	 * Endpoints, mapped to whether the IP allowlist of the 2.x core may still call them.
	 *
	 * Handler classes are named as the endpoint, with underscores instead of dashes.
	 *
	 * @var bool[]
	 */
	private $endpoints = array(
		'inventory'       => false,
		'error-log'       => true,
		'core-version'    => true,
		'plugins-version' => true,
		'theme-version'   => true,
		'users'           => true,
	);

	/**
	 * Register the REST API endpoints.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_api_endpoints() {
		foreach ( array_keys( $this->endpoints ) as $endpoint ) {
			register_rest_route(
				$this->endpoint_base,
				'/' . $endpoint,
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'api_handler' ),
					'permission_callback' => array( $this, 'permissions_callback' ),
				)
			);
		}
	}

	/**
	 * Hide Rest API endpoint from the wp-json list.
	 *
	 * @param array $endpoints The endpoints.
	 */
	public function hide_rest_api_endpoint( $endpoints ) {
		if ( ! is_array( $endpoints ) ) {
			return $endpoints;
		}

		if ( ! isset( $endpoints['namespaces'] ) ) {
			return $endpoints;
		}

		foreach ( $endpoints['namespaces'] as $key => $value ) {
			if ( $this->endpoint_base === $value ) {
				unset( $endpoints['namespaces'][ $key ] );
			}
		}

		unset( $endpoints['routes'][ '/' . $this->endpoint_base . '/' ] );
		unset( $endpoints['routes'][ '/' . $this->endpoint_base ] );

		foreach ( array_keys( $this->endpoints ) as $endpoint ) {
			unset( $endpoints['routes'][ '/' . $this->endpoint_base . '/' . $endpoint ] );
		}

		return $endpoints;
	}

	/**
	 * API endpoint handler.
	 *
	 * @param WP_REST_Request $request The request object.
	 */
	public function api_handler( WP_REST_Request $request ) {
		$endpoint = str_replace( '/' . $this->endpoint_base . '/', '', $request->get_route() );

		if ( ! isset( $this->endpoints[ $endpoint ] ) ) {
			return new WP_Error( 'dxsf_proxy_invalid_endpoint', 'Invalid endpoint', array( 'status' => 404 ) );
		}

		$class = 'Dxsf_proxy\Handlers\\' . str_replace( '-', '_', $endpoint );

		if ( ! class_exists( $class ) ) {
			return new WP_Error( 'dxsf_proxy_invalid_endpoint', 'Invalid endpoint', array( 'status' => 404 ) );
		}

		$handler = new $class();

		return $handler->handle( $request );
	}

	/**
	 * Permissions callback.
	 *
	 * A signed request from the core is the way in. The IP allowlist of the 2.x
	 * core stays for the legacy endpoints until the switch-over, and only when
	 * a remote address is set.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return true|WP_Error
	 */
	public function permissions_callback( WP_REST_Request $request ) {
		if ( 'revoked' === Settings::status() ) {
			return new WP_Error( 'dxsf_revoked', 'Forbidden', array( 'status' => 403 ) );
		}

		if ( null !== $request->get_header( 'x_dxsf_signature' ) ) {
			return $this->verify_signed( $request );
		}

		$endpoint = str_replace( '/' . $this->endpoint_base . '/', '', $request->get_route() );

		if ( empty( $this->endpoints[ $endpoint ] ) ) {
			return new WP_Error( 'dxsf_forbidden', 'Forbidden', array( 'status' => 401 ) );
		}

		return $this->legacy_ip_allowed() ? true : new WP_Error( 'dxsf_forbidden', 'Forbidden', array( 'status' => 401 ) );
	}

	/**
	 * Check a signed request from the core.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return true|WP_Error
	 */
	public function verify_signed( WP_REST_Request $request ) {
		$denied    = new WP_Error( 'dxsf_forbidden', 'Forbidden', array( 'status' => 401 ) );
		$site_id   = (string) $request->get_header( 'x_dxsf_site' );
		$timestamp = (string) $request->get_header( 'x_dxsf_timestamp' );
		$nonce     = (string) $request->get_header( 'x_dxsf_nonce' );
		$signature = (string) $request->get_header( 'x_dxsf_signature' );

		if ( ! Settings::connected() || ! hash_equals( Settings::site_id(), $site_id ) ) {
			return $denied;
		}

		if ( ! Signature::fresh( $timestamp ) || ! preg_match( '/^[a-f0-9]{32}$/', $nonce ) ) {
			return $denied;
		}

		$query = $request->get_query_params();
		unset( $query['rest_route'] );

		$canonical = Signature::canonical_request( $request->get_method(), $request->get_route(), $query, $timestamp, $nonce, $request->get_body() );

		if ( ! Signature::verify( $canonical, Settings::site_key(), $signature ) ) {
			return $denied;
		}

		// One use per nonce, kept for twice the window so a replay is always caught.
		// With a persistent object cache, wp_cache_add() claims the nonce atomically.
		$seen = 'dxsf_nonce_' . $nonce;

		if ( wp_using_ext_object_cache() ) {
			return wp_cache_add( $seen, 1, 'dxsf_proxy', 2 * Signature::WINDOW ) ? true : $denied;
		}

		if ( false !== get_transient( $seen ) ) {
			return $denied;
		}

		set_transient( $seen, 1, 2 * Signature::WINDOW );

		return true;
	}

	/**
	 * The 2.x check: the request comes from the configured server address.
	 *
	 * @return bool
	 */
	private function legacy_ip_allowed() {
		$remote = defined( 'DXSF_REMOTE' ) ? DXSF_REMOTE : get_option( 'dxsf_remote_address' );

		if ( empty( $remote ) || empty( $_SERVER['REMOTE_ADDR'] ) ) {
			return false;
		}

		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) === $remote;
	}
}
