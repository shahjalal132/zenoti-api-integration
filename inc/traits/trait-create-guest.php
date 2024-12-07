<?php

namespace BOILERPLATE\Inc\Traits;

trait Create_Guest {

    /**
     * Protected class constructor to prevent direct object creation
     *
     * This is meant to be overridden in the classes which implement
     * this trait. This is ideal for doing stuff that you only want to
     * do once, such as hooking into actions and filters, etc.
     */
    protected function __construct() {
    }

    public function create_a_guest( array $payload ) {

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

}