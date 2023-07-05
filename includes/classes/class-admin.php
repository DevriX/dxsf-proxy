<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
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
 * Admin class.
 */
class Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param string $plugin_name       The name of this plugin.
	 */
	public function __construct( $plugin_name ) {

		$this->plugin_name = $plugin_name;

	}

	/**
	 * It adds a menu page to the admin menu
	 */
	public function add_dxsf_menu_page() {

		// get current user email

		$user = wp_get_current_user();
		$user_email = $user->user_email;

		if ( str_contains( $user_email, '@devrix.com' ) || DXSF_DEBUG ) {
			add_menu_page(
				'DXSF Settings',
				'DXSF Settings',
				'manage_options',
				'dxsf-settings',
				array( $this, 'create_dxsf_settings_page' )
			);
		}
	}

	/**
	 * It creates a settings page for the plugin
	 */
	public function create_dxsf_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'dxsf-settings-group' ); ?>
				<?php do_settings_sections( 'dxsf-settings-section' ); ?>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function register_dxsf_settings() {
		add_settings_section(
			'dxsf-settings-section',
			'DXSF Settings',
			false,
			'dxsf-settings-section'
		);

		register_setting(
			'dxsf-settings-group',
			'dxsf_error_log_file'
		);

		// register_setting(
		// 	'dxsf-settings-group',
		// 	'dxsf_project_title'
		// );

		// register_setting(
		// 	'dxsf-settings-group',
		// 	'dxsf_log_days'
		// );

		add_settings_field(
			'dxsf_error_log_file',
			'Path to the error log file',
			array( $this, 'render_dxsf_error_log_file_field' ),
			'dxsf-settings-section',
			'dxsf-settings-section'
		);

		// add_settings_field(
		// 	'dxsf_project_title',
		// 	'Project Title',
		// 	array( $this, 'render_dxsf_project_title_field' ),
		// 	'dxsf-settings-section',
		// 	'dxsf-settings-section'
		// );

		// add_settings_field(
		// 	'dxsf_log_days',
		// 	'Days to keep the log files',
		// 	array( $this, 'render_dxsf_log_days_field' ),
		// 	'dxsf-settings-section',
		// 	'dxsf-settings-section'
		// );
	}

	/**
	 * It creates a text input field with the name of dxsf_error_log_file and the value of the option
	 * dxsf_error_log_file
	 */
	public function render_dxsf_error_log_file_field() {
		$error_log_file = get_option( 'dxsf_error_log_file' );
		echo '<input type="text" name="dxsf_error_log_file" value="' . esc_attr( $error_log_file ) . '" size="50"/>';
		echo '<div class="dxsf-info-messages">The plugin does the call from root/wp-content/plugins/dxsf-wordpress-proxy/includes/classes/handlers/, so you have to make sure the path to the error log will match</div>';
		echo '<div class="dxsf-info-messages">e.g. the path might be like ../../../../../../../../../../../mnt/log/php.error.log</div>';
		echo '<div class="dxsf-info-messages">An endpoint URL can also be used here. Make sure you add the full URL, including the <em>https://</em> part.</div>';
	}

	/**
	 * It creates a text input field with the name of dxsf_project_title and the value of the option
	 * dxsf_project_title
	 */
	// public function render_dxsf_project_title_field() {
	// 	$slack_title_message = get_option( 'dxsf_project_title' );
	// 	echo '<input type="text" name="dxsf_project_title" value="' . esc_attr( $slack_title_message ) . '" size="80"/>';
	// }

	/**
	 * It creates a text input field with the name "dxsf_log_days" and the value of the option
	 * "dxsf_log_days" (which is stored in the database)
	 */
	// public function render_dxsf_log_days_field() {
	// 	$log_days = get_option( 'dxsf_log_days' );
	// 	echo '<input type="number" name="dxsf_log_days" value="' . esc_attr( $log_days ) . '" size="1"/>';
	// }

}
