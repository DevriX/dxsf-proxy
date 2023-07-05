<?php
/**
 * The public functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @link       https://devrix.com
 * @since      1.0.0
 *
 * @package    Dxsf_Proxy
 * @subpackage Dxsf_Proxy/includes/classes
 * @author     DevriX <contact@devrix.com>
 */

namespace Dxsf_proxy;

use WP_REST_Request;

/**
 * Common class.
 */
class API {

	/**
	 * The base endpoint.
	 * @var string
	 */
	private $endpoint_base = 'dxsf-proxy/v1';

	/**
	 * The endpoints that will be registered.
	 * 
	 * Handler classes should be named as the endpoint name but with underscores instead of dashes, of course.
	 * 
	 * @var string[]
	 */
	private $endpoints = array(
		'error-log',
		'core-version',
		'plugins-version',
		'theme-version',
	);

	/**
	 * Register the REST API endpoints.
	 *
	 * @since 1.0.0
	 */

	public function register_rest_api_endpoints() {

		foreach ( $this->endpoints as $endpoint ) {
			register_rest_route(
				$this->endpoint_base,
				'/' . $endpoint,
				array(
					'methods'  => 'GET',
					'callback' => array( $this, 'api_handler' ),
					'permission_callback' => array( $this, 'permissions_callback' )
				)
			);
		}
	}

	/**
	 * Hide Rest API endpoint from the wp-json list.
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

		foreach ( $this->endpoints as $endpoint ) {
			unset( $endpoints['routes'][ '/' . $this->endpoint_base . '/' . $endpoint ] );
		}

		return $endpoints;
	}

	/**
	 * API endpoint handler.
	 */
	public function api_handler( WP_REST_Request $request ) {
		
		// Dynamically call a class from the handlers folder based on the endpoint name.
		$endpoint = $request->get_route();
		$endpoint = str_replace( '/' . $this->endpoint_base . '/', '', $endpoint );
		$endpoint = str_replace( '-', '_', $endpoint );
		$endpoint = 'Dxsf_proxy\Handlers\\' . $endpoint;

		if ( class_exists( $endpoint ) ) {
			$handler = new $endpoint();
			return $handler->handle( $request );
		} else {
			return new \WP_Error( 'dxsf_proxy_invalid_endpoint', 'Invalid endpoint', array( 'status' => 404 ) );
		}
	}

	/**
	 * Permissions callback.
	 */
	public function permissions_callback( WP_REST_Request $request ) {

		$remote = false;

		if ( defined( 'DXSF_REMOTE' ) ) {
			$remote = DXSF_REMOTE;
		} else {
			$remote = get_option( 'dxsf_remote_address' );
		}

		if ( empty( $remote ) ) {
			return false;
		}

		if ( ! DXSF_DEBUG && ( empty( $_SERVER ) || $_SERVER['REMOTE_ADDR'] !== $remote ) ) {
			return false;
		}

		return true;
	}
}
