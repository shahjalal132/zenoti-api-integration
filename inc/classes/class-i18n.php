<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 * 
 * 
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;

class I18n {

    use Singleton;

    public function __construct() {
        $this->load_plugin_textdomain();
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {

        load_plugin_textdomain(
            'wp-plugin-boilerplate',
            false,
            dirname( dirname( dirname( plugin_basename( __FILE__ ) ) ) ) . '/languages/'
        );

    }

}