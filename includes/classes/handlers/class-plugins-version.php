<?php
namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

class plugins_version implements HandlerInterface {

	public function handle( WP_REST_Request $request ) : WP_REST_Response {

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