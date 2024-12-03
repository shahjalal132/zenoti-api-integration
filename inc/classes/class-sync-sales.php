<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Sync_Sales {

    use Singleton;
    use Program_Logs;

    private $api_base_url = 'https://api.zenoti.com/v1';
    private $api_key;
    private $center_id;

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
        // get customer email
        $customer_email = $order->get_billing_email();

        // initialize guest id
        $guest_id = '';

        // search guest
        $guest = $this->search_a_guest( $customer_email );
        // decode json
        $guest = json_decode( $guest, true );

        // check if guest exists than get guest id
        if ( $guest && $guest['page_Info']['total'] > 0 ) {
            $this->put_program_logs( 'guest found' );
            $guest_id = $guest['guests'][0]['id'];
        } else {
            $this->put_program_logs( 'guest not found' );
        }

        $this->put_program_logs( 'Guest ID: ' . $guest_id );

    }

    public function search_a_guest( $email ) {

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

}