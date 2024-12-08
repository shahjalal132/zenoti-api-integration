<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Api_Credentials;
use BOILERPLATE\Inc\Traits\Create_Guest;
use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Search_Guest;
use BOILERPLATE\Inc\Traits\Singleton;

class Sync_Sales {

    use Singleton;
    use Program_Logs;
    use Search_Guest;
    use Create_Guest;
    use Api_Credentials;

    protected $center_ids;
    protected $guest_id;
    private $total;

    public function __construct() {
        $this->setup_hooks();
        // Initialize credentials
        $this->load_api_credentials();
    }

    public function setup_hooks() {

        add_action( 'woocommerce_order_status_completed', [ $this, 'sync_sales' ], 10, 1 );

        // get center ids
        $this->center_ids = $this->get_center_ids_from_db();
        
    }

    /**
     * Sync Sales with zenoti
     * @param int $order_id
     */
    public function sync_sales( $order_id ) {

        // get order
        $order = wc_get_order( $order_id );

        // get customer details
        $customer_email = $order->get_billing_email();
        $first_name     = $order->get_billing_first_name();
        $last_name      = $order->get_billing_last_name();
        $phone          = $order->get_billing_phone();
        $address_1      = $order->get_billing_address_1();
        $address_2      = $order->get_billing_address_2();
        $city           = $order->get_billing_city();
        $state          = $order->get_billing_state();
        $postcode       = $order->get_billing_postcode();
        $country        = $order->get_billing_country();

        // get phone code based on country
        $country_code = $this->get_country_code_based_on_country( $country );

        // initialize guest id
        $guest_id = '';

        if ( !empty( $this->center_ids ) ) {
            foreach ( $this->center_ids as $center_id ) {
                // search guest
                $guest = $this->search_a_guest( $center_id, $customer_email );
                // $this->put_program_logs( 'Guest Response: ' . $guest );

                // decode json
                $guest = json_decode( $guest, true );

                // check if guest exists
                if ( $guest && isset( $guest['page_Info']['total'] ) && $guest['page_Info']['total'] > 0 ) {

                    // prepare message
                    $message = sprintf( "Guest found for email: %s id: %s", $customer_email, $guest['guests'][0]['id'] );
                    $this->put_program_logs( $message );

                    // get guest id
                    $guest_id        = $guest['guests'][0]['id'];
                    $this->center_id = $center_id;
                    break; // Exit the loop if a guest is found
                }
            }
        }

        // If no guest found, create a new one
        if ( empty( $guest_id ) ) {
            $payload = [
                "center_id"     => $this->center_ids[0],
                "personal_info" => [
                    "user_name"     => strtolower( $first_name . '_' . $last_name ),
                    "first_name"    => $first_name,
                    "last_name"     => $last_name,
                    "email"         => $customer_email,
                    "mobile_phone"  => [
                        "country_code" => $country_code,
                        "number"       => preg_replace( '/\D/', '', $phone ) // remove non-numeric characters
                    ],
                    "gender"        => -1,
                    "date_of_birth" => null,
                ],
                "address_info"  => [
                    "address_1"   => $address_1,
                    "address_2"   => $address_2,
                    "city"        => $city,
                    "state_id"    => -1,
                    "state_other" => "",
                    "zip_code"    => $postcode,
                    "country_id"  => $country_code,
                ],
            ];

            $new_guest_response = $this->create_a_guest( $payload );
            // $this->put_program_logs( 'New Guest Response: ' . $new_guest_response );
            $new_guest = json_decode( $new_guest_response, true );

            if ( $new_guest && isset( $new_guest['id'] ) && isset( $new_guest['center_id'] ) ) {

                // prepare message
                $message = sprintf( "Guest created for email: %s id: %s", $customer_email, $new_guest['id'] );
                $this->put_program_logs( $message );

                // get guest id
                $guest_id        = $new_guest['id'];
                $this->center_id = $new_guest['center_id'];
            }
        }

        $this->guest_id = $guest_id;
        // $this->put_program_logs( 'Center ID: ' . $this->center_id );
        // $this->put_program_logs( 'Guest ID: ' . $guest_id );

        // Prepare invoice payload
        $invoice_payload = [
            "center_id"     => $this->center_id,
            "guest_id"      => $guest_id,
            "products"      => [],
            "created_by_id" => $this->employee_id,
        ];

        foreach ( $order->get_items() as $item ) {
            // Get product object
            $product = $item->get_product(); // This returns the WC_Product object

            if ( $product ) {
                // Get product SKU
                $product_sku = $product->get_sku();
                $quantity    = $item->get_quantity();

                // get product id by product code as product sku. (backward compatibility)
                // $product_id = $this->get_product_sku_by_product_code( $product_sku );
                // $this->put_program_logs( 'product_id: ' . $product_id );

                // get total
                $this->total += $product->get_price() * $quantity;

                // Populate invoice payload products
                $invoice_payload['products'][] = [
                    "id"         => $product_sku,
                    "quantity"   => $quantity,
                    "sale_by_id" => $this->employee_id,
                ];
            } else {
                // Handle cases where the product object might not be available
                $this->put_program_logs( 'Product not found for order item ID: ' . $item->get_id() );
            }
        }

        // $this->put_program_logs( 'Total: ' . $this->total );

        // Create invoice
        // $invoice_response = $this->create_a_invoice( $invoice_payload );
        // $this->put_program_logs( "Invoice response: " . $invoice_response );

        // get all product of a center
        // $products_json = $this->get_all_products( $this->center_id );
        // decode json
        // $products = json_decode( $products_json, true );

        // $this->put_program_logs( 'Products: ' . print_r( $products, true ) );

        // prepare message
        // $message = sprintf( "Total %s products found for center %s", count( $products ), $this->center_id );
        // $this->put_program_logs( $message );
    }

    public function get_country_code_based_on_country( string $country ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_countries';
        $country_id = $wpdb->get_var( "SELECT country_id FROM $table_name WHERE country_code = '{$country}'" );
        return $country_id;
    }

    public function get_center_ids_from_db() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_centers';
        $sql        = $wpdb->prepare( "SELECT center_id FROM $table_name" );
        $result     = $wpdb->get_results( $sql );

        $center_ids = [];
        $center_ids = array_map( function ($row) {
            return $center_ids[] = $row->center_id;
        }, $result );

        return $center_ids;
    }

    public function get_all_products( string $center_id ) {

        $products = [];
        $page     = 1;
        $per_page = 100;

        do {
            // Prepare the URL with pagination parameters
            $url = "{$this->api_base_url}/centers/{$center_id}/products?page={$page}&size={$per_page}";

            $curl = curl_init();
            curl_setopt_array( $curl, array(
                CURLOPT_URL            => $url,
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

            // Decode the response
            $response_array = json_decode( $response, true );

            // Check if the response contains products
            if ( isset( $response_array['products'] ) ) {
                $products = array_merge( $products, $response_array['products'] );
            }

            // Check if more pages are available
            $total_products = $response_array['page_info']['total'] ?? 0;
            $page++;

        } while ( count( $products ) < $total_products );

        return json_encode( $products );
    }

    public function create_a_invoice( $payload ) {

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/invoices/products",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => json_encode( $payload ),
            CURLOPT_HTTPHEADER     => array(
                'Authorization: apikey ' . $this->api_key,
                'accept: application/json',
                'content-type: application/json',
                'application_name: app',
                'application_version: 1.0.0',
            ),
        ) );

        $response = curl_exec( $curl );

        curl_close( $curl );
        return $response;
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

}