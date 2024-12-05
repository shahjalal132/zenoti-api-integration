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

        register_rest_route( 'api/v1', '/sync-inventory', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'sync_inventory' ],
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

    public function sync_inventory() {
        try {
            // Get inventory items from the database
            $inventory_items = $this->get_inventory_items_from_db();

            if ( empty( $inventory_items ) ) {
                return [
                    'success' => false,
                    'message' => __( 'No unsynced inventory items found in the database.', 'zenoti' ),
                ];
            }

            $updated_products   = [];
            $not_found_products = [];

            foreach ( $inventory_items as $inventory_item ) {

                $product_code = $inventory_item->product_code;
                $quantity     = $inventory_item->total_quantity;

                // get product id by product code as product sku. (backward compatibility)
                // $product_id = $this->get_product_sku_by_product_code( $product_code );
                // $this->put_program_logs( 'product_id: ' . $product_id );

                // Sync inventory to WooCommerce
                $sync_result = $this->sync_inventory_to_woocommerce( $product_code, $quantity );

                if ( $sync_result['success'] ) {
                    $updated_products[] = $product_code;
                    $this->mark_inventory_as_synced( $product_code ); // Mark as synced
                } else {
                    $not_found_products[] = $product_code;
                }
            }

            // Prepare the summary message
            $message = __( 'Inventory sync completed.', 'zenoti' );
            if ( !empty( $updated_products ) ) {
                $message .= ' ' . __( 'Updated products: ', 'zenoti' ) . implode( ', ', $updated_products ) . '.';
            }
            if ( !empty( $not_found_products ) ) {
                $message .= ' ' . __( 'Products not found: ', 'zenoti' ) . implode( ', ', $not_found_products ) . '.';
            }

            return [
                'success' => true,
                'message' => $message,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __( 'An error occurred during inventory syncing: ', 'zenoti' ) . $e->getMessage(),
            ];
        }
    }

    public function get_inventory_items_from_db() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_inventory';

        try {
            $limit   = get_option( 'option3', 1 );
            $results = $wpdb->get_results(
                "SELECT product_code, store_quantity, floor_quantity, total_quantity FROM $table_name WHERE is_synced = 0 LIMIT {$limit}",
                OBJECT
            );

            if ( $results === false ) {
                throw new \Exception( __( 'Failed to fetch inventory items from the database.', 'zenoti' ) );
            }

            return $results;
        } catch (\Exception $e) {
            $this->put_program_logs( $e->getMessage() );
            return [];
        }
    }

    public function get_product_sku_by_product_code( $product_code ) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_products';

        try {
            $product = $wpdb->get_row( "SELECT product_id FROM $table_name WHERE product_code = '{$product_code}'" );
            return $product->product_id;
        } catch (\Exception $e) {
            $this->put_program_logs( $e->getMessage() );
            return '';
        }
    }

    public function sync_inventory_to_woocommerce( $product_sku, $quantity ) {

        // $this->put_program_logs( 'Product Code: ' . $product_sku . ' Quantity: ' . $quantity );

        try {
            // Query for the product using its SKU
            $args = [
                'post_type'      => 'product',
                'meta_query'     => [
                    [
                        'key'     => '_sku',
                        'value'   => $product_sku,
                        'compare' => '=',
                    ],
                ],
                'posts_per_page' => 1, // Optimize query to return only one product
            ];

            $query = new \WP_Query( $args );

            if ( $query->have_posts() ) {
                $query->the_post();

                $product_id = get_the_ID();

                // Ensure manage stock is enabled
                update_post_meta( $product_id, '_manage_stock', 'yes' );

                // Update the stock quantity
                update_post_meta( $product_id, '_stock', $quantity );

                // Update stock status based on quantity
                if ( $quantity <= 0 ) {
                    update_post_meta( $product_id, '_stock_status', 'outofstock' );
                } else {
                    update_post_meta( $product_id, '_stock_status', 'instock' );
                }

                // Clean cache to reflect changes
                wc_delete_product_transients( $product_id );

                wp_reset_postdata();

                return [
                    'success' => true,
                    'message' => __( 'Product stock updated successfully for SKU: ', 'zenoti' ) . $product_sku,
                ];
            } else {
                wp_reset_postdata();
                return [
                    'success' => false,
                    'message' => __( 'Product with SKU ', 'zenoti' ) . $product_sku . __( ' not found in WooCommerce.', 'zenoti' ),
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __( 'Failed to update product stock for SKU: ', 'zenoti' ) . $product_sku . '. ' . $e->getMessage(),
            ];
        }
    }

    public function mark_inventory_as_synced( $product_code ) {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_inventory';

        try {
            $update_result = $wpdb->update(
                $table_name,
                [ 'is_synced' => 1 ],
                [ 'product_code' => $product_code ],
                [ '%d' ],
                [ '%s' ]
            );

            if ( $update_result === false ) {
                throw new \Exception( __( 'Failed to mark inventory as synced for SKU: ', 'zenoti' ) . $product_code );
            }
        } catch (\Exception $e) {
            $this->put_program_logs( $e->getMessage() );
        }
    }

}
