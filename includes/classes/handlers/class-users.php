<?php
/**
 * Users endpoint: the accounts whose email matches the configured domains.
 *
 * @since 1.0.0
 * @package Dxsf_Proxy
 */

namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Users handler.
 */
class Users implements HandlerInterface {

	/**
	 * Return the matching users.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$email_extensions = get_option( 'dxsf_email_extensions' );

		if ( empty( $email_extensions ) ) {
			return new WP_REST_Response( 'Email Extensions not set', 404 );
		}

		return new WP_REST_Response( self::find( $email_extensions ), 200 );
	}

	/**
	 * Users whose email ends with one of the given domains.
	 *
	 * @param string $email_extensions Comma separated domains, e.g. "devrix.com,abv.bg".
	 * @return array
	 */
	public static function find( $email_extensions ) {
		global $wpdb;

		$likes = array();

		foreach ( explode( ',', (string) $email_extensions ) as $extension ) {
			$extension = trim( $extension );

			if ( '' !== $extension ) {
				$likes[] = '%@' . $wpdb->esc_like( $extension );
			}
		}

		if ( empty( $likes ) ) {
			return array();
		}

		$where = implode( ' OR ', array_fill( 0, count( $likes ), 'user_email LIKE %s' ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- $where holds the placeholders; one read per push.
		$users = $wpdb->get_results( $wpdb->prepare( "SELECT ID, display_name, user_email FROM {$wpdb->users} WHERE {$where}", $likes ) );

		$is_multisite = is_multisite();
		$response     = array();

		foreach ( $users as $user ) {
			$user_info = array(
				'id'    => (int) $user->ID,
				'name'  => $user->display_name,
				'email' => $user->user_email,
				'sites' => '',
			);

			if ( $is_multisite ) {
				$user_info['sites'] = implode( ',', wp_list_pluck( get_blogs_of_user( $user->ID ), 'userblog_id' ) );
			}

			$response[] = $user_info;
		}

		return $response;
	}
}
