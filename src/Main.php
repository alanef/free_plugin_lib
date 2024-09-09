<?php

namespace Fullworks_Free_Plugin_Lib;

class Main {
	/**
	 * @var mixed
	 */
	private $plugin_shortname;
	/**
	 * @var mixed
	 */
	private $page;
	/**
	 * @var \WP_Screen|null
	 */
	private $current_page;

	public function __construct( $plugin_shortname, $page ) {
		$this->plugin_shortname = $plugin_shortname;
		$this->page             = $page;

		add_action( 'init', array( $this, 'load_text_domain' ) );

		$this->current_page = $screen = get_current_screen();

		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

		if ( is_admin() && $this->current_page->id === 'settings_page_ffpl-opt-in' ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		}

		if ( is_admin() && $this->current_page->id === $this->page ) {
			if ( ! get_option( $this->plugin_shortname . '_form_rendered' ) ) {
				wp_safe_redirect( admin_url( 'admin.php?options-general.php?page=ffpl-opt-in' ) );
			}

		}
	}

	public function add_settings_page() {
		if ( ! get_option( $this->plugin_shortname . '_form_rendered' ) ) {
			add_options_page(
				$this->esc_html__( 'Opt In', 'free-plugin-lib' ), // Page title
				$this->esc_html__( 'Opt In', 'free-plugin-lib' ), // Menu title
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
		echo "Hello, World!";
	}


	public function enqueue_assets() {

		$base_url = __DIR__ . '../Assets/';

		// Enqueue a CSS file from the ../Assets/css directory
		wp_enqueue_style( 'ffpl-style-css', $base_url . 'css/style.css' );

		// Enqueue a JavaScript file from the ../Assets/scripts directory
		wp_enqueue_script( 'ffpl-main-js', $base_url . 'scripts/main.js', [], false, true );
	}


	private function esc_html__( $text, $domain ) {
		esc_html( translate( $text, $domain ) );
	}
}
