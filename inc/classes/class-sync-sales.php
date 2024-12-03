<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Sync_Sales {

    use Singleton;
    use Program_Logs;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'woocommerce_order_status_completed', [ $this, 'sync_sales' ], 10, 1 );
    }

    /**
     * Sync Sales with zenoti
     * @param int $order_id
     */
    public function sync_sales( $order_id ) {

        // get order
        $order = wc_get_order( $order_id );

    }

}