<?php
// tests/bootstrap.php

// Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Mock WordPress functions and constants
define('ABSPATH', __DIR__ . '/');
define('WP_DEBUG', true);

// Mock WordPress functions

// Security & Authentication
if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce() {
		return 'test_nonce';
	}
}

if (!function_exists('wp_verify_nonce')) {
	function wp_verify_nonce($nonce, $action = -1) {
		return $nonce === 'valid_nonce' ? true : false;
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return true;
	}
}

// Options & Site
if ( ! function_exists( 'get_site_option' ) ) {
	function get_site_option( $option, $default = false ) {
		return false;
	}
}

if ( ! function_exists( 'update_site_option' ) ) {
	function update_site_option( $option, $value ) {
		return true;
	}
}

if ( ! function_exists( 'delete_site_option' ) ) {
	function delete_site_option( $option ) {
		return true;
	}
}

if ( ! function_exists( 'get_bloginfo' ) ) {
	function get_bloginfo( $show = '' ) {
		return 'test-version';
	}
}

// Hooks
if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $callback ) {
		return call_user_func( $callback );
	}
}

if ( ! function_exists( 'register_uninstall_hook' ) ) {
	function register_uninstall_hook( $file, $callback ) {
		return call_user_func( $callback );
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}
}

// Admin UI
if ( ! function_exists( 'add_options_page' ) ) {
	function add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback ) {
		return true;
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'http://test.com/wp-admin/' . $path;
	}
}

if ( ! function_exists( 'wp_safe_redirect' ) ) {
	function wp_safe_redirect( $location, $status = 302 ) {
		return true;
	}
}

// Sanitization & Escaping
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( $str ) );
	}
}

// HTTP & API
if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args = array() ) {
		return [ 'response' => [ 'code' => 200 ] ];
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return isset( $response['response']['code'] ) ? $response['response']['code'] : 500;
	}
}

if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers() {
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return ( $thing instanceof \WP_Error );
	}
}

// Translation
if ( ! function_exists( 'translate' ) ) {
	function translate( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'load_plugin_textdomain' ) ) {
	function load_plugin_textdomain( $domain, $abs_rel_path = false, $plugin_rel_path = false ) {
		return true;
	}
}

// Assets
if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $in_footer = false ) {
		return true;
	}
}

if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( $handle, $object_name, $l10n ) {
		return true;
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://test.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

// Classes
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $errors = array();

		public function __construct( $code = '', $message = '', $data = '' ) {
			if ( $code ) {
				$this->errors[ $code ] = array( $message );
			}
		}

		public function get_error_message( $code = '' ) {
			if ( empty( $code ) ) {
				$code = array_keys( $this->errors )[0];
			}

			return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
		}
	}
}

if ( ! class_exists( 'WP_Screen' ) ) {
	class WP_Screen {
		public $id;

		public function __construct( $id = '' ) {
			$this->id = $id;
		}
	}
}
$GLOBALS['mock_attempts'] = 0;

if (!function_exists('get_transient')) {
	function get_transient($key) {
		return $GLOBALS['mock_attempts'];
	}
}

if (!function_exists('set_transient')) {
	function set_transient($key, $value) {
		$GLOBALS['mock_attempts'] = $value;
		return true;
	}
}
if (!function_exists('plugin_dir_url')) {
	function plugin_dir_url($file) {
		return 'http://test.com/wp-content/plugins/test/';
	}
}

if (!function_exists('esc_html')) {
	function esc_html($text) {
		return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
	}
}
class Freemius {
	private $premium = false;

	public function can_use_premium_code() {
		return $this->premium;
	}

	public function set_premium($value) {
		$this->premium = $value;
	}
}