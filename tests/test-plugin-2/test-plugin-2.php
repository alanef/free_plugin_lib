<?php
/**
 * Plugin Name: Test Plugin 2 for Free Plugin Lib
 * Description: A second test plugin to test multiple plugins using the Free Plugin Lib
 * Version: 1.0.0
 * Author: Fullworks
 * Text Domain: test-plugin-2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load lib's autoloader from wp-env mapped path
require_once WP_PLUGIN_DIR . '/free-plugin-lib/vendor/autoload.php';

use Fullworks_Free_Plugin_Lib\Main;

class Test_Plugin_2 {

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

		// Add test plugin to the plugin map
		add_filter( 'ffpl_plugin_map', array( $this, 'add_to_plugin_map' ) );

		// Initialize the Free Plugin Lib
		new Main(
			plugin_basename( __FILE__ ),                            // plugin_file
			admin_url( 'options-general.php?page=test-plugin-2' ),  // settings_page
			'test_plugin_2',                                        // plugin_shortname
			'test-plugin-2',                                        // page
			'Test Plugin 2'                                         // plugin_name
		);
	}

	public function add_settings_page() {
		add_options_page(
			'Test Plugin 2 Settings',
			'Test Plugin 2',
			'manage_options',
			'test-plugin-2',
			array( $this, 'render_settings_page' )
		);
	}

	public function handle_reset_optin() {
		if ( ! isset( $_POST['reset_optin_nonce_2'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['reset_optin_nonce_2'] ) ), 'reset_optin_action_2' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Delete the opt-in option to reset to clean state (both for single and multisite)
		delete_option( 'test_plugin_2_form_rendered' );
		delete_site_option( 'test_plugin_2_form_rendered' );

		// Also reset the notice dismissal for current user
		delete_user_meta( get_current_user_id(), 'test_plugin_2_notice_dismissed' );

		wp_safe_redirect( admin_url( 'options-general.php?page=test-plugin-2&reset=1' ) );
		exit;
	}

	public function render_settings_page() {
		$current_status = get_site_option( 'test_plugin_2_form_rendered', 'not set' );
		?>
		<div class="wrap">
			<h1>Test Plugin 2 Settings</h1>
			<p>This is a second test plugin to verify the library behaves correctly when multiple plugins use it.</p>

			<?php if ( isset( $_GET['reset'] ) && $_GET['reset'] == '1' ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Opt-in settings have been reset.</p>
				</div>
			<?php endif; ?>

			<h2>Opt-in Debug</h2>
			<?php
			$notice_dismissed = get_user_meta( get_current_user_id(), 'test_plugin_2_notice_dismissed', true );
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
			</table>

			<form method="post">
				<?php wp_nonce_field( 'reset_optin_action_2', 'reset_optin_nonce_2' ); ?>
				<p>
					<button type="submit" class="button button-secondary" onclick="return confirm('Are you sure you want to reset all opt-in settings?');">
						Reset All Opt-in Settings
					</button>
				</p>
			</form>

		</div>
		<?php
	}

	public function add_to_plugin_map( $plugin_map ) {
		$plugin_map['test_plugin_2'] = 'test_plugin_2';
		return $plugin_map;
	}
}

// Initialize the plugin
Test_Plugin_2::get_instance();