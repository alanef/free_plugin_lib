<?php

namespace Fullworks_Free_Plugin_Lib;

use Fullworks_Free_Plugin_Lib\Classes\Email;

class Main {
    /**
     * @var mixed
     */
    private static $plugin_shortname;
    /**
     * @var string
     */
    private static $version = '1.0.0';
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

    public function __construct($plugin_file, $settings_page, $plugin_shortname, $page) {
        self::$plugin_shortname = $plugin_shortname;
        $x = get_site_option(self::$plugin_shortname . '_form_rendered');
        $this->page = $page;
        $this->plugin_file = $plugin_file;
        $this->settings_page = $settings_page;
        register_activation_hook($this->plugin_file, array($this, 'plugin_activate'));

        register_uninstall_hook($this->plugin_file, array('\Fullworks_Free_Plugin_Lib\Main', 'plugin_uninstall'));
        add_filter('plugin_action_links_' . $this->plugin_file, array($this, 'plugin_action_links'));
        add_action('init', array($this, 'load_text_domain'));
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Move AJAX handler registration outside current_screen
        add_action('wp_ajax_ffpl_handle_optin', array($this, 'handle_optin_ajax'));
        
        // Move enqueue assets to admin_enqueue_scripts
        add_action('admin_enqueue_scripts', array($this, 'conditional_enqueue_assets'));

        add_action('ffpl_ad_display', array(new Classes\Advert(), 'ad_display'));
    }

    public function plugin_activate() {
        if (!get_site_option(self::$plugin_shortname . '_form_rendered')) {
            if (isset($_REQUEST['_wpnonce'])) {
                $bulk_nonce = wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-plugins');
                $single_nonce = wp_verify_nonce($_REQUEST['_wpnonce'], 'activate-plugin_' . $this->plugin_file);
                if (!$bulk_nonce && !$single_nonce) {
                    return;
                }
            } else {
                return;
            }
            if (isset($_GET['activate-multi'])) {
                return;
            }
            if (isset($_REQUEST['action']) &&
                'activate-selected' === sanitize_text_field(wp_unslash($_REQUEST['action'])) &&
                isset($_REQUEST['checked']) &&
                is_array($_REQUEST['checked']) &&
                count($_REQUEST['checked']) > 1
            ) {
                return;
            }
            update_site_option(self::$plugin_shortname . '_form_rendered', 'pending');
        }
    }

    public static function plugin_uninstall() {
        delete_site_option(self::$plugin_shortname . '_form_rendered');
    }

    public function plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url($this->settings_page) . '">' . esc_html($this->translate('Settings')) . '</a>';
        array_unshift(
            $links,
            $settings_link
        );
        if ('optout' === get_site_option(self::$plugin_shortname . '_form_rendered', 'optout')) {
            $settings_link = '<a href="' . esc_url(admin_url('options-general.php?page=ffpl-opt-in')) . '" style="font-weight:900; font-size: 110%; color: #b32d2e;">' . esc_html($this->translate('Opt In')) . '</a>';
            array_unshift(
                $links,
                $settings_link
            );
        }

        return $links;
    }


    public function add_settings_page() {
        $option = get_site_option(self::$plugin_shortname . '_form_rendered');
        if ('pending' === $option) {
            update_site_option(self::$plugin_shortname . '_form_rendered', 'rendering');
            wp_safe_redirect(admin_url('options-general.php?page=ffpl-opt-in'));
            exit;
        }
        if (in_array($option, array('rendering', 'optout'))) {
            add_options_page(
                esc_html($this->translate('Opt In')), // Page title
                esc_html($this->translate('Opt In')), // Menu title
                'manage_options', // Capability
                'ffpl-opt-in', // Menu slug
                array($this, 'render_opt_in_page') // Callback function
            );
        }
    }

    public function load_text_domain() {
        load_plugin_textdomain('free-plugin-lib', false, __DIR__ . '../languages');
    }

    public function render_opt_in_page() {
        $user = wp_get_current_user();
        update_site_option(self::$plugin_shortname . '_form_rendered', 'optout');
        ?>
        <div class="wrap">
            <div class="fpl-wrap">
                <div class="box">
                    <div class="logo-container">
                        <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'Assets/images/logo.svg'); ?>"
                             alt="Logo" class="logo">
                    </div>
                    <div class="box-content">
                        <p>Stay in the loop with <strong>essential updates</strong>! Opt in to receive notifications for
                            <strong>security and feature updates</strong>, <strong>expert tips</strong> to get the most
                            out of your plugin, and <strong>exclusive offers</strong> just for our community.</p>
                        <p>By opting in, you're directly <strong>supporting us</strong> in keeping this plugin
                           <strong>FREE</strong> for everyone. Thank
                           you for helping us grow and improve together!</p>
                        <label class="screen-reader-text" for="fpl_email">My Email</label>
                        <input id="fpl_email" class="fpl_email" name="email" type="email"
                               value="<?php echo esc_attr($user->user_email); ?>"
                               aria-label="Enter your email address"/>
                        <div class="button-wrap">
                            <div class="button-1">
                                <button class="button button-primary btn-optin" name="action" value="optin"
                                        tabindex="1">
                                    Allow & Continue
                                </button>
                                <p><a href="#" class="details-link" id="detailsLink">Privacy & Details</a></p>
                            </div>
                            <div class="button-2">
                                <a href="<?php echo esc_url($this->settings_page); ?>"
                                   class="button button-secondary btn-skip" name="action" value="skip"
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
                        <h3>Communications You'll Receive:</h3>
                        <ul>
                            <li>Security updates and critical patches</li>
                            <li>New feature announcements</li>
                            <li>WordPress tips and best practices</li>
                            <li>Occasional special offers</li>
                        </ul>

                        <h3>How We Protect Your Privacy:</h3>
                        <ul>
                            <li>Your email is stored securely and never shared with third parties</li>
                            <li>We send no more than one email per week, except in the case of security issues</li>
                            <li>You can unsubscribe instantly at any time</li>
                            <li>We only collect your email address - nothing else</li>
                            <li>Data is stored in secure servers within the EU</li>
                        </ul>

                        <p class="privacy-links">
                            <a href="https://fullworksplugins.com/free-plugin-opt-in-privacy-policy/" target="_blank">View our full Privacy
                                Policy</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <?php
    }

    public function conditional_enqueue_assets($hook) {
        // Only load on our specific page
        if ($hook !== 'settings_page_ffpl-opt-in') {
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
            'settings_page' => $this->settings_page
        ));
    }

    public function handle_optin_ajax() {
        // Verify request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => 'Invalid request method']);
            wp_die();
        }

        // Set proper headers
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');

        // Verify user capabilities early
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(['message' => 'Unauthorized access'], 403);
            wp_die();
        }

        // Verify nonce with specific error message
        if (!check_ajax_referer('ffpl_optin_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Security check failed'], 403);
            wp_die();
        }

        // Rate limiting check
        $rate_limit_key = 'ffpl_optin_attempts_' . get_current_user_id();
        $attempts = get_transient($rate_limit_key) ?: 0;
        if ($attempts > 5) {
            wp_send_json_error(['message' => 'Too many attempts. Please try again later.'], 429);
            wp_die();
        }

        $email = sanitize_email(wp_unslash($_POST['email']));
        
        $email_handler = new Email(self::$plugin_shortname);
        $result = $email_handler->handle_optin_submission($email);

        if ($result) {
            update_site_option(self::$plugin_shortname . '_form_rendered', 'optin');
            wp_send_json_success(array(
                'redirect_url' => $this->settings_page
            ));
        } else {
	        update_site_option(self::$plugin_shortname . '_form_rendered', 'optout');
	        wp_send_json_success(array(
		        'redirect_url' => $this->settings_page
	        ));
        }
        wp_die();
    }

    private function translate($text) {
        // deliberately done like this to stop polygots auto adding to translation files as
        // wp.org doesn't differentiate text domains
        return translate($text, 'free-plugin-lib');
    }
}
