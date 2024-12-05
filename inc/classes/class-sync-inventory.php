<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Sync_Inventory {

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
        $this->center_id    = get_option( 'option2' );
    }

    public function register_rest_route() {

        register_rest_route( 'api/v1', '/get-inventory', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_inventory' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function get_inventory() {

        // get inventory from api based on center id
        $inventory = $this->get_all_inventory_from_api( $this->center_id );

        // insert inventory to database
        if ( !empty( $inventory ) ) {
            $this->insert_inventory_to_db( $inventory );
        }
    }

    public function get_all_inventory_from_api( $center_id ) {

        // get date
        $date = date( 'Y-m-d H:i:s' );
        $date = urlencode( $date );

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/inventory/stock?inventory_date={$date}&center_id={$center_id}",
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

        // decode response
        $inventory       = json_decode( $response, true );
        $inventory_items = $inventory['list'];
        // $this->put_program_logs( 'Total Inventory items: ' . count( $inventory_items ) );

        return $inventory_items;
    }

    public function insert_inventory_to_db( $inventory_items ) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_inventory';

        // insert inventory
        foreach ( $inventory_items as $inventory_item ) {

            echo '<pre>';
            print_r( $inventory_item );
            die();
        }

        $this->put_program_logs(
            sprintf(
                'Inventory items inserted to database successfully. Total: %d',
                count( $inventory_items )
            )
        );
    }

}