<?php

namespace BOILERPLATE\Inc\Traits;

trait Search_Guest {

    /**
     * Protected class constructor to prevent direct object creation
     *
     * This is meant to be overridden in the classes which implement
     * this trait. This is ideal for doing stuff that you only want to
     * do once, such as hooking into actions and filters, etc.
     */
    protected function __construct() {
    }

    public function search_a_guest( string $center_id, string $email ) {

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/guests/search?center_id={$center_id}&email={$email}",
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