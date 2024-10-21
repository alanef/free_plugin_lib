<?php

namespace Fullworks_Free_Plugin_Lib;

class Main {
	/**
	 * @var mixed
	 */
	private static $plugin_shortname;
	/**
	 * @var mixed
	 */
	private $page;
	/**
	 * @var \WP_Screen|null
	 */
	private $current_page;
	/**
	 * @var mixed
	 */
	private $plugin_file;
	/**
	 * @var mixed
	 */
	private $settings_page;

	public function __construct( $plugin_file, $settings_page, $plugin_shortname, $page ) {
		self::$plugin_shortname = $plugin_shortname;
		$x                      = get_site_option( self::$plugin_shortname . '_form_rendered' );
		$this->page             = $page;
		$this->plugin_file      = $plugin_file;
		$this->settings_page    = $settings_page;
		register_activation_hook( $this->plugin_file, array( $this, 'plugin_activate' ) );
		register_uninstall_hook( $this->plugin_file, array( '\Fullworks_Free_Plugin_Lib\Main', 'plugin_uninstall' ) );
		add_filter( 'plugin_action_links_' . $this->plugin_file, array( $this, 'plugin_action_links' ) );
		add_action( 'init', array( $this, 'load_text_domain' ) );
		add_action( 'init', function () {
			$a = 1;
		} );
		add_action( 'admin_init', function () {

		} );
		add_action( 'admin_menu', function () {

		} );

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		add_action( 'current_screen', function () {
			$this->current_page = get_current_screen();
			if ( is_admin() && $this->current_page->id === 'settings_page_ffpl-opt-in' ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			}
		} );
	}

	public function plugin_activate() {
		if ( ! get_site_option( self::$plugin_shortname . '_form_rendered' ) ) {
			if ( isset( $_REQUEST['_wpnonce'] ) ) {
				$bulk_nonce   = wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-plugins' );
				$single_nonce = wp_verify_nonce( $_REQUEST['_wpnonce'], 'activate-plugin_' . $this->plugin_file );
				if ( ! $bulk_nonce && ! $single_nonce ) {
					return;
				}
			} else {
				return;
			}
			if ( isset( $_GET['activate-multi'] ) ) {
				return;
			}
			if ( isset( $_REQUEST['action'] ) &&
			     'activate-selected' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) &&
			     isset( $_REQUEST['checked'] ) &&
			     is_array( $_REQUEST['checked'] ) &&
			     count( $_REQUEST['checked'] ) > 1
			) {
				return;
			}
			update_site_option( self::$plugin_shortname . '_form_rendered', 'pending' );
		}
	}

	public static function plugin_uninstall() {
		delete_site_option( self::$plugin_shortname . '_form_rendered' );
	}

	public function plugin_action_links( $links ) {
		$settings_link = '<a href="' . esc_url( $this->settings_page ) . '">' . esc_html( $this->translate( 'Settings' ) ) . '</a>';
		array_unshift(
			$links,
			$settings_link
		);
		if ( 'optout' === get_site_option( self::$plugin_shortname . '_form_rendered' ) ) {
			$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=ffpl-opt-in' ) ) . '" style="font-weight:900; font-size: 110%; color: #b32d2e;">' . esc_html( $this->translate( 'Opt In' ) ) . '</a>';
			array_unshift(
				$links,
				$settings_link
			);
		}

		return $links;
	}


	public function add_settings_page() {
		$option = get_site_option( self::$plugin_shortname . '_form_rendered' );
		if ( 'pending' === $option ) {
			update_site_option( self::$plugin_shortname . '_form_rendered', 'rendering' );
			wp_safe_redirect( admin_url( 'options-general.php?page=ffpl-opt-in' ) );
			exit;
		}
		if ( in_array( $option, array( 'rendering', 'optout' ) ) ) {
			add_options_page(
				esc_html( $this->translate( 'Opt In' ) ), // Page title
				esc_html( $this->translate( 'Opt In' ) ), // Menu title
				'manage_options', // Capability
				'ffpl-opt-in', // Menu slug
				array( $this, 'render_opt_in_page' ) // Callback function
			);
		}
	}

	public function load_text_domain() {
		load_plugin_textdomain( 'free-plugin-lib', false, __DIR__ . '../languages' );
	}

	public function render_opt_in_page() {
		$user = wp_get_current_user();
		update_site_option( self::$plugin_shortname . '_form_rendered', 'optout' );
		?>
        <div class="wrap">
            <div class="fpl-wrap">
                <div class="box">
                    <div class="logo-container">
                        <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'Assets/images/logo.svg' ); ?>"
                             alt="Logo" class="logo">
                    </div>
                    <div class="box-content">
                        <p>Opt in to get email notifications for security & feature updates, educational content, and
                            occasional offers.</p>
                        <p>By agreeing to opt in you are helping to keep this FREE plugin totally FREE</p>
                        <label class="screen-reader-text" for="fpl_email">My Email</label>
                        <input id="fpl_email" class="fpl_email" name="email" type="email"
                               value="<?php echo esc_attr( $user->user_email ); ?>"
                               aria-label="Enter your email address"/>
                        <div class="button-wrap">
                            <div class="button-1">
                                <button class="button button-primary btn-optin" name="action" value="optin"
                                        tabindex="1">
                                    Allow &amp; Continue
                                </button>
                                <p><a href="#" class="details-link" id="detailsLink">Privacy & Details</a></p>
                            </div>
                            <div class="button-2">
                                <a href="<?php echo esc_url( $this->settings_page); ?> "class="button button-secondary btn-skip" name="action" value="skip"
                                        tabindex="2">
                                    Skip
                                </a>
                                <p class="small-text">If you skip this, that's okay! The plugin will still work just
                                    fine</p>
                            </div>
                        </div>
                        <form>
                    </div>
                    <div class="details-content" id="detailsContent">
                        <p>Here are more details about our service...</p>
                    </div>
                </div>
            </div>
        </div>

		<?php
	}


	public function enqueue_assets() {

		$base_url = plugin_dir_url( __FILE__ ) . '../src/Assets/';

		// Enqueue a CSS file from the ../Assets/css directory
		wp_enqueue_style( 'ffpl-style-css', $base_url . 'css/style.css' );

		// Enqueue a JavaScript file from the ../Assets/scripts directory
		wp_enqueue_script( 'ffpl-main-js', $base_url . 'scripts/main.js', [], false, true );
	}


	private function translate( $text ) {
		// deliberately done like this to stop polygots auto adding to translation files as
		// wp.org doesn't differentiate text domains
		return translate( $text, 'free-plugin-lib' );
	}
}