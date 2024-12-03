<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 */

class Plugin_Deactivator {

    public static function deactivate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_countries';
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
    }

    public static function remove_centers_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_centers';
        $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
    }

}