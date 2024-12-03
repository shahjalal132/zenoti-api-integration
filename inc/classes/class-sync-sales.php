<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Sync_Sales {

    use Singleton;
    use Program_Logs;

    protected $api_base_url = 'https://api.zenoti.com/v1';
    protected $api_key;
    protected $center_id;
    protected $guest_id;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'woocommerce_order_status_completed', [ $this, 'sync_sales' ], 10, 1 );

        // get credentials
        $file = PLUGIN_BASE_PATH . '/inc/files/credentials.json';
        if ( file_exists( $file ) ) {
            $credentials     = json_decode( file_get_contents( $file ) );
            $this->api_key   = $credentials->apiKey;
            $this->center_id = $credentials->centerId;
        }
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

        // search guest
        $guest = $this->search_a_guest( $customer_email );
        // decode json
        $guest = json_decode( $guest, true );

        // check if guest exists
        if ( $guest && isset( $guest['page_Info']['total'] ) && $guest['page_Info']['total'] > 0 ) {
            $guest_id = $guest['guests'][0]['id'];
        } else {
            // generate payload for creating a guest
            $payload = [
                "center_id"     => $this->center_id,
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

            // put payload
            // $this->put_program_logs( 'Payload: ' . json_encode( $payload ) );

            // create a guest
            $new_guest_response = $this->create_a_guest( $payload );
            // $this->put_program_logs( 'API Response: ' . $new_guest_response );
            $new_guest = json_decode( $new_guest_response, true );

            if ( $new_guest && isset( $new_guest['id'] ) ) {
                $guest_id = $new_guest['id'];
            }
        }

        $this->guest_id = $guest_id;
        // $this->put_program_logs( 'Guest ID: ' . $guest_id );
    }

    /**
     * Search a guest on zenoti.
     * @param string $email
     * @return string
     */
    public function search_a_guest( string $email ) {

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/guests/search?center_id={$this->center_id}&email={$email}",
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

    public function create_a_guest( $payload ) {

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/guests",
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
            ),
        ) );

        $response = curl_exec( $curl );

        curl_close( $curl );
        return $response;
    }

    public function get_country_code_based_on_country( string $country ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_countries';
        $country_id = $wpdb->get_var( "SELECT country_id FROM $table_name WHERE country_code = '{$country}'" );
        return $country_id;
    }

}