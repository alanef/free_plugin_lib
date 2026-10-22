<?php

namespace Fullworks_Free_Plugin_Lib;

use Fullworks_Free_Plugin_Lib\Classes\Email;

class Main {
	/**
	 * Shortname of the most recently constructed instance, used only as the default for
	 * plugin_uninstall(). Every plugin bundling this library shares one copy of the class,
	 * so per-plugin state must live on the instance, never here.
	 *
	 * @var mixed
	 */
	private static $last_plugin_shortname;
	/**
	 * @var mixed
	 */
	private $plugin_shortname;
	/**
	 * @var string
	 */
	private static $version = '1.3.1';
	/**
	 * The host plugin's settings page as passed: a menu slug ('stop-user-enumeration'), a hook
	 * suffix ('settings_page_contact-form-settings') or '' for a plugin with no settings page.
	 *
	 * @var mixed
	 */
	private $page;
	/**
	 * The menu slug part of $page, compared with $_GET['page'].
	 *
	 * @var string
	 */
	private $page_slug;
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
	private $plugin_name;
	private $settings_page;

	public function __construct($plugin_file, $settings_page, $plugin_shortname, $page, $plugin_name) {
		$this->plugin_shortname = $plugin_shortname;
		self::$last_plugin_shortname = $plugin_shortname;
		$this->page = $page;
		$this->page_slug = (string) preg_replace('/^.*_page_/', '', (string) $page);
		$this->plugin_file = $plugin_file;
		$this->settings_page = $settings_page;
		$this->plugin_name = $plugin_name;

		add_filter('plugin_action_links_' . $this->plugin_file, array($this, 'plugin_action_links'));
		add_action('init', array($this, 'load_text_domain'));
		add_action('admin_init', array($this, 'handle_skip_optin'));
        add_action('admin_init', array($this, 'handle_optin_page'));
		add_action('admin_menu', array($this, 'add_settings_page'));

		// Move AJAX handler registration outside current_screen
		add_action('wp_ajax_ffpl_handle_optin', array($this, 'handle_optin_ajax'));
		add_action('wp_ajax_ffpl_dismiss_notice', array($this, 'handle_dismiss_notice'));

		// Move enqueue assets to admin_enqueue_scripts
		add_action('admin_enqueue_scripts', array($this, 'conditional_enqueue_assets'));

		// Admin notice for setup prompt
		add_action('admin_notices', array($this, 'maybe_show_setup_notice'));
	}

	/**
	 * Remove the library's data. Host plugins call this from their own uninstall routine.
	 *
	 * The library does not register an uninstall hook itself: WordPress stores one uninstall
	 * callback per plugin, so registering one here would replace the host plugin's.
	 *
	 * @param string|null $plugin_shortname Defaults to the shortname passed to the constructor.
	 *                                      Pass it explicitly when Main may not have been
	 *                                      constructed yet, e.g. when it is created on a hook.
	 */
	public static function plugin_uninstall($plugin_shortname = null) {
		$plugin_shortname = null === $plugin_shortname ? self::$last_plugin_shortname : $plugin_shortname;
		if (empty($plugin_shortname)) {
			return;
		}
		delete_site_option($plugin_shortname . '_form_rendered');
	}

	public function handle_skip_optin() {
		if (!isset($_GET['ffpl_skip'])) {
			return;
		}

		// Verify nonce - the nonce IS the ffpl_skip value
		$nonce = sanitize_text_field(wp_unslash($_GET['ffpl_skip']));
		if (!wp_verify_nonce($nonce, 'ffpl_skip_' . $this->plugin_shortname)) {
			return;
		}

		// Set status to optout - user explicitly skipped
		update_site_option($this->plugin_shortname . '_form_rendered', 'optout');

		// Redirect to clean URL (remove the skip param)
		wp_safe_redirect(remove_query_arg('ffpl_skip'));
		exit;
	}

	public function handle_optin_page() {
		// admin_init also runs for admin-ajax.php, and a redirect there breaks every AJAX call on the site
		if (wp_doing_ajax() || '' === $this->page_slug || !current_user_can('manage_options')) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is page routing, not form processing
		$current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
		$option = get_site_option($this->plugin_shortname . '_form_rendered');
		// 'rendering' means the opt-in screen was shown but left without a choice, so ask again
		if (in_array($option, array('pending', 'rendering'), true) && $current_page === $this->page_slug) {
			update_site_option($this->plugin_shortname . '_form_rendered', 'rendering');
			wp_safe_redirect(admin_url('options-general.php?page=ffpl-opt-in-' . $this->plugin_shortname));
			exit;
		}
	}

	public function plugin_action_links($links) {
		$settings_link = '<a href="' . esc_url($this->settings_page) . '">' . esc_html($this->translate('Settings')) . '</a>';
		array_unshift(
			$links,
			$settings_link
		);
		if ('optout' === get_site_option($this->plugin_shortname . '_form_rendered', 'optout')) {
			$settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=ffpl-opt-in-'.$this->plugin_shortname )) . '" style="font-weight:900; font-size: 110%; color: #b32d2e;">' . esc_html($this->translate('Opt In')) . '</a>';
			array_unshift(
				$links,
				$settings_link
			);
		}

		return $links;
	}

	public function add_settings_page() {
		// First-run detection - if option doesn't exist, set to pending
		$option = get_site_option($this->plugin_shortname . '_form_rendered');
		if (false === $option) {
			update_site_option($this->plugin_shortname . '_form_rendered', 'pending');
			$option = 'pending';
		}

		// Register the opt-in page if not yet completed
		if (in_array($option, array('pending', 'rendering', 'optout'))) {
			add_options_page(
				esc_html($this->translate('Opt In ')) . esc_html( $this->plugin_name), // Page title
				esc_html($this->translate('Opt In ') . esc_html( $this->plugin_name) ), // Menu title
				'manage_options', // Capability
				'ffpl-opt-in-'.$this->plugin_shortname, // Menu slug
				array($this, 'render_opt_in_page') // Callback function
			);
		}
	}

	public function load_text_domain() {
		$rel_path = dirname(plugin_basename(__DIR__));
		load_plugin_textdomain('free-plugin-lib', false,  $rel_path = dirname(plugin_basename(__DIR__)) . '/src/languages');
	}

	public function render_opt_in_page() {
		$user = wp_get_current_user();
		// Keep status as 'rendering' while viewing - only change on actual user action
		?>
        <div class="fpl-page-wrap" role="main">
            <div class="fpl-wrap" role="form" aria-labelledby="optin-heading">
                <div class="box">
                    <div class="logo-container">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'Assets/images/logo.svg'); ?>"
                             alt="<?php echo esc_attr($this->translate('Logo')); ?>" class="logo">
                    </div>
                    <div class="box-content">
                        <h1 id="optin-heading"><?php echo esc_html($this->translate('Opt In to Stay Updated')); ?></h1>
                        <p>
							<?php
							printf(
								'%s <strong>%s</strong>! %s <strong>%s</strong>, <strong>%s</strong> %s <strong>%s</strong> %s',
								esc_html($this->translate('Stay in the loop with')),
								esc_html($this->translate('essential updates')),
								esc_html($this->translate('Opt in to receive notifications for')),
								esc_html($this->translate('security and feature updates')),
								esc_html($this->translate('expert tips')),
								esc_html($this->translate('to get the most out of your plugin, and')),
								esc_html($this->translate('exclusive offers')),
								esc_html($this->translate('just for our community'))
							);
							?>
                        </p>
                        <p>
							<?php
							printf(
								'%s <strong>%s</strong> %s <strong>%s %s</strong> %s',
								esc_html($this->translate('By opting in, you\'re directly')),
								esc_html($this->translate('supporting us')),
								esc_html($this->translate('in keeping')),
								esc_attr($this->plugin_name),
								esc_html($this->translate('FREE')),
								esc_html($this->translate('for everyone. Thank you for helping us grow and improve together!'))
							);
							?>
                        </p>
                        <label for="fpl_email"><?php echo esc_html($this->translate('My Email')); ?></label>
                        <input id="fpl_email" class="fpl_email" name="email" type="email"
                               value="<?php echo esc_attr($user->user_email); ?>"
                               aria-label="<?php echo esc_attr($this->translate('Enter your email address')); ?>"/>
                        <div class="button-wrap">
                            <div class="button-1">
                                <button class="button button-primary btn-optin" name="action" value="optin"
                                        tabindex="1">
									<?php echo esc_html($this->translate('Allow & Continue')); ?>
                                </button>
                                <p><a href="#" class="details-link" id="detailsLink"><?php echo esc_html($this->translate('Privacy & Details')); ?></a></p>
                            </div>
                            <div class="button-2">
                                <a href="<?php echo esc_url(add_query_arg('ffpl_skip', wp_create_nonce('ffpl_skip_' . $this->plugin_shortname), $this->settings_page)); ?>"
                                   class="button button-secondary btn-skip" name="action" value="skip"
                                   tabindex="2">
									<?php echo esc_html($this->translate('Skip')); ?>
                                </a>
                                <p class="small-text"><?php echo esc_html($this->translate('If you skip this, that\'s okay! The plugin will still work just fine')); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="details-content" id="detailsContent" aria-hidden="true">
                        <h3><?php echo esc_html($this->translate('Communications You\'ll Receive:')); ?></h3>
                        <ul>
                            <li><?php echo esc_html($this->translate('Security updates and critical patches')); ?></li>
                            <li><?php echo esc_html($this->translate('New feature announcements')); ?></li>
                            <li><?php echo esc_html($this->translate('WordPress tips and best practices')); ?></li>
                            <li><?php echo esc_html($this->translate('Occasional special offers')); ?></li>
                        </ul>

