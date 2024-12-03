<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;
use \WP_Error;

class Sync_Countries {

    use Singleton;
    use Program_Logs;

    protected $api_base_url = 'https://api.zenoti.com/v1';
    protected $api_key;
    protected $center_id;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        // Register REST API action
        add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );

        // Load credentials
        $file = PLUGIN_BASE_PATH . '/inc/files/credentials.json';
        if ( file_exists( $file ) ) {
            $credentials = json_decode( file_get_contents( $file ) );
            if ( isset( $credentials->apiKey, $credentials->centerId ) ) {
                $this->api_key   = $credentials->apiKey;
                $this->center_id = $credentials->centerId;
            } else {
                $this->put_program_logs( 'Invalid credentials file structure.' );
            }
        } else {
            $this->put_program_logs( 'Credentials file not found.' );
        }
    }

    public function register_rest_route() {
        register_rest_route( 'api/v1', '/sync-countries', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'sync_countries' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function sync_countries( $request ) {

        // Fetch countries from API
        $countries_json = $this->get_countries();
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

    public function get_countries() {
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
}