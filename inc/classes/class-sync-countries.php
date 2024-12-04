<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;
use \WP_Error;

class Sync_Countries {

    use Singleton;
    use Program_Logs;

    protected $api_base_url;
    protected $api_key;
    protected $center_id;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        // Register REST API action
        add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );

        // get api credentials
        $this->api_base_url = get_option( 'api_url', 'https://api.zenoti.com/v1' );
        $this->api_key      = get_option( 'api_key' );
    }

    public function register_rest_route() {

        register_rest_route( 'api/v1', '/sync-countries', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'sync_countries' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'api/v1', '/sync-centers', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'sync_centers' ],
            'permission_callback' => '__return_true',
        ] );

        register_rest_route( 'api/v1', '/sync-products', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'sync_products' ],
            'permission_callback' => '__return_true',
        ] );

    }

    public function sync_countries( $request ) {

        // Fetch countries from API
        $countries_json = $this->get_countries_from_api();
        // Decode JSON
        $countries_array = json_decode( $countries_json, true );

        // Extract countries
        $countries = $countries_array['countries'];

        // Insert countries into the database
        $insert_status = $this->insert_countries_to_database( $countries );

        if ( is_wp_error( $insert_status ) ) {
            return $insert_status;
        }

        return rest_ensure_response( [
            'status'  => 'success',
            'message' => 'Countries synced successfully.',
            'count'   => count( $countries ),
        ] );
    }

    public function get_countries_from_api() {
        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/countries?add_defaults_for_dropdown=false",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'Authorization: apikey ' . $this->api_key,
                'accept: application/json',
            ),
        ) );

        $response = curl_exec( $curl );
        $error    = curl_error( $curl );

        curl_close( $curl );
        return $response;
    }

    public function insert_countries_to_database( array $countries ) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_countries';

        // Truncate the table
        $wpdb->query( "TRUNCATE TABLE $table_name" );

        // Insert countries
        foreach ( $countries as $country ) {
            $insert_result = $wpdb->insert( $table_name, [
                'country_id'   => $country['id'],
                'phone_code'   => $country['phone_code'],
                'country_code' => $country['country_code'],
                'country_data' => json_encode( $country ),
            ] );

            if ( $insert_result === false ) {
                $this->put_program_logs( "Failed to insert country with ID: {$country['id']}" );
                return new \WP_Error( 'db_error', 'Failed to insert country data.', [ 'status' => 500 ] );
            }
        }

        return true;
    }

    public function sync_centers() {

        // Fetch centers from API
        $centers_json = $this->get_centers_from_api();
        // Decode JSON
        $centers_array = json_decode( $centers_json, true );

        // Extract centers
        $centers = $centers_array['centers'] ?? [];

        if ( !empty( $centers ) ) {
            // Insert centers into the database
            $insert_status = $this->insert_centers_to_database( $centers );
        }

        if ( is_wp_error( $insert_status ) ) {
            return $insert_status;
        }

        return rest_ensure_response( [
            'status'  => 'success',
            'message' => 'Centers synced successfully.',
            'count'   => count( $centers ),
        ] );
    }

    public function get_centers_from_api() {

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/centers",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'Authorization: apikey ' . $this->api_key,
                'accept: application/json',
            ),
        ) );

        $response = curl_exec( $curl );

        curl_close( $curl );
        return $response;
    }

    public function insert_centers_to_database( $centers ) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_centers';

        // Truncate the table
        $wpdb->query( "TRUNCATE TABLE $table_name" );

        // Insert centers
        foreach ( $centers as $center ) {
            $insert_result = $wpdb->insert( $table_name, [
                'center_id'   => $center['id'],
                'center_code' => $center['code'],
                'center_name' => $center['name'],
                'center_data' => json_encode( $center ),
            ] );

            if ( $insert_result === false ) {
                $this->put_program_logs( "Failed to insert center with ID: {$center['id']}" );
                return new \WP_Error( 'db_error', 'Failed to insert center data.', [ 'status' => 500 ] );
            }
        }

        return true;
    }

    public function sync_products() {
        return "sync_products";
    }
}
