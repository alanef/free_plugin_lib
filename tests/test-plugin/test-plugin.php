<?php
/**
 * Plugin Name: Test Plugin for Free Plugin Lib
 * Description: A test plugin to demonstrate the Free Plugin Lib functionality
 * Version: 1.0.0
 * Author: Fullworks
 * Text Domain: test-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load lib's autoloader from wp-env mapped path
require_once WP_PLUGIN_DIR . '/free-plugin-lib/vendor/autoload.php';

use Fullworks_Free_Plugin_Lib\Main;

class Test_Plugin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'handle_reset_optin' ) );
		add_action( 'admin_init', array( $this, 'handle_save_settings' ) );

		// Add test plugin to the plugin map
		add_filter( 'ffpl_plugin_map', array( $this, 'add_to_plugin_map' ) );

		// Override verify URL if custom URL is set
		add_filter( 'ffpl_verify_url', array( $this, 'override_verify_url' ) );

		// Initialize the Free Plugin Lib
		new Main(
			plugin_basename( __FILE__ ),                          // plugin_file
			admin_url( 'options-general.php?page=test-plugin' ),  // settings_page
			'test_plugin',                                         // plugin_shortname
			'test-plugin',                                         // page
			'Test Plugin'                                          // plugin_name
		);
	}

	public function add_settings_page() {
		add_options_page(
			'Test Plugin Settings',
			'Test Plugin',
			'manage_options',
			'test-plugin',
			array( $this, 'render_settings_page' )
		);
	}

	public function handle_reset_optin() {
		if ( ! isset( $_POST['reset_optin_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['reset_optin_nonce'] ) ), 'reset_optin_action' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Delete the opt-in option to reset to clean state (both for single and multisite)
		delete_option( 'test_plugin_form_rendered' );
		delete_site_option( 'test_plugin_form_rendered' );

		// Also reset the notice dismissal for current user
		delete_user_meta( get_current_user_id(), 'test_plugin_notice_dismissed' );

		// Redirect back with success message
		wp_safe_redirect( admin_url( 'options-general.php?page=test-plugin&reset=1' ) );
		exit;
	}

	public function handle_save_settings() {
		if ( ! isset( $_POST['test_plugin_settings_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( $_POST['test_plugin_settings_nonce'], 'test_plugin_save_settings' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$verify_url = isset( $_POST['test_plugin_verify_url'] ) ? esc_url_raw( $_POST['test_plugin_verify_url'] ) : '';
		update_option( 'test_plugin_verify_url', $verify_url );

		wp_safe_redirect( admin_url( 'options-general.php?page=test-plugin&saved=1' ) );
		exit;
	}

	public function render_settings_page() {
		$current_status = get_site_option( 'test_plugin_form_rendered', 'not set' );
		$verify_url     = get_option( 'test_plugin_verify_url', '' );
		$default_url    = 'https://verify.workflow.fw9.uk';
		?>
		<div class="wrap">
			<h1>Test Plugin Settings</h1>
			<p>This is a blank settings page for testing the Free Plugin Lib.</p>

			<?php if ( isset( $_GET['reset'] ) && $_GET['reset'] == '1' ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Opt-in settings and notice dismissal have been reset. Go to the dashboard to see the setup notice, or refresh this page to trigger the opt-in redirect.</p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['saved'] ) && $_GET['saved'] == '1' ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Settings saved.</p>
				</div>
			<?php endif; ?>

			<h2>Verify URL Override</h2>
			<form method="post">
				<?php wp_nonce_field( 'test_plugin_save_settings', 'test_plugin_settings_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="test_plugin_verify_url">Custom Verify URL</label></th>
						<td>
							<input type="url" id="test_plugin_verify_url" name="test_plugin_verify_url"
								value="<?php echo esc_attr( $verify_url ); ?>" class="regular-text"
								placeholder="<?php echo esc_attr( $default_url ); ?>">
							<p class="description">
								Leave empty to use default: <code><?php echo esc_html( $default_url ); ?></code>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button( 'Save Settings' ); ?>
			</form>

			<hr>

			<h2>Opt-in Debug</h2>
			<?php
			$notice_dismissed = get_user_meta( get_current_user_id(), 'test_plugin_notice_dismissed', true );
			?>
			<table class="form-table">
				<tr>
					<th>Opt-in status</th>
					<td><code><?php echo esc_html( $current_status ); ?></code></td>
				</tr>
				<tr>
					<th>Notice dismissed</th>
					<td><code><?php echo $notice_dismissed ? 'yes' : 'no'; ?></code></td>
				</tr>
				<tr>
					<th>Active verify URL</th>
					<td><code><?php echo esc_html( $verify_url ? $verify_url : $default_url ); ?></code></td>
				</tr>
			</table>

			<form method="post">
				<?php wp_nonce_field( 'reset_optin_action', 'reset_optin_nonce' ); ?>
				<p>
					<button type="submit" class="button button-secondary" onclick="return confirm('Are you sure you want to reset all opt-in settings?');">
						Reset All Opt-in Settings
					</button>
				</p>
				<p class="description">This will delete the opt-in option AND the notice dismissal so you can test the full flow again.</p>
			</form>

		</div>
		<?php
	}

	/**
	 * Uninstall: remove this plugin's settings, then the library's data.
	 * The library no longer registers its own uninstall hook, so host plugins must call it.
	 */
	public static function uninstall() {
		delete_option( 'test_plugin_verify_url' );
		Main::plugin_uninstall( 'test_plugin' );
	}

	/**
	 * Add test plugin to the plugin map so opt-in submission works
	 */
	public function add_to_plugin_map( $plugin_map ) {
		$plugin_map['test_plugin'] = 'test_plugin';
		return $plugin_map;
	}

	/**
	 * Override verify URL if a custom URL is set in settings
	 */
	public function override_verify_url( $url ) {
		$custom_url = get_option( 'test_plugin_verify_url', '' );
		return $custom_url ? $custom_url : $url;
	}
}

register_uninstall_hook( __FILE__, array( 'Test_Plugin', 'uninstall' ) );

// Initialize the plugin
Test_Plugin::get_instance();