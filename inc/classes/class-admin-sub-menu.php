<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Admin_Sub_Menu {

    use Singleton;
    use Program_Logs;

    private $base_url;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'admin_menu', [ $this, 'register_admin_sub_menu' ] );
        add_filter( 'plugin_action_links_' . PLUGIN_BASE_NAME, [ $this, 'add_plugin_action_links' ] );

        $this->base_url = site_url() . '/wp-json/api/v1';

        // save api credentials
        add_action( 'wp_ajax_save_credentials', [ $this, 'save_api_credentials' ] );
        add_action( 'wp_ajax_save_options', [ $this, 'save_options' ] );
        add_action( 'wp_ajax_sync_countries', [ $this, 'sync_countries' ] );
        add_action( 'wp_ajax_sync_centers', [ $this, 'sync_centers' ] );
    }

    public function save_api_credentials() {

        $api_url = sanitize_text_field( $_POST['api_url'] );
        $api_key = sanitize_text_field( $_POST['api_key'] );

        if ( empty( $api_url ) || empty( $api_key ) ) {
            wp_send_json_error( 'An error occurred! Please fill all the fields.' );
        }

        update_option( 'api_url', $api_url );
        update_option( 'api_key', $api_key );

        wp_send_json_success( 'Credentials saved successfully!' );
        die();
    }

    public function save_options() {

        $option1 = sanitize_text_field( $_POST['option1'] );
        $option2 = sanitize_text_field( $_POST['option2'] );

        if ( empty( $option1 ) || empty( $option2 ) ) {
            wp_send_json_error( 'An error occurred! Please fill all the fields.' );
        }

        update_option( 'option1', $option1 );
        update_option( 'option2', $option2 );

        wp_send_json_success( 'Options saved successfully!' );
        die();
    }

    function add_plugin_action_links( $links ) {
        $settings_link = '<a href="admin.php?page=zenoti-settings">' . __( 'Settings', 'zenoti' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public function register_admin_sub_menu() {
        add_submenu_page(
            'options-general.php',
            'Zenoti Settings',
            'Zenoti Settings',
            'manage_options',
            'zenoti-settings',
            [ $this, 'menu_callback_html' ],
        );
    }

    public function menu_callback_html() {
        include_once PLUGIN_BASE_PATH . '/templates/template-admin-sub-menu.php';
    }

    public function sync_countries() {
        $url      = $this->base_url . '/sync-countries';
        $response = wp_remote_get( $url, [
            'timeout' => 300,
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'An error occurred! Please try again.' );
        }

        wp_send_json_success( 'Countries synced successfully!' );
    }

    public function sync_centers() {
        $url      = $this->base_url . '/sync-centers';
        $response = wp_remote_get( $url, [
            'timeout' => 300,
        ] );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( 'An error occurred! Please try again.' );
        }

        wp_send_json_success( 'Countries synced successfully!' );
    }

}