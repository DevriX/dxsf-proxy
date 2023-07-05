<?php
/**
 * The core plugin class.
 *
 * This is used to define attributes, functions, internationalization used across
 * both the admin-specific hooks, and public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @link       https://devrix.com
 * @since      1.0.0
 *
 * @package    Dxsf_Proxy
 * @subpackage Dxsf_Proxy/includes/classes
 * @author     DevriX <contact@devrix.com>
 */

namespace Dxsf_proxy;

/**
 * Core class.
 */
class Dxsf_Proxy {

	/**
	 * The loader that's responsible for maintaining and registering all hooks
	 * that power the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		$this->plugin_name = 'dxsf-proxy';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Loader. Orchestrates the hooks of the plugin.
	 * - i18n. Defines internationalization functionality.
	 * - Admin. Defines all hooks for the admin area.
	 * - Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		if ( function_exists( 'spl_autoload_register' ) ) {
			spl_autoload_register(
				function( $class ) {
					$class      = str_replace( '_', '-', strtolower( $class ) );
					$path_array = explode( '\\', $class );

					if ( $this->plugin_name === $path_array[0] ) {
						array_shift( $path_array );
						$index                = count( $path_array ) - 1;
						$path_array[ $index ] = 'class-' . $path_array[ $index ];
						$class_path           = implode( DIRECTORY_SEPARATOR, $path_array );
						$classpath            = DXSF_PROXY_DIR . 'includes/classes' . DIRECTORY_SEPARATOR . $class_path . '.php';

						if ( file_exists( $classpath ) ) {
							include_once $classpath;
						}
					}
				}
			);
			// Run the loader.
			$this->loader = new Loader();
		}

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the I18n class in order to set the domain and to
	 * register the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Admin( $this->get_plugin_name_() );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_dxsf_menu_page' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_dxsf_settings' );


		$updater = new Updater\Update();

		$this->loader->add_filter( 'plugins_api', $updater, 'info', 20, 3 );
		$this->loader->add_filter( 'site_transient_update_plugins', $updater, 'update' );
		$this->loader->add_action( 'upgrader_process_complete', $updater, 'purge', 10, 2 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		// Instantiates a new object of the class Common.
		$plugin_public = new API( $this->get_plugin_name_() );

		$this->loader->add_action( 'rest_api_init', $plugin_public, 'register_rest_api_endpoints' );
		$this->loader->add_action( 'rest_pre_echo_response', $plugin_public, 'hide_rest_api_endpoint' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name_() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}
