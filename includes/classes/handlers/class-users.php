<?php
namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

class users implements HandlerInterface {

	public function handle( WP_REST_Request $request ) : WP_REST_Response {

		$users = get_users(
			array(
				'fields'      => array( 'ID', 'display_name', 'user_email' ),
				'count_total' => false,
				'blog_id'     => '0',
			)
		);

		$is_multisite = is_multisite();

		$email_extensions = get_option( 'dxsf_email_extensions' );

		if ( empty( $email_extensions ) ) {
			return new WP_REST_Response( 'Email Extensions not set', 200 );
		}

		$email_extensions = explode( ',', $email_extensions );

		$response = array();

		foreach ( $users as $user ) {

			try {
				$extension = explode( '@', $user->user_email)[1];
			} catch ( \Exception $e ) {
				continue;
			}

			if ( ! in_array( $extension, $email_extensions ) ) {
				continue;
			}

			$user_info = array(
				'id'    => $user->ID,
				'name'  => $user->display_name,
				'email' => $user->user_email,
				'sites' => '',
			);

			if ( $is_multisite ) {
				$user_blogs = get_blogs_of_user( $user->ID );

				$user_info['sites'] = array();

				foreach ( $user_blogs as $user_blog ) {
					$user_info['sites'][] = $user_blog->userblog_id;
				}

				$user_info['sites'] = implode( ',', $user_info['sites'] );
			}

			$response[] = $user_info;
		}

		return new WP_REST_Response( $response, 200 );
	}
}
