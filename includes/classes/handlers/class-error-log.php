<?php
/**
 * Error log endpoint: the log lines from a given day onwards.
 *
 * @since 1.0.0
 * @package Dxsf_Proxy
 */

namespace Dxsf_proxy\Handlers;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Error log handler.
 */
class Error_Log implements HandlerInterface {

	/**
	 * Return the error log from the requested date.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$log = self::read( $request->get_param( 'date' ) );

		if ( is_wp_error( $log ) ) {
			return new WP_REST_Response( $log->get_error_message(), 404 );
		}

		return new WP_REST_Response( $log, 200 );
	}

	/**
	 * Read the configured log from a date onwards. Used by the endpoint and the settings page.
	 *
	 * @param string|null $date Any strtotime() date, today when empty.
	 * @return string|WP_Error
	 */
	public static function read( $date = null ) {
		$error_path = get_option( 'dxsf_error_log_file' );

		if ( empty( $error_path ) ) {
			return new WP_Error( 'dxsf_no_log_path', 'Error log path not set' );
		}

		if ( ! self::exists( $error_path ) ) {
			return new WP_Error( 'dxsf_no_log_file', 'Error log file not found' );
		}

		$date_format = get_option( 'dxsf_date_format', '[d-M-Y' );
		$timestamp   = empty( $date ) ? false : strtotime( (string) $date );
		$day         = gmdate( $date_format, false === $timestamp ? time() : $timestamp );

		return self::fetch_error_log_data( $error_path, $day );
	}

	/**
	 * Whether the log is a readable file or a URL that answers 200.
	 *
	 * @param string $error_path Path or URL.
	 * @return bool
	 */
	public static function exists( $error_path ) {
		if ( file_exists( $error_path ) ) {
			return is_readable( $error_path );
		}

		if ( ! wp_http_validate_url( $error_path ) ) {
			return false;
		}

		$response = wp_safe_remote_head( $error_path, array( 'timeout' => 10 ) );

		return ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Most of the day's log that is returned. Past it, only fatal and parse errors are kept,
	 * so a log full of notices can neither exhaust memory nor hide a fatal.
	 */
	const MAX_BYTES = 2097152;

	/**
	 * Lines from the first one that starts with the date, to the end of the log.
	 *
	 * @param string $file Local path, or an http(s) URL.
	 * @param string $date Date prefix in the log's format.
	 * @return string
	 */
	public static function fetch_error_log_data( $file, $date ) {
		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- streamed, logs can be large.
			$handle = fopen( $file, 'r' );
		} else {
			// URLs go through the WordPress HTTP API, which refuses internal addresses and other stream wrappers.
			$response = wp_safe_remote_get(
				$file,
				array(
					'timeout'             => 20,
					'limit_response_size' => 50 * self::MAX_BYTES,
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				return '';
			}

			$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
			fwrite( $handle, wp_remote_retrieve_body( $response ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			rewind( $handle );
		}

		if ( false === $handle ) {
			return '';
		}

		$error_log  = '';
		$in_range   = false;
		$keep_trace = false;

		while ( ( $line = fgets( $handle, 65535 ) ) !== false ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			if ( ! $in_range ) {
				if ( 0 !== strpos( $line, $date ) ) {
					continue;
				}

				$in_range = true;
			}

			if ( strlen( $error_log ) < self::MAX_BYTES ) {
				$error_log .= $line;
				continue;
			}

			// Over the cap: fatal and parse errors only, with the trace lines that follow them.
			if ( '[' === substr( $line, 0, 1 ) ) {
				$keep_trace = false !== strpos( $line, 'PHP Fatal error' ) || false !== strpos( $line, 'PHP Parse error' );
			}

			if ( $keep_trace ) {
				$error_log .= $line;
			}
		}

		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $error_log;
	}
}