                        <h3><?php echo esc_html($this->translate('How We Protect Your Privacy:')); ?></h3>
                        <ul>
                            <li><?php echo esc_html($this->translate('Your email is stored securely and never shared with third parties')); ?></li>
                            <li><?php echo esc_html($this->translate('We send no more than one email per week, except in the case of security issues')); ?></li>
                            <li><?php echo esc_html($this->translate('You can unsubscribe instantly at any time')); ?></li>
                            <li><?php echo esc_html($this->translate('We only collect your email address - nothing else')); ?></li>
                            <li><?php echo esc_html($this->translate('Data is stored in secure servers within the EU')); ?></li>
                        </ul>

                        <p class="privacy-links">
                            <a href="https://fullworksplugins.com/free-plugin-opt-in-privacy-policy/" target="_blank">
								<?php echo esc_html($this->translate('View our full Privacy Policy')); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
		<?php
	}

	public function conditional_enqueue_assets($hook) {
		// Only load on our specific page
		if ($hook !== 'settings_page_ffpl-opt-in-'.$this->plugin_shortname) {
			return;
		}
		$this->enqueue_assets();
	}

	public function enqueue_assets() {
		$base_url = plugin_dir_url(__FILE__) . '../src/Assets/';

		// Enqueue a CSS file from the ../Assets/css directory
		wp_enqueue_style('ffpl-style-css', $base_url . 'css/style.css', array(), self::$version, 'all');

		// Enqueue a JavaScript file from the ../Assets/scripts directory
		wp_enqueue_script('ffpl-main-js', $base_url . 'scripts/main.js', array('jquery'), self::$version, true);

		// Localize script with necessary data
		wp_localize_script('ffpl-main-js', 'ffplData', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('ffpl_optin_nonce'),
			'shortname' => $this->plugin_shortname,
			'settings_page' => $this->settings_page
		));
	}

	public function handle_optin_ajax() {
		// Every plugin bundling the library registers this handler; leave other plugins' requests to them
		if (isset($_POST['shortname']) && !$this->is_own_shortname(sanitize_key(wp_unslash($_POST['shortname'])))) {
			return;
		}

		// Verify request method
		if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
			wp_send_json_error(['message' => $this->translate('Invalid request method')]);
			wp_die();
		}

		// Set proper headers
		nocache_headers();
		header('Content-Type: application/json; charset=utf-8');

		// Verify user capabilities early
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => $this->translate('Unauthorized access')], 403);
			wp_die();
		}

		// Verify nonce with specific error message
		if (!check_ajax_referer('ffpl_optin_nonce', 'nonce', false)) {
			wp_send_json_error(['message' => $this->translate('Security check failed')], 403);
			wp_die();
		}

		// Rate limiting check
		$rate_limit_key = 'ffpl_optin_attempts_' . get_current_user_id();
		$attempts = get_transient($rate_limit_key) ?: 0;
		if ($attempts > 5) {
			wp_send_json_error(['message' => $this->translate('Too many attempts. Please try again later.')], 429);
			wp_die();
		}

		$email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

		$email_handler = new Email($this->plugin_shortname);
		$result = $email_handler->handle_optin_submission($email);

		if ($result) {
			update_site_option($this->plugin_shortname . '_form_rendered', 'optin');
			wp_send_json_success(array(
				'redirect_url' => $this->settings_page
			));
		} else {
			update_site_option($this->plugin_shortname . '_form_rendered', 'optout');
			wp_send_json_success(array(
				'redirect_url' => $this->settings_page
			));
		}
		wp_die();
	}

	public function maybe_show_setup_notice() {
		// Only for users who can manage options
		if (!current_user_can('manage_options')) {
			return;
		}

		$screen = get_current_screen();
		if (!$screen) {
			return;
		}

		$option = get_site_option($this->plugin_shortname . '_form_rendered');

		// Only show notice if no decision has been made yet (pending or rendering)
		// Don't show if optin, optout, or any other status - a decision was made
		if (!in_array($option, array('pending', 'rendering'), true)) {
			return;
		}

		// Don't show if user dismissed the notice
		if (get_user_meta(get_current_user_id(), $this->plugin_shortname . '_notice_dismissed', true)) {
			return;
		}

		// Show on dashboard, plugins page, tools, options-general, or this plugin's settings page
		$page = $screen->base;
		$display_on_pages = array(
			'dashboard',
			'plugins',
			'tools',
			'options-general',
		);
		if ('' !== $this->page_slug) {
			// A hook suffix is already the screen base; a menu slug is assumed to be under Settings
			$display_on_pages[] = $this->page === $this->page_slug ? 'settings_page_' . $this->page : $this->page;
		}

		if (!in_array($page, $display_on_pages, true)) {
			return;
		}

		$opt_in_url = admin_url('options-general.php?page=ffpl-opt-in-' . $this->plugin_shortname);
		?>
		<div class="notice notice-info is-dismissible ffpl-setup-notice" data-shortname="<?php echo esc_attr($this->plugin_shortname); ?>">
			<p>
				<strong><?php echo esc_html($this->plugin_name); ?>:</strong>
				<?php
				printf(
					'%s <a href="%s"><strong>%s</strong></a>',
					esc_html($this->translate('Choose whether to receive security updates, tips and occasional offers.')),
					esc_url($opt_in_url),
					esc_html($this->translate('Opt in or skip'))
				);
				?>
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('.ffpl-setup-notice[data-shortname="<?php echo esc_js($this->plugin_shortname); ?>"]').on('click', '.notice-dismiss', function() {
				$.post(ajaxurl, {
					action: 'ffpl_dismiss_notice',
					shortname: '<?php echo esc_js($this->plugin_shortname); ?>',
					nonce: '<?php echo esc_js(wp_create_nonce('ffpl_dismiss_notice')); ?>'
				});
			});
		});
		</script>
		<?php
	}

	public function handle_dismiss_notice() {
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => $this->translate('Unauthorized access')], 403);
			wp_die();
		}

		if (!check_ajax_referer('ffpl_dismiss_notice', 'nonce', false)) {
			wp_send_json_error(['message' => $this->translate('Security check failed')], 403);
			wp_die();
		}

		// Every plugin bundling the library registers this handler; leave other plugins' requests to them
		$shortname = isset($_POST['shortname']) ? sanitize_key(wp_unslash($_POST['shortname'])) : '';
		if (!$this->is_own_shortname($shortname)) {
			return;
		}

		// The meta key keeps the shortname's original case so existing dismissals stay readable
		update_user_meta(get_current_user_id(), $this->plugin_shortname . '_notice_dismissed', true);
		wp_send_json_success();
		wp_die();
	}

	/**
	 * sanitize_key() lowercases, and host plugins pass mixed-case shortnames such as 'SUE'.
	 *
	 * @param string $shortname A shortname that has been through sanitize_key().
	 */
	private function is_own_shortname($shortname) {
		return '' !== $shortname && $shortname === sanitize_key($this->plugin_shortname);
	}

	private function translate($text) {
		// deliberately done like this to stop polygots auto adding to translation files as
		// wp.org doesn't differentiate text domains
		return translate($text, 'free-plugin-lib');
	}
}