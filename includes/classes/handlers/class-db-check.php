<?php
namespace Dxsf_proxy\Handlers;

use WP_REST_Request;
use WP_REST_Response;

class db_check implements HandlerInterface {

	/**
	 * Handle the request.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {

		return new WP_REST_Response(
			$this->scan_database_for_malicious_code(),
		);
	}

	public function scan_database_for_malicious_code() {
		global $wpdb;

		// Define some patterns commonly found in malicious code.
		$patterns = array(
			'base64_decode(',
			'eval(',
			'gzinflate(',
			'str_rot13(',
			'assert(',
			'shell_exec(',
			'exec(',
			'passthru(',
			'system(',
			'popen(',
			'proc_open(',
			'pcntl_exec(',

			// SQL Injection patterns.
			'UNION ALL SELECT',
			'1=1',

			// Common PHP shells.
			'c99shell',
			'r57shell',
			'WebShell',

			// Backdoors and other signatures.
			'FilesMan',
			'JGF1dGhfc',
			'Uploader',

			// Other suspicious patterns.
			'$_REQUEST[',
			'$_POST[',
			'$_GET[',
			'@$_COOKIE',
			'@$_REQUEST',

			// Common encoding/obfuscation techniques.
			'gzuncompress(',
			'rot13(',
			'bin2hex(',
			'hex2bin(',
			'unpack(',

			// Bypass techniques.
			'disable_functions',
			'safe_mode',
			'open_basedir',
			'auto_prepend_file',
			'auto_append_file',

			// PHP object injection.
			'O:[0-9]+:"',

			// Accessing external servers.
			'file_get_contents(',
			'curl_init(',
			'curl_exec(',
			'fsockopen(',
			'pfsockopen(',
		);

		$suspicious_entries = array();

		// Get all tables in the database.
		$tables = $wpdb->get_col( 'SHOW TABLES' );

		foreach ( $tables as $table ) {
			$columns = $wpdb->get_col( "DESCRIBE $table" );

			foreach ( $columns as $column ) {
				foreach ( $patterns as $pattern ) {
					$query   = $wpdb->prepare( "SELECT * FROM $table WHERE $column LIKE %s", '%' . $pattern . '%' );
					$results = $wpdb->get_results( $query );

					if ( $results ) {
						foreach ( $results as $result ) {
							$suspicious_entries[] = array(
								'table'   => $table,
								'column'  => $column,
								'pattern' => $pattern,
								'entry'   => $result,
							);
						}
					}
				}
			}
		}

		return $suspicious_entries;
	}
}
