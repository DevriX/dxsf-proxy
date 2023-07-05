<?php
namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

class theme_version implements HandlerInterface {

	public function handle( WP_REST_Request $request ) : WP_REST_Response {

		$parent = get_template();
		$child = get_stylesheet();

		$parent_theme = wp_get_theme( $parent );
		$child_theme = wp_get_theme( $child );

		$response = array(
			'parent_theme'   => $parent_theme->get( 'Name' ),
			'child_theme'    => $child_theme->get( 'Name' ),
			'parent_version' => $parent_theme->get( 'Version' ),
			'child_version'  => $child_theme->get( 'Version' ),
			'parent_slug'    => $parent,
			'child_slug'     => $child,
		);

		return new WP_REST_Response( $response, 200 );
	}
	
}