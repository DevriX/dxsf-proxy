<?php
/**
 * The contract every REST handler follows.
 *
 * @since 1.0.0
 * @package Dxsf_Proxy
 */

namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * REST endpoint handler.
 */
interface HandlerInterface {
	/**
	 * Answer a request.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response;
}
