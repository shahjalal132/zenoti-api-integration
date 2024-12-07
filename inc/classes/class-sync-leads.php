<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Singleton;

class Sync_Leads {

    use Singleton;
    use Program_Logs;

    public function __construct() {
        $this->setup_hooks();
    }

    public function setup_hooks() {
        add_action( 'wp_ajax_lead_generation', [ $this, 'lead_generation' ] );
        add_action( 'wp_ajax_nopriv_lead_generation', [ $this, 'lead_generation' ] );
    }

    public function lead_generation() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_leads';

        // check if the nonce is set
        if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'sync_leads' ) ) {
            wp_send_json_error( 'An error occurred! Please try again.' );
        }

        // Sanitize the input fields
        $first_name = sanitize_text_field( $_POST['first_name'] );
        $last_name  = sanitize_text_field( $_POST['last_name'] );
        $email      = sanitize_email( $_POST['email'] );
        $phone      = sanitize_text_field( $_POST['phone'] );
        $city       = sanitize_text_field( $_POST['city'] );
        $country    = sanitize_text_field( $_POST['country'] );

        $message = sprintf(
            'First Name: %s, Last Name: %s, Email: %s, Phone: %s, City: %s, Country: %s',
            $first_name,
            $last_name,
            $email,
            $phone,
            $city,
            $country
        );
        // $this->put_program_logs( $message );

        // insert the lead data into the database
        $wpdb->insert(
            $table_name,
            [
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'email'      => $email,
                'phone'      => $phone,
                'city'       => $city,
                'country'    => $country,
            ]
        );

        // Respond with success
        wp_send_json_success( 'Lead data logged successfully.' );
    }

}