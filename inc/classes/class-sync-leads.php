<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Api_Credentials;
use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Search_Guest;
use BOILERPLATE\Inc\Traits\Singleton;

class Sync_Leads {

    use Singleton;
    use Program_Logs;
    use Search_Guest;
    use Api_Credentials;

    public function __construct() {
        $this->setup_hooks();
        // Initialize credentials
        $this->load_api_credentials();
    }

    public function setup_hooks() {
        add_action( 'wp_ajax_lead_generation', [ $this, 'lead_generation' ] );
        add_action( 'wp_ajax_nopriv_lead_generation', [ $this, 'lead_generation' ] );

        // Register REST API action
        add_action( 'rest_api_init', [ $this, 'register_rest_route' ] );

    }

    public function register_rest_route() {

        register_rest_route( 'api/v1', '/sync-leads', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'sync_leads' ],
            'permission_callback' => '__return_true',
        ] );

    }

    public function lead_generation() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_leads';

        try {
            // Check if the nonce is set and valid
            if ( !isset( $_POST['nonce'] ) || !wp_verify_nonce( $_POST['nonce'], 'sync_leads' ) ) {
                throw new \Exception( 'Invalid nonce. Possible CSRF attack.' );
            }

            // Sanitize input fields
            $first_name = sanitize_text_field( $_POST['first_name'] );
            $last_name  = sanitize_text_field( $_POST['last_name'] );
            $email      = sanitize_email( $_POST['email'] );
            $phone      = sanitize_text_field( $_POST['phone'] );
            $city       = sanitize_text_field( $_POST['city'] );
            $country    = sanitize_text_field( $_POST['country'] );

            // Validate email
            if ( !is_email( $email ) ) {
                throw new \Exception( 'Invalid email address provided.' );
            }

            // Prepare message for debugging
            $message = sprintf(
                'First Name: %s, Last Name: %s, Email: %s, Phone: %s, City: %s, Country: %s',
                $first_name,
                $last_name,
                $email,
                $phone,
                $city,
                $country
            );
            // $this->put_program_logs('Lead submission received: ' . $message);

            // Insert the lead data into the database
            $insert = $wpdb->insert(
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

            // Check if the insert was successful
            if ( !$insert ) {
                $last_error    = $wpdb->last_error;
                $error_message = 'Failed to insert lead data into the database. ' . ( !empty( $last_error ) ? 'Database error: ' . $last_error : 'No additional error information available.' );
                $this->put_program_logs( $error_message );
                throw new \Exception( 'An error occurred while saving lead data. Please try again later.' );
            }

            // Respond with success
            wp_send_json_success( 'Lead data logged successfully.' );
        } catch (\Exception $e) {
            // Log error
            $this->put_program_logs( 'Error in lead generation: ' . $e->getMessage() );

            // Respond with error message
            wp_send_json_error( $e->getMessage() );
        }
    }

    public function sync_leads() {

        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_leads';

        // prepare query
        $sql = "SELECT * FROM $table_name WHERE is_synced = 0 LIMIT 1";
        // get lead
        $lead = $wpdb->get_row( $sql );

        if ( empty( $lead ) ) {
            return "Didn't find OR No leads to sync";
        }

        // extract email
        $email = $lead->email;

        // initialize guest id
        $guest_id = "";

        // search guest
        $existing_guest = $this->search_a_guest( $this->center_id, $email );
        // $this->put_program_logs( 'Guest Response: ' . $existing_guest );
        // decode response
        $existing_guest = json_decode( $existing_guest, true );
        // check if existing_guest exists
        if ( isset( $existing_guest['page_Info']['total'] ) && $existing_guest['page_Info']['total'] > 0 ) {
            $guest_id = $existing_guest['guests'][0]['id'];
        }

        /**
         * TODO: Create guest if not found.
         * Generate payload
         * Create opportunity
         */

        return $guest_id;
    }

}