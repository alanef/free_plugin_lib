<?php

namespace Fullworks_Free_Plugin_Lib\Classes;

class Email {
    private static $plugin_shortname;

    public function __construct($plugin_shortname) {
        self::$plugin_shortname = $plugin_shortname;
    }

    public function handle_optin_submission($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        $response = wp_remote_post('https://octopus.fullworksplugins.com/wp-json/fullworks-freemius-octopusmail/v2/action?list=4c6924da-03e8-11ef-b408-2f0724a38cbd&tag_free=' . self::$plugin_shortname, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'type' => 'install.activated',
                'objects' => array(
                    'user' => array(
                        'is_marketing_allowed' => true,
                        'email' => $email,
                        'first' => '',
                        'last' => ''
                    )
                )
            )),
            'timeout' => 25
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return false;
        }

        return true;
    }
}
