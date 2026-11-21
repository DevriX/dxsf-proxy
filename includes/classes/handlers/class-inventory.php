<?php
/**
 * Inventory endpoint: the same payload as the push, for "refresh now" from the core.
 *
 * @since 3.0.0
 * @package Dxsf_Proxy
 */

namespace Dxsf_proxy\Handlers;

use Dxsf_proxy\Inventory as Site_Inventory;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Inventory handler.
 */
class Inventory implements HandlerInterface {

	/**
	 * Return the full inventory.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( Site_Inventory::collect(), 200 );
	}
}
