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

		$user       = wp_get_current_user();
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
			<hr>
			<h2>Monitoring</h2>
			<p><strong>Debug log</strong></p>
			<?php if ( empty( get_option( 'dxsf_error_log_file' ) ) ) : ?>
				<p>There is no error log file path set.</p>
			<?php else :
				$date = gmdate( 'Y-m-d' );
				if ( isset( $_GET['date'] ) ) {
					$date = $_GET['date'];
				}

				$response = wp_remote_get(
					rest_url( 'dxsf-proxy/v1/error-log' ) . '?date=' . $date . '&dxdoneonsameserver=true',
					array(
						'sslverify' => false,
					)
				);

				$error_log = wp_remote_retrieve_body( $response );

				// Format the error log
				$error_log = str_replace( "\\n", '<br>', esc_html( $error_log ) );
				$error_log = str_replace( "\\t", '&nbsp;&nbsp;&nbsp;&nbsp;', $error_log );
				$error_log = str_replace( ' ', '&nbsp;', $error_log );

				// Color coat the error log
				$error_log = str_replace( 'PHP&nbsp;Fatal&nbsp;error', '<span style="color: red">PHP&nbsp;Fatal&nbsp;error</span>', $error_log );
				$error_log = str_replace( 'PHP&nbsp;Warning', '<span style="color: orange">PHP&nbsp;Warning</span>', $error_log );
				$error_log = str_replace( 'PHP&nbsp;Notice', '<span style="color: blue">PHP&nbsp;Notice</span>', $error_log );
				$error_log = str_replace( 'PHP&nbsp;Parse&nbsp;error', '<span style="color: red">PHP&nbsp;Parse&nbsp;error</span>', $error_log );
				$error_log = str_replace( 'PHP&nbsp;Deprecated', '<span style="color: purple">PHP&nbsp;Deprecated</span>', $error_log );
				$error_log = str_replace( 'PHP&nbsp;Unknown&nbsp;error', '<span style="color: red">PHP&nbsp;Unknown&nbsp;error</span>', $error_log );
				?>
				<form method="GET" action="" >
					<label for="date">Show error log starting from:</label>
					<input type="date" name="date" value="<?php echo date( 'Y-m-d' ); ?>" >
					<?php wp_nonce_field( 'dxsf_proxy' ); ?>
					<input type="hidden" name="page" value="dxsf-settings">
					<input type="submit" value="Show">
				</form>
				<?php if ( ! empty( $error_log ) ) : ?>
					<!-- Count amount of errors -->
					<?php
					$php_fatal_error_count   = substr_count( $error_log, 'PHP&nbsp;Fatal&nbsp;error' );
					$php_warning_count       = substr_count( $error_log, 'PHP&nbsp;Warning' );
					$php_notice_count        = substr_count( $error_log, 'PHP&nbsp;Notice' );
					$php_parse_error_count   = substr_count( $error_log, 'PHP&nbsp;Parse&nbsp;error' );
					$php_deprecated_count    = substr_count( $error_log, 'PHP&nbsp;Deprecated' );
					$php_unknown_error_count = substr_count( $error_log, 'PHP&nbsp;Unknown&nbsp;error' );
					?>
					<span><strong>Fatal errors</strong> - <?php echo $php_fatal_error_count; ?>;</span>
					<span><strong>Warnings</strong> - <?php echo $php_warning_count; ?>;</span>
					<span><strong>Notices</strong> - <?php echo $php_notice_count; ?>;</span>
					<span><strong>Parse errors</strong> - <?php echo $php_parse_error_count; ?>;</span>
					<span><strong>Deprecated</strong> - <?php echo $php_deprecated_count; ?>;</span>
					<span><strong>Unknown errors</strong> - <?php echo $php_unknown_error_count; ?>;</span>
					
					<div style="height: 500px; overflow: auto; border: 1px solid #ccc; padding: 10px; margin-top: 10px;"><?php echo $error_log; ?></div>
				<?php else : ?>
					<p>There is no error log for this date.</p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public function register_dxsf_settings() {
		add_settings_section(
			'dxsf-settings-section',
			'Configuration',
			false,
			'dxsf-settings-section'
		);

		register_setting(
			'dxsf-settings-group',
			'dxsf_error_log_file'
		);

		register_setting(
			'dxsf-settings-group',
			'dxsf_date_format'
		);

		register_setting(
			'dxsf-settings-group',
			'dxsf_remote_address'
		);

		register_setting(
			'dxsf-settings-group',
			'dxsf_email_extensions'
		);

		add_settings_field(
			'dxsf_error_log_file',
			'Path to the error log file',
			array( $this, 'render_dxsf_error_log_file_field' ),
			'dxsf-settings-section',
			'dxsf-settings-section'
		);

		add_settings_field(
			'dxsf_date_format',
			'Log date format',
			array( $this, 'render_dxsf_date_format_field' ),
			'dxsf-settings-section',
			'dxsf-settings-section'
		);

		add_settings_field(
			'dxsf_remote_address',
			'Remote address',
			array( $this, 'render_dxsf_remote_address_field' ),
			'dxsf-settings-section',
			'dxsf-settings-section'
		);

		add_settings_field(
			'dxsf_email_extensions',
			'Email extensions',
			array( $this, 'render_dxsf_email_extensions_field' ),
			'dxsf-settings-section',
			'dxsf-settings-section'
		);
	}

	/**
	 * It creates a text input field with the name of dxsf_error_log_file and the value of the option
	 * dxsf_error_log_file
	 */
	public function render_dxsf_error_log_file_field() {
		$error_log_file = get_option( 'dxsf_error_log_file' );

		if ( ! empty( $error_log_file ) ) {
			$response = wp_remote_get(
				get_site_url() . '/wp-json/dxsf-proxy/v1/error-log',
				array(
					'sslverify' => false,
				)
			);

			$file_exists = false;
			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				$file_exists = true;
			}
		}

		?>
		<?php if ( ! empty( $error_log_file ) && ! $file_exists ) : ?>
			<div class="notice notice-error" style="padding: 10px 10px">The error log file does not exist.</div>
		<?php endif; ?>

		<input type="text" name="dxsf_error_log_file" value="<?php echo esc_attr( $error_log_file ); ?>" size="50"/>
		<div class="dxsf-info-messages">The plugin does the call from root/wp-content/plugins/dxsf-wordpress-proxy/includes/classes/handlers/, so you have to make sure the path to the error log will match</div>
		<div class="dxsf-info-messages">e.g. the path might be like ../../../../../../../../../../../mnt/log/php.error.log</div>
		<div class="dxsf-info-messages">An endpoint URL can also be used here. Make sure you add the full URL, including the <em>https://</em> part.</div>
		<div class="dxsf-info-messages">Your local debug.log is here: <strong><?php echo WP_CONTENT_DIR; ?>/debug.log</strong></div>
		<?php
	}

	public function render_dxsf_date_format_field() {
		$date_format = get_option( 'dxsf_date_format', '[d-M-Y' );
		?>
		<input type="text" name="dxsf_date_format" value="<?php echo esc_attr( $date_format ); ?>" size="50"/>
		<div class="dxsf-info-messages">The date format of the error log file.</div>
		<div class="dxsf-info-messages"><strong>[d-M-Y</strong> is used by default.</div>
		<?php
	}

	/**
	 * It creates a text input field with the name of dxsf_remote_address and the value of the option
	 * dxsf_remote_address
	 */
	public function render_dxsf_remote_address_field() {
		$remote_address = get_option( 'dxsf_remote_address' );
		?>
		<input type="text" name="dxsf_remote_address" value="<?php echo esc_attr( $remote_address ); ?>" size="50"/>
		<div class="dxsf-info-messages">The IP address of the DX Stability Framework server.</div>

		<?php if ( defined( 'DXSF_REMOTE' ) ) : ?>
			<div style="color:green" class="dxsf-info-messages">The IP address of the DX Stability Framework server is defined in the wp-config.php file.</div>
			<?php
		endif;
	}

	/**
	 * It creates a text input field with the name of dxsf_email_extensions and the value of the option
	 * dxsf_email_extensions
	 */
	public function render_dxsf_email_extensions_field() {
		$email_extensions = get_option( 'dxsf_email_extensions' );
		?>
		<input type="text" name="dxsf_email_extensions" value="<?php echo esc_attr( $email_extensions ); ?>"/>
		<div class="dxsf-info-messages">The email extensions of the users that will returned on the /users endpoint.</div>
		<div class="dxsf-info-messages">e.g. devrix.com,abv.bg,kindamail.com,kinda.email,kmail.live</div>
		<div class="dxsf-info-messages">If you want to add more than one email extension, separate them with a comma.</div>
		<?php
	}
}
