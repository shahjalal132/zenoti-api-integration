<?php
/**
 * Bootstraps the plugin. load class.
 */

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Singleton;

class Autoloader {
    use Singleton;

    protected function __construct() {

        // load class.
        I18n::get_instance();
        Enqueue_Assets::get_instance();
        // Admin_Top_Menu::get_instance();
        Admin_Sub_Menu::get_instance();
        Sync_Sales::get_instance();
        Sync_Countries::get_instance();
        Sync_Inventory::get_instance();
    }
}