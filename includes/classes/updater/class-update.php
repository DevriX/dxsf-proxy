<?php
namespace Dxsf_proxy\Updater;

class Update {

	public $plugin_slug;
	public $version;
	public $cache_key;
	public $cache_allowed;
	public $package_name;

	public function __construct() {

		$this->plugin_slug   = plugin_basename( DXSF_PROXY_DIR );
		$this->package_name  = 'dxsf-wordpress-proxy.zip';
		$this->version       = DXSF_PROXY_VERSION;
		$this->cache_key     = 'dxsf_proxy_update';
		$this->cache_allowed = false;
	}

	public function get_remote_version() {

		$remote_version = get_transient( $this->cache_key );

		if( false === $remote_version || ! $this->cache_allowed ) {

			$remote = wp_remote_get(
				'https://raw.githubusercontent.com/DevriX/dxsf-proxy/master/dxsf-proxy.php',
				array(
					'timeout' => 10,
					'headers' => array(
						'Accept' => 'application/json'
					)
				)
			);

			if(
				is_wp_error( $remote )
				|| 200 !== wp_remote_retrieve_response_code( $remote )
				|| empty( wp_remote_retrieve_body( $remote ) )
			) {
				return false;
			}

			$remote = wp_remote_retrieve_body( $remote );

			preg_match( '/^\s*\* Version:\s*(.*)$/im', $remote, $matches );

			if( empty( $matches[1] ) ) {
				return false;
			}

			$remote_version = $matches[1];

			set_transient( $this->cache_key, $remote_version, DAY_IN_SECONDS );

		}

		return $remote_version;

	}


	function info( $res, $action, $args ) {

		// do nothing if you're not getting plugin information right now
		if( 'plugin_information' !== $action ) {
			return $res;
		}

		// do nothing if it is not our plugin
		if( $this->plugin_slug !== $args->slug ) {
			return $res;
		}

		// get updates
		$remote_version = $this->get_remote_version();

		if( ! $remote_version ) {
			return $res;
		}

		$res = new \stdClass();

		$res->name          = 'DXSF Proxy';
		$res->slug          = $this->plugin_slug;
		$res->version       = $remote_version;
		$res->author        = 'DevriX';
		$res->download_link = 'https://github.com/DevriX/dxsf-proxy/releases/latest/download/' . $this->package_name;
		$res->trunk         = 'https://github.com/DevriX/dxsf-proxy/releases/latest/download/' . $this->package_name;

		return $res;

	}

	public function update( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_version = $this->get_remote_version();

		if (
			$remote_version
			&& version_compare( $this->version, $remote_version, '<' )
		) {
			$res = new \stdClass();
			$res->slug          = $this->plugin_slug;
			$res->plugin        = plugin_basename( DXSF_PROXY_DIR . '/dxsf-proxy.php' ); // misha-update-plugin/misha-update-plugin.php
			$res->new_version   = $remote_version;
			$res->author        = 'DevriX';
			$res->download_link = 'https://github.com/DevriX/dxsf-proxy/releases/latest/download/' . $this->package_name;
			$res->trunk         = 'https://github.com/DevriX/dxsf-proxy/releases/latest/download/' . $this->package_name;
			$res->package       = 'https://github.com/DevriX/dxsf-proxy/releases/latest/download/' . $this->package_name;

			$transient->response[ $res->plugin ] = $res;
		}

		return $transient;
	}

	public function purge( $upgrader, $options ) {

		if (
			$this->cache_allowed
			&& 'update' === $options['action']
			&& 'plugin' === $options[ 'type' ]
		) {
			// just clean the cache when new plugin version is installed
			delete_transient( $this->cache_key );
		}
	}
}
