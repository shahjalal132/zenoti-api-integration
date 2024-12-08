<?php

namespace BOILERPLATE\Inc\Traits;

trait Api_Credentials {
    private $api_base_url;
    private $api_key;
    private $center_id;
    private $employee_id;

    public function load_api_credentials() {
        $this->api_base_url = get_option( 'api_url', 'https://api.zenoti.com/v1' );
        $this->api_key      = get_option( 'api_key' );
        $this->employee_id  = get_option( 'option1' );
        $this->center_id    = get_option( 'option2' );
    }
}
