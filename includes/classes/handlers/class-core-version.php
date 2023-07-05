<?php
namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

class core_version implements HandlerInterface {

	public function handle( WP_REST_Request $request ) : WP_REST_Response {

		$core_version = get_bloginfo( 'version' );

		return new WP_REST_Response( $core_version, 200 );
	}
	
}