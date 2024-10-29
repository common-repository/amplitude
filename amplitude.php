<?php
/**
 * Plugin Name: Amplitude
 * Plugin URI: https://amplitude.com/?utm_medium=referral&utm_source=wordpress
 * Description: Track activity across your site and uncover actionable insights with Amplitude.
 * Author: Amplitude
 * Author URI: https://amplitude.com/?utm_medium=referral&utm_source=wordpress
 * Version: 0.2.1
 * Requires at least: 5.2
 * Requires PHP: 5.6
 */

//  last updated 28th Thursday March 2024 17:19:10 UTC
namespace Amplitude\Analytics\WP\PLG;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Amplitude {

    public function __wakeup() {}

    public function __clone() {}

    private function __construct() {}

    const SLUG = 'amplitude';

    private const CDN_SUBDOMAIN_MAP = array(
        'us' => 'cdn',
        'eu' => 'cdn.eu'
    );

    private $option = 'aampli_plg_options';

    private static $instance = null;

    public $defaults = array(
        'api_key' => '',
        'session_replay_enabled' => false,
        'main_panel_enabled' => false,
        'session_replay_sample_rate' => 100,
        'server_zone_eu' => false,
    );

    public static function get_instance() {

        if(!isset(self::$instance)) {
            self::$instance = new self();
            self::$instance->setup_constants();
            self::$instance->admin_hooks();
            self::$instance->frontend_hooks();
        }

        return self::$instance;
    }

    public function setup_constants() {
        define('AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH', dirname( __FILE__ ));
    }

    public function admin_hooks() {

        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        add_action('admin_enqueue_scripts', array($this, 'load_styles'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_head', array($this, 'load_admin_sdk'));
        add_action('admin_notices', array($this, 'load_applicable_banner'));
    }

    public function load_styles() {
        if($this->on_amplitude_settings_page()) {
            wp_enqueue_style('my-plugin-admin-styles', plugins_url('/styles/amplitude-styles.css', __FILE__));
        }
    }

    public function load_applicable_banner() {
        $api_key = $this->get_api_key();
        $settings_page_url = admin_url('options-general.php?page=amplitude');

        if(empty($api_key) && !$this->on_amplitude_settings_page()) {
            include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/api-key-banner.php');
        }
    }

    public function on_amplitude_settings_page() {

        $current_screen = get_current_screen();

        return isset($current_screen->id) ? ($current_screen->id === 'settings_page_' . self::SLUG) : false;
    }

    public function frontend_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'load_frontend_sdk'));
    }

    public function load_admin_sdk() {
        $current_screen = get_current_screen();

        if($current_screen->id === 'settings_page_' . self::SLUG || $current_screen->id === 'toplevel_page_' . 'amplitude-dashboard') {
            $this->load_amplitude_sdk('c47ffe86bdde1a3e01e8cc86c4de1711', true, 100, false, false);
        }
    }

    public function load_frontend_sdk() {
        $api_key = $this->get_api_key();
        $session_replay_enabled = $this->get_session_replay_enabled();
        $session_replay_sample_rate = $this->get_session_replay_sample_rate();
        $is_eu_server_zone = $this->get_server_zone_eu();

        if(!empty($api_key)) {
            $this->load_amplitude_sdk($api_key, $session_replay_enabled, $session_replay_sample_rate, true, $is_eu_server_zone);
        }
    }

    public function load_amplitude_sdk($api_key, $session_replay, $session_replay_sample_rate, $should_change_library, $is_eu_server_zone) {
        $wordpress_data = array(
            'apiKey' => $api_key,
            'sessionReplay' => $session_replay,
            'sampleRate' => $session_replay_sample_rate,
            'shouldChangeLibrary' => $should_change_library,
            'isEuServerZone' => $is_eu_server_zone,
        );

        $aampli_plg_scr_enhance = 'aampli_plg_scr_enhance';
        $aampli_plg_scr_session_replay = 'aampli_plg_scr_session_replay';
        $aampli_plg_scr_setup = 'aampli_plg_scr_setup';
        $aampli_plg_scr_configure = 'aampli_plg_scr_configure';
        $aampli_plg_scr_payload = 'aampliPlgScrPayload';
        $aampli_plg_scr_web_experiment = 'aampli_plg_scr_web_experiment';

        $defer_script_list = array(
            $aampli_plg_scr_enhance,
            $aampli_plg_scr_session_replay,
            $aampli_plg_scr_setup,
        );

        $configuration_dependency_list = array(
            $aampli_plg_scr_setup,
            $aampli_plg_scr_enhance,
        );

        $cdn_subdomain = self::CDN_SUBDOMAIN_MAP[$is_eu_server_zone ? 'eu' : 'us'];
        $web_experiment_script = "https://{$cdn_subdomain}.amplitude.com/script/{$api_key}.experiment.js";
        wp_enqueue_script($aampli_plg_scr_web_experiment, $web_experiment_script);

        wp_enqueue_script($aampli_plg_scr_setup, 'https://cdn.amplitude.com/libs/analytics-browser-2.7.4-min.js.gz');

        wp_enqueue_script($aampli_plg_scr_enhance, 'https://cdn.amplitude.com/libs/plugin-autocapture-browser-0.9.0-min.js.gz', array($aampli_plg_scr_setup));

        if($session_replay) {
            wp_enqueue_script($aampli_plg_scr_session_replay, 'https://cdn.amplitude.com/libs/plugin-session-replay-browser-1.6.8-min.js.gz', array($aampli_plg_scr_setup));
            $configuration_dependency_list[] = $aampli_plg_scr_session_replay;
        }

        wp_enqueue_script($aampli_plg_scr_configure, plugin_dir_url( __FILE__ ) . '/scripts/amplitude-configure.js', $configuration_dependency_list);
        wp_localize_script($aampli_plg_scr_configure, $aampli_plg_scr_payload, $wordpress_data);
    }

    public function admin_menu() {
        add_options_page(
            'Amplitude Settings',
            'Amplitude',
            'manage_options',
            self::SLUG,
            array($this, 'admin_page')
        );

        if($this->get_main_panel_enabled()) {
            add_menu_page(
                'Amplitude Dashboard',
                'Amplitude',
                'read',
                'amplitude-dashboard',
                array($this, 'load_general_page'),
                'dashicons-external',
                56
            );
        }
    }

    function load_general_page () {
        $settings_page_url = admin_url('options-general.php?page=amplitude');
        include_once(AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/amplitude-general.php');
    }

    public function admin_page() {
        if(!current_user_can('manage_options')) {
            wp_die('Sorry, you don\'t have the necessary permissions to access this page');
        }

        include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/settings.php');
    }

    public static function handle_activation () {
        if (version_compare(PHP_VERSION, '5.6', '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            exit('This plugin requires PHP version 5.6 or higher. Your current PHP version is ' . PHP_VERSION);
        }

        self::get_instance()->setup_settings();
    }

    public static function setup_settings() {

        $settings = get_option(Amplitude::get_instance()->get_option_name());

        if(!empty($settings)) {
            return;
        }

        update_option(Amplitude::get_instance()->get_option_name(), Amplitude::get_instance()->defaults);
    }

    public function add_action_links ($links) {

        $settings_url = $this->get_settings_url();

        $setings_anchor_tag = sprintf( '<a href="' . $settings_url . '">%s</a>', "Settings" );

        array_unshift($links, $setings_anchor_tag);

        return $links;
    }

    private function get_settings_url () {

        $path = 'options-general.php?page=amplitude';

        $settings_url = is_network_admin() ? network_admin_url( $path ) : admin_url( $path );

        return $settings_url;
    }

    public function register_settings() {
        $settings = $this->_get_default_settings();

        register_setting(self::SLUG, $this->get_option_name(), array($this, 'sanitize_settings'));

        foreach($settings as $section_name => $section) {
            add_settings_section(
                $section_name,
				$section['title'],
				$section['callback'],
				self::SLUG
            );

            foreach ( $section['fields'] as $field ) {

                add_settings_field(
                   $field['name'],
                   $field['title'],
                   $field['callback'],
                   self::SLUG,
                   $section_name
               );

            }
        }
    }

    public function _get_default_settings() {
        return apply_filters('amplitude_default_settings', array(
            'general' => array(
                'title' => 'General',
                'callback' => array($this, 'general_section_callback'),
                'fields' => array(
                    array(
                        'name' => 'api_key',
                        'title' => 'Amplitude API Key',
                        'callback' => array($this, 'api_key_callback'),
                    )
                )
            ),
            'session_replay' => array(
                'title' => 'Session Replay Settings',
                'callback' => array($this, 'session_replay_section_callback'),
                'fields' => array(
                    array(
                        'name' => 'session_replay_enabled',
                        'title' => 'Enable Session Replay',
                        'callback' => array($this, 'session_replay_callback'),
                    ),
                    array(
                        'name' => 'session_replay_sample_rate',
                        'title' => 'Session Replay Sample Rate',
                        'callback' => array($this, 'session_replay_sample_rate_callback'),
                    ),
                )
                ),
            'advanced' => array(
                'title' => 'Other Settings',
                'callback' => array($this, 'do_nothing'),
                'fields' => array(
                    array(
                        'name' => 'server_zone_eu',
                        'title' => 'Set Server Zone to EU',
                        'callback' => array($this, 'server_zone_callback'),
                    ),
                )
            )
        )
        );
    }

    public function get_option_name() {
		return $this->option;
	}

    public static function general_section_callback() {
        include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/settings-description.php');
    }

    public static function session_replay_section_callback() {
        include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/session-replay-description.php');
    }

    public function session_replay_callback () {
        $settings = $this->get_settings();

        $name = $this->get_option_name() . '[session_replay_enabled]';

        include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/session-replay-toggle.php');
    }

    public function session_replay_sample_rate_callback () {
        $settings = $this->get_settings();

        $name = $this->get_option_name() . '[session_replay_sample_rate]';

        $session_replay_enabled_on_load = $this->get_session_replay_enabled();

        $interaction_string = $session_replay_enabled_on_load === "1" ? "" : "disabled";

        include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/session-replay-sample-rate-slider.php');
    }

    public function main_panel_callback () {
        $settings = $this->get_settings();

        $name = $this->get_option_name() . '[main_panel_enabled]';

        include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/main-panel-toggle.php');
    }

    public function server_zone_callback () {
        $settings = $this->get_settings();

        $name = $this->get_option_name() . '[server_zone_eu]';

        include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/server-zone-toggle.php');
    }


    public function api_key_callback() {
        $settings = $this->get_settings();
        $name = $this->get_option_name() . '[api_key]';

        include_once( AMPLITUDE_ANALYTICS_WP_PLG_FILE_PATH . '/templates/api-key-input.php');
    }

    public static function do_nothing() {

    }

    public static function sanitize_settings ($input) {

        $checkbox_name_list = array('session_replay_enabled', 'main_panel_enabled', 'server_zone_eu');

        foreach($checkbox_name_list as $checkbox_name) {
            $input[$checkbox_name] = isset( $input[ $checkbox_name ] ) ? '1' : '0';
        }

        $text_field_name_list = array('api_key');

        foreach($text_field_name_list as $text_field_name) {
            $input[$text_field_name] = isset($input[$text_field_name]) ? sanitize_text_field( $input[ $text_field_name ] ) : '';
        }

        $sample_rate_key = 'session_replay_sample_rate';

        $sample_rate_options = array(
            "options" => array(
                "min_range" => 0,
                "max_range" => 100
            )
        );

        if(!isset($input[$sample_rate_key]) || filter_var($input[$sample_rate_key], FILTER_VALIDATE_INT, $sample_rate_options) === false) {
            $input[$sample_rate_key] = 0;
        }

        return apply_filters('aampli_plg_settings_sanitization', $input);
    }

    public function get_settings() {
		return apply_filters( 'aampli_plg_get_settings', get_option( $this->option ), $this );
	}

    public function get_api_key() {
        $settings = $this->get_settings();
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';

        return $api_key;
    }

    public function get_session_replay_enabled() {
        $settings = $this->get_settings();
        $session_replay_enabled = isset($settings['session_replay_enabled']) ? $settings['session_replay_enabled'] : false;

        return $session_replay_enabled;
    }

    public function get_server_zone_eu() {
        $settings = $this->get_settings();
        $server_zone_eu = isset($settings['server_zone_eu']) ? $settings['server_zone_eu'] : false;

        return $server_zone_eu;
    }

    public function get_main_panel_enabled() {

        // currently disabling the main panel
        return false;

        $settings = $this->get_settings();
        $main_panel_enabled = isset($settings['main_panel_enabled']) ? $settings['main_panel_enabled'] : false;

        return $main_panel_enabled;
    }

    public function get_session_replay_sample_rate() {
        $settings = $this->get_settings();
        $session_replay_sample_rate = isset($settings['session_replay_sample_rate']) ? $settings['session_replay_sample_rate'] : 5;

        return $session_replay_sample_rate;
    }
}

register_activation_hook( __FILE__, array( 'Amplitude\Analytics\WP\PLG\Amplitude', 'handle_activation' ) );
add_action('plugins_loaded','Amplitude\Analytics\WP\PLG\Amplitude::get_instance');
