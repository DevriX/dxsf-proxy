<?php
namespace Dxsf_proxy\Updater;

class Update {

	public $plugin_slug;
	public $version;
	public $cache_key;
	public $cache_allowed;

	public function __construct() {

		$this->plugin_slug = plugin_basename( DXSF_PROXY_DIR );
		$this->version = DXSF_PROXY_VERSION;
		$this->cache_key = 'dxsf_proxy_update';
		$this->cache_allowed = false;
	}

	public function request() {

		$remote = get_transient( $this->cache_key );

		if( false === $remote || ! $this->cache_allowed ) {

			// read the info.json file in the same folder
			$remote = file_get_contents( DXSF_PROXY_DIR . '/includes/classes/updater/info.json' );

			// $remote = wp_remote_get(
			// 	'https://rudrastyh.com/wp-content/uploads/updater/info.json',
			// 	array(
			// 		'timeout' => 10,
			// 		'headers' => array(
			// 			'Accept' => 'application/json'
			// 		)
			// 	)
			// );

			// if(
			// 	is_wp_error( $remote )
			// 	|| 200 !== wp_remote_retrieve_response_code( $remote )
			// 	|| empty( wp_remote_retrieve_body( $remote ) )
			// ) {
			// 	return false;
			// }

			set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

		}

		$remote = json_decode( $remote );

		return $remote;

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
		$remote = $this->request();

		if( ! $remote ) {
			return $res;
		}

		$res = new \stdClass();

		$res->name = $remote->name;
		$res->slug = $remote->slug;
		$res->version = $remote->version;
		$res->author = $remote->author;
		$res->download_link = $remote->download_url;
		$res->trunk = $remote->download_url;

		return $res;

	}

	public function update( $transient ) {

		if ( empty($transient->checked ) ) {
			return $transient;
		}

		$remote = $this->request();

		if (
			$remote
			&& version_compare( $this->version, $remote->version, '<' )
		) {

			$res = new \stdClass();
			$res->slug = $this->plugin_slug;
			$res->plugin = plugin_basename( DXSF_PROXY_DIR . '/dxsf-proxy.php' ); // misha-update-plugin/misha-update-plugin.php
			$res->new_version = $remote->version;
			$res->package = $remote->download_url;

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