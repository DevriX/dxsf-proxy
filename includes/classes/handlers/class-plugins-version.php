<?php
/**
 * Plugins version endpoint: every installed plugin with its version.
 *
 * @since 1.0.0
 * @package Dxsf_Proxy
 */

namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Plugins version handler.
 */
class Plugins_Version implements HandlerInterface {

	/**
	 * Return the installed plugins.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {

		$plugins = get_plugins();

		$response = array();

		foreach ( $plugins as $key => $plugin ) {
			$response[] = array(
				'name'    => $plugin['Name'],
				'slug'    => dirname( plugin_basename( $key ) ),
				'version' => $plugin['Version'],
			);
		}

		return new WP_REST_Response( $response, 200 );
	}
}
