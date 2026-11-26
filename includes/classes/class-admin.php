<?php
/**
 * The settings page, shown to DevriX administrators only.
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
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE = 'dxsf-settings';

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 */
	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Whether the current user may see the page: an administrator with a devrix.com address.
	 *
	 * @return bool
	 */
	public static function is_devrix_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		$email = strtolower( (string) wp_get_current_user()->user_email );

		return '@devrix.com' === substr( $email, -11 );
	}

	/**
	 * Add the menu page.
	 */
	public function add_dxsf_menu_page() {
		if ( ! self::is_devrix_admin() ) {
			return;
		}

		add_menu_page(
			__( 'DXSF Settings', 'dxsf-proxy' ),
			__( 'DXSF Settings', 'dxsf-proxy' ),
			'manage_options',
			self::PAGE,
			array( $this, 'create_dxsf_settings_page' )
		);
	}

	/**
	 * Register the monitoring settings saved through options.php.
	 */
	public function register_dxsf_settings() {
		// Unregistered for everyone else, so options.php refuses the group: a site admin without a
		// devrix.com address can neither see nor post these settings.
		if ( ! self::is_devrix_admin() ) {
			return;
		}

		add_settings_section( 'dxsf-settings-section', __( 'Monitoring', 'dxsf-proxy' ), '__return_false', 'dxsf-settings-section' );

		register_setting( 'dxsf-settings-group', 'dxsf_error_log_file', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'dxsf-settings-group', 'dxsf_date_format', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'dxsf-settings-group', 'dxsf_remote_address', array( 'sanitize_callback' => array( $this, 'sanitize_ip' ) ) );
		register_setting( 'dxsf-settings-group', 'dxsf_email_extensions', array( 'sanitize_callback' => 'sanitize_text_field' ) );

		add_settings_field( 'dxsf_error_log_file', __( 'Path to the error log file', 'dxsf-proxy' ), array( $this, 'render_dxsf_error_log_file_field' ), 'dxsf-settings-section', 'dxsf-settings-section' );
		add_settings_field( 'dxsf_date_format', __( 'Log date format', 'dxsf-proxy' ), array( $this, 'render_dxsf_date_format_field' ), 'dxsf-settings-section', 'dxsf-settings-section' );
		add_settings_field( 'dxsf_email_extensions', __( 'Email extensions', 'dxsf-proxy' ), array( $this, 'render_dxsf_email_extensions_field' ), 'dxsf-settings-section', 'dxsf-settings-section' );
		add_settings_field( 'dxsf_remote_address', __( 'Legacy server address', 'dxsf-proxy' ), array( $this, 'render_dxsf_remote_address_field' ), 'dxsf-settings-section', 'dxsf-settings-section' );
	}

	/**
	 * Keep a valid IP address or nothing.
	 *
	 * @param string $value Submitted value.
	 * @return string
	 */
	public function sanitize_ip( $value ) {
		$value = trim( (string) $value );

		return filter_var( $value, FILTER_VALIDATE_IP ) ? $value : '';
	}

	/**
	 * Save the connection, from the form on the settings page.
	 */
	public function save_connection() {
		if ( ! self::is_devrix_admin() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'dxsf-proxy' ), 403 );
		}

		check_admin_referer( 'dxsf_connection' );

		Settings::save_connection(
			isset( $_POST['dxsf_core_url'] ) ? wp_unslash( $_POST['dxsf_core_url'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in Settings.
			isset( $_POST['dxsf_site_id'] ) ? wp_unslash( $_POST['dxsf_site_id'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in Settings.
			isset( $_POST['dxsf_site_key'] ) ? wp_unslash( $_POST['dxsf_site_key'] ) : '' // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized in Settings.
		);

		Client::schedule();

		// admin-post.php does not build the menu, so menu_page_url() is empty here.
		wp_safe_redirect( add_query_arg( 'dxsf-saved', '1', admin_url( 'admin.php?page=' . self::PAGE ) ) );
		exit;
	}

	/**
	 * Push the inventory now, from the settings page.
	 */
	public function push_now() {
		if ( ! self::is_devrix_admin() ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'dxsf-proxy' ), 403 );
		}

		check_admin_referer( 'dxsf_push_now' );

		Client::push( 'manual' );

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );
		exit;
	}

	/**
	 * Notices for DevriX administrators: a revoked site, an outdated proxy, a failing push.
	 */
	public function admin_notices() {
		if ( ! self::is_devrix_admin() ) {
			return;
		}

		if ( 'revoked' === Settings::status() ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'DXSF Proxy: this site is disconnected from the Stability Framework. Enter a new site key to reconnect.', 'dxsf-proxy' )
			);
			return;
		}

		$min = get_option( 'dxsf_min_version', '' );

		if ( $min && version_compare( DXSF_PROXY_VERSION, $min, '<' ) ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				/* translators: %s: minimum proxy version. */
				esc_html( sprintf( __( 'DXSF Proxy: the Stability Framework needs proxy version %s or newer. Update the plugin.', 'dxsf-proxy' ), $min ) )
			);
		}
	}

	/**
	 * Render the settings page.
	 */
	public function create_dxsf_settings_page() {
		if ( ! self::is_devrix_admin() ) {
			return;
		}

		$last = Settings::last_push();
		$next = wp_next_scheduled( Client::HOOK );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( isset( $_GET['dxsf-saved'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Connection saved.', 'dxsf-proxy' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Connection', 'dxsf-proxy' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dxsf_save_connection">
				<?php wp_nonce_field( 'dxsf_connection' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dxsf_core_url"><?php esc_html_e( 'Core URL', 'dxsf-proxy' ); ?></label></th>
						<td><?php $this->connection_field( 'core_url', 'url', Settings::core_url() ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="dxsf_site_id"><?php esc_html_e( 'Site ID', 'dxsf-proxy' ); ?></label></th>
						<td><?php $this->connection_field( 'site_id', 'text', Settings::site_id() ); ?></td>
					</tr>
					<tr>
						<th scope="row"><label for="dxsf_site_key"><?php esc_html_e( 'Site key', 'dxsf-proxy' ); ?></label></th>
						<td><?php $this->connection_field( 'site_key', 'password', '' ); ?>
							<p class="description">
								<?php
								echo esc_html(
									'' === Settings::site_key()
										? __( 'Not set. Copy it from the site page in the Stability Framework dashboard.', 'dxsf-proxy' )
										: __( 'Saved. Leave empty to keep it, paste a new one to replace it.', 'dxsf-proxy' )
								);
								?>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save connection', 'dxsf-proxy' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Status', 'dxsf-proxy' ); ?></h2>
			<table class="widefat striped" style="max-width: 720px">
				<tbody>
					<tr><td><?php esc_html_e( 'Proxy version', 'dxsf-proxy' ); ?></td><td><?php echo esc_html( DXSF_PROXY_VERSION ); ?></td></tr>
					<tr><td><?php esc_html_e( 'Site status', 'dxsf-proxy' ); ?></td><td><?php echo esc_html( Settings::connected() ? Settings::status() : __( 'not connected', 'dxsf-proxy' ) ); ?></td></tr>
					<tr>
						<td><?php esc_html_e( 'Last push', 'dxsf-proxy' ); ?></td>
						<td>
							<?php
							if ( $last['time'] ) {
								echo esc_html( wp_date( 'Y-m-d H:i', $last['time'] ) . ', ' . ( $last['ok'] ? __( 'ok', 'dxsf-proxy' ) : __( 'failed', 'dxsf-proxy' ) ) . ': ' . $last['message'] );
							} else {
								esc_html_e( 'never', 'dxsf-proxy' );
							}
							?>
						</td>
					</tr>
					<tr><td><?php esc_html_e( 'Next push', 'dxsf-proxy' ); ?></td><td><?php echo esc_html( $next ? wp_date( 'Y-m-d H:i', $next ) : __( 'not scheduled', 'dxsf-proxy' ) ); ?></td></tr>
				</tbody>
			</table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dxsf_push_now">
				<?php wp_nonce_field( 'dxsf_push_now' ); ?>
				<?php submit_button( __( 'Send inventory now', 'dxsf-proxy' ), 'secondary', 'submit', true, Settings::connected() ? array() : array( 'disabled' => 'disabled' ) ); ?>
			</form>

			<form method="post" action="options.php">
				<?php settings_fields( 'dxsf-settings-group' ); ?>
				<?php do_settings_sections( 'dxsf-settings-section' ); ?>
				<?php submit_button(); ?>
			</form>

			<hr>
			<?php $this->render_error_log(); ?>
		</div>
		<?php
	}

	/**
	 * One connection input, read-only when wp-config.php sets the value.
	 *
	 * @param string $name  core_url, site_id or site_key.
	 * @param string $type  Input type.
	 * @param string $value Current value, never the key.
	 */
	private function connection_field( $name, $type, $value ) {
		if ( Settings::locked( $name ) ) {
			printf(
				'<code>%s</code> <span class="description">%s</span>',
				esc_html( 'site_key' === $name ? '********' : $value ),
				/* translators: %s: constant name. */
				esc_html( sprintf( __( 'Set by %s in wp-config.php.', 'dxsf-proxy' ), 'DXSF_' . strtoupper( $name ) ) )
			);
			return;
		}

		printf(
			'<input type="%1$s" id="dxsf_%2$s" name="dxsf_%2$s" value="%3$s" class="regular-text" autocomplete="off">',
			esc_attr( $type ),
			esc_attr( $name ),
			esc_attr( $value )
		);
	}

	/**
	 * The error log viewer, read directly from the file.
	 */
	private function render_error_log() {
		echo '<h2>' . esc_html__( 'Debug log', 'dxsf-proxy' ) . '</h2>';

		if ( empty( get_option( 'dxsf_error_log_file' ) ) ) {
			echo '<p>' . esc_html__( 'There is no error log file path set.', 'dxsf-proxy' ) . '</p>';
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view.
		$date = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : '';
		$date = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? $date : gmdate( 'Y-m-d' );
		$log  = Handlers\Error_Log::read( $date );
		?>
		<form method="get" action="">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
			<label for="dxsf-date"><?php esc_html_e( 'Show error log starting from:', 'dxsf-proxy' ); ?></label>
			<input type="date" id="dxsf-date" name="date" value="<?php echo esc_attr( $date ); ?>">
			<input type="submit" class="button" value="<?php esc_attr_e( 'Show', 'dxsf-proxy' ); ?>">
		</form>
		<?php
		if ( is_wp_error( $log ) ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html( $log->get_error_message() ) . '</p></div>';
			return;
		}

		if ( '' === trim( $log ) ) {
			echo '<p>' . esc_html__( 'There is no error log for this date.', 'dxsf-proxy' ) . '</p>';
			return;
		}

		$levels = array(
			'PHP Fatal error'   => 'red',
			'PHP Parse error'   => 'red',
			'PHP Unknown error' => 'red',
			'PHP Warning'       => 'orange',
			'PHP Notice'        => 'blue',
			'PHP Deprecated'    => 'purple',
		);

		$html   = esc_html( $log );
		$counts = array();

		foreach ( $levels as $label => $color ) {
			$counts[ $label ] = substr_count( $log, $label );
			$html             = str_replace( esc_html( $label ), '<span style="color: ' . esc_attr( $color ) . '">' . esc_html( $label ) . '</span>', $html );
		}

		echo '<p>';
		foreach ( $counts as $label => $count ) {
			echo '<span style="margin-right: 12px"><strong>' . esc_html( str_replace( 'PHP ', '', $label ) ) . '</strong>: ' . absint( $count ) . '</span>';
		}
		echo '</p>';

		// $html is escaped above; only the level spans are added.
		echo '<pre style="height: 500px; overflow: auto; border: 1px solid #ccc; padding: 10px; background: #fff; white-space: pre-wrap">' . $html . '</pre>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Render the error log path field.
	 */
	public function render_dxsf_error_log_file_field() {
		$error_log_file = get_option( 'dxsf_error_log_file' );
		?>
		<?php if ( ! empty( $error_log_file ) && ! Handlers\Error_Log::exists( $error_log_file ) ) : ?>
			<div class="notice notice-error inline"><p><?php esc_html_e( 'The error log file does not exist or is not readable.', 'dxsf-proxy' ); ?></p></div>
		<?php endif; ?>
		<input type="text" name="dxsf_error_log_file" value="<?php echo esc_attr( $error_log_file ); ?>" class="large-text"/>
		<p class="description"><?php esc_html_e( 'An absolute path, or a full URL including https://.', 'dxsf-proxy' ); ?></p>
		<p class="description">
			<?php
			/* translators: %s: path to debug.log. */
			echo esc_html( sprintf( __( 'Your local debug.log is here: %s', 'dxsf-proxy' ), WP_CONTENT_DIR . '/debug.log' ) );
			?>
		</p>
		<?php
	}

	/**
	 * Render the date format field.
	 */
	public function render_dxsf_date_format_field() {
		$date_format = get_option( 'dxsf_date_format', '[d-M-Y' );
		?>
		<input type="text" name="dxsf_date_format" value="<?php echo esc_attr( $date_format ); ?>" class="regular-text"/>
		<p class="description"><?php esc_html_e( 'The date format the log lines start with. [d-M-Y is the default.', 'dxsf-proxy' ); ?></p>
		<?php
	}

	/**
	 * Render the email extensions field.
	 */
	public function render_dxsf_email_extensions_field() {
		$email_extensions = get_option( 'dxsf_email_extensions' );
		?>
		<input type="text" name="dxsf_email_extensions" value="<?php echo esc_attr( $email_extensions ); ?>" class="regular-text"/>
		<p class="description"><?php esc_html_e( 'Email domains of the users to report, comma separated, e.g. devrix.com.', 'dxsf-proxy' ); ?></p>
		<?php
	}

	/**
	 * Render the legacy server address field.
	 */
	public function render_dxsf_remote_address_field() {
		$remote_address = get_option( 'dxsf_remote_address' );
		?>
		<input type="text" name="dxsf_remote_address" value="<?php echo esc_attr( $remote_address ); ?>" class="regular-text"/>
		<p class="description"><?php esc_html_e( 'IP address of the 2.x Stability Framework server. Clear it once the site is connected to the new core.', 'dxsf-proxy' ); ?></p>
		<?php if ( defined( 'DXSF_REMOTE' ) ) : ?>
			<p class="description"><?php esc_html_e( 'DXSF_REMOTE in wp-config.php overrides this field.', 'dxsf-proxy' ); ?></p>
		<?php endif; ?>
		<?php
	}
}
