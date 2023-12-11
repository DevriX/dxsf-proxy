<?php
namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

class error_log implements HandlerInterface {

	public function handle( WP_REST_Request $request ): WP_REST_Response {

		$error_path = get_option( 'dxsf_error_log_file' );

		if ( empty( $error_path ) ) {
			return new WP_REST_Response( 'Error log path not set', 404 );
		}

		if ( ! file_exists( $error_path ) ) {
			$file_headers = @get_headers( $error_path );

			if ( ! $file_headers || ! str_contains( $file_headers[0], '200' ) ) {
				return new WP_REST_Response( 'Error log file not found', 404 );
			}
		}

		$date_format = get_option( 'dxsf_date_format', '[d-M-Y' );

		$today = date( $date_format );

		if ( $request->get_param( 'date' ) ) {

			$today = date( $date_format, strtotime( $request->get_param( 'date' ) ) );
		}

		$error_log = $this->fetch_error_log_data( $error_path, $today );

		return new WP_REST_Response( $error_log, 200 );
	}

	public static function fetch_error_log_data( $file, $date, ) {
		$handle = @fopen( $file, 'r' );

		$error_log         = '';
		$proper_date_range = false; // By default, we're not in today's range

		// Try to read lines one by one
		// First rows would be
		while ( ( $line = fgets( $handle, 65535 ) ) !== false ) {
			// Already in the right range at the end
			if ( $proper_date_range ) {
				$error_log .= $line . PHP_EOL;
			} elseif ( 0 === strpos( $line, "$date" ) ) { // If we start with today's date
				$proper_date_range = true;
				$error_log        .= $line . PHP_EOL;
			} else {
				continue; // skip
			}
		}
		if ( ! feof( $handle ) ) {
			$error_log .= "Error: unexpected fgets() fail\n";
		}
		fclose( $handle );

		return $error_log;
	}
}
