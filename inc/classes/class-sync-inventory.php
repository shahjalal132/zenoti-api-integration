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

        // Get API credentials
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
        // Validate API credentials
        if ( empty( $this->api_key ) || empty( $this->center_id ) ) {
            return new \WP_Error(
                'missing_credentials',
                __( 'API key or Center ID is not set in options.', 'zenoti' ),
                [ 'status' => 400 ]
            );
        }

        try {
            // Get inventory from API
            $inventory = $this->get_all_inventory_from_api( $this->center_id );

            if ( empty( $inventory ) ) {
                return new \WP_Error(
                    'empty_inventory',
                    __( 'No inventory data retrieved from the API.', 'zenoti' ),
                    [ 'status' => 404 ]
                );
            }

            // Insert inventory into the database
            $this->insert_inventory_to_db( $inventory );

            return [
                'success' => true,
                'message' => __( 'Inventory inserted into the database successfully.', 'zenoti' ),
            ];
        } catch (\Exception $e) {
            return new \WP_Error(
                'sync_error',
                __( 'An error occurred while syncing inventory: ', 'zenoti' ) . $e->getMessage(),
                [ 'status' => 500 ]
            );
        }
    }

    public function get_all_inventory_from_api( $center_id ) {

        $date = urlencode( date( 'Y-m-d H:i:s' ) );

        $curl = curl_init();
        curl_setopt_array( $curl, [
            CURLOPT_URL            => "{$this->api_base_url}/inventory/stock?inventory_date={$date}&center_id={$center_id}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Authorization: apikey ' . $this->api_key,
                'accept: application/json',
            ],
        ] );

        $response  = curl_exec( $curl );
        $http_code = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

        curl_close( $curl );

        if ( $http_code !== 200 ) {
            throw new \Exception( __( 'API Request failed with status code: ', 'zenoti' ) . $http_code );
        }

        $inventory = json_decode( $response, true );
        return $inventory['list'] ?? [];
    }

    public function insert_inventory_to_db( $inventory_items ) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_inventory';

        // Truncate table with error handling
        $truncate_result = $wpdb->query( "TRUNCATE TABLE $table_name" );
        if ( $truncate_result === false ) {
            throw new \Exception( __( 'Failed to truncate the inventory table.', 'zenoti' ) );
        }

        // Insert inventory items
        foreach ( $inventory_items as $inventory_item ) {
            $insert_result = $wpdb->insert(
                $table_name,
                [
                    'center_code'    => $inventory_item['center_code'] ?? '',
                    'product_code'   => $inventory_item['product_code'] ?? '',
                    'store_quantity' => $inventory_item['store_quantity'] ?? 0,
                    'floor_quantity' => $inventory_item['floor_quantity'] ?? 0,
                    'total_quantity' => $inventory_item['total_quantity'] ?? 0,
                    'inventory_data' => json_encode( $inventory_item ),
                    'is_synced'      => 0,
                ]
            );

            if ( $insert_result === false ) {
                throw new \Exception( __( 'Failed to insert inventory data for product: ', 'zenoti' ) . ( $inventory_item['product_code'] ?? 'unknown' ) );
            }
        }
    }
}
