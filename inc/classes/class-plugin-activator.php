<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 */

class Plugin_Activator {

    public static function activate() {
        // create sync_countries table
        global $wpdb;
        $table_name      = $wpdb->prefix . 'sync_countries';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id INT AUTO_INCREMENT,
            country_id INT NOT NULL,
            phone_code INT NOT NULL,
            country_code VARCHAR(255) NOT NULL,
            country_data TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function create_centers_table() {
        // create sync_centers table
        global $wpdb;
        $table_name      = $wpdb->prefix . 'sync_centers';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
             id INT AUTO_INCREMENT,
             center_id VARCHAR(255) NOT NULL,
             center_code VARCHAR(20) NOT NULL,
             center_name VARCHAR(255) NOT NULL,
             center_data TEXT NOT NULL,
             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
             updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
             PRIMARY KEY (id)
         ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function create_products_table() {
        // create sync_centers table
        global $wpdb;
        $table_name      = $wpdb->prefix . 'sync_products';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
             id INT AUTO_INCREMENT,
             product_id VARCHAR(255) UNIQUE NOT NULL,
             product_code VARCHAR(20) NOT NULL,
             product_data TEXT NOT NULL,
             status VARCHAR(20) NOT NULL DEFAULT 'pending',
             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
             updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
             PRIMARY KEY (id)
         ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

}