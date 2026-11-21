<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * @link       https://devrix.com
 * @since      1.0.0
 *
 * @package    Dxsf_Proxy
 * @author     DevriX <contact@devrix.com>
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$dxsf_options = array(
	'dxsf_core_url',
	'dxsf_site_id',
	'dxsf_site_key',
	'dxsf_site_status',
	'dxsf_min_version',
	'dxsf_latest_version',
	'dxsf_last_push',
	'dxsf_error_log_file',
	'dxsf_date_format',
	'dxsf_remote_address',
	'dxsf_email_extensions',
);

foreach ( $dxsf_options as $dxsf_option ) {
	delete_option( $dxsf_option );
}

wp_clear_scheduled_hook( 'dxsf_proxy_push' );
delete_transient( 'dxsf_proxy_update' );
