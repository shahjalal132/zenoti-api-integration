<?php

/**
 * Enqueue Plugin Admin and Public Assets
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Enqueue_Assets {

    use Singleton;
    use Program_Logs;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        // Actions for admin assets
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        // Actions for public assets
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
    }

    /**
     * Enqueue Admin Assets.
     * @param mixed $page_now Current page
     * @return void
     */
    public function enqueue_admin_assets( $page_now ) {

        // $this->put_program_logs( 'Current Page: ' . $page_now );
        if ( 'settings_page_menu-slug2' === $page_now ) {

            // enqueue admin css
            // wp_enqueue_style( "wpb-bootstrap", PLUGIN_ADMIN_ASSETS_DIR_URL . "/css/bootstrap.min.css", [], false, "all" );
            wp_enqueue_style( "toast", PLUGIN_ADMIN_ASSETS_DIR_URL . "/css/toast.css", [], false, "all" );
            wp_enqueue_style( "wpb-admin-css", PLUGIN_ADMIN_ASSETS_DIR_URL . "/css/admin-style.css", [], time(), "all" );

            // enqueue admin js
            // wp_enqueue_script( "wpb-bootstrap", PLUGIN_ADMIN_ASSETS_DIR_URL . "/js/bootstrap.bundle.min.js", [], false, true );
            wp_enqueue_script( "wpb-admin-js", PLUGIN_ADMIN_ASSETS_DIR_URL . "/js/admin-script.js", [ 'jquery' ], time(), true );
            wp_localize_script( "wpb-admin-js", "wpb_admin_localize", array( "ajax_url" => admin_url( "admin-ajax.php" ) ) );
        }

    }

    /**
     * Enqueue Public Assets.
     * @return void
     */
    public function enqueue_public_assets() {

        // enqueue public css
        wp_enqueue_style( "wpb-bootstrap", PLUGIN_PUBLIC_ASSETS_URL . "/css/bootstrap.min.css", [], time(), "all" );
        wp_enqueue_style( "wpb-public-css", PLUGIN_PUBLIC_ASSETS_URL . "/css/public-style.css", [], time(), "all" );

        // enqueue public js    
        wp_enqueue_script( "wpb-bootstrap", PLUGIN_PUBLIC_ASSETS_URL . "/js/bootstrap.bundle.min.js", [], false, true );
        wp_enqueue_script( "wpb-public-js", PLUGIN_PUBLIC_ASSETS_URL . "/js/public-script.js", [ 'jquery' ], time(), true );

    }

}