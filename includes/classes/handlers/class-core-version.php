<?php
/**
 * Core version endpoint: the installed WordPress version.
 *
 * @since 1.0.0
 * @package Dxsf_Proxy
 */

namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Core version handler.
 */
class Core_Version implements HandlerInterface {

	/**
	 * Return the WordPress version.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {

		$core_version = get_bloginfo( 'version' );

		return new WP_REST_Response( $core_version, 200 );
	}
}
