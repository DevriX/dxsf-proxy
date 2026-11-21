<?php
/**
 * Theme version endpoint: the active theme and its parent.
 *
 * @since 1.0.0
 * @package Dxsf_Proxy
 */

namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Theme version handler.
 */
class Theme_Version implements HandlerInterface {

	/**
	 * Return the active theme and parent.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {

		$parent = get_template();
		$child  = get_stylesheet();

		$parent_theme = wp_get_theme( $parent );
		$child_theme  = wp_get_theme( $child );

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
