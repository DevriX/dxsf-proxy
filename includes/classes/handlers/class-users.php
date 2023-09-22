<?php
namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

class users implements HandlerInterface {

	public function handle( WP_REST_Request $request ): WP_REST_Response {

		$email_extensions = get_option( 'dxsf_email_extensions' );

		if ( empty( $email_extensions ) ) {
			return new WP_REST_Response( 'Email Extensions not set', 200 );
		}

		global $wpdb;

		$email_extensions = explode( ',', $email_extensions );

		foreach ( $email_extensions as $index => $extension ) {
			$extension = trim( $extension );

			if ( empty( $extension ) ) {
				unset( $email_extensions[ $index ] );
			} else {
				$email_extensions[ $index ] = "user_email LIKE '%@" . $wpdb->esc_like( $extension ) . "'";
			}
		}

		$where = 'WHERE (' . implode( ' OR ', $email_extensions ) . ')';

		$users = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, display_name, user_email FROM $wpdb->users $where"
			)
		);

		$is_multisite = is_multisite();

		$response = array();

		foreach ( $users as $user ) {

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
