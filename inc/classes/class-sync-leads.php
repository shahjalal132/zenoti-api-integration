<?php

namespace BOILERPLATE\Inc;

use BOILERPLATE\Inc\Traits\Api_Credentials;
use BOILERPLATE\Inc\Traits\Program_Logs;
use BOILERPLATE\Inc\Traits\Search_Guest;
use BOILERPLATE\Inc\Traits\Create_Guest;
use BOILERPLATE\Inc\Traits\Singleton;

class Sync_Leads {

    use Singleton;
    use Search_Guest;
    use Create_Guest;
    use Program_Logs;
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

        try {

            // Query to fetch the first unsynced lead
            $sql  = "SELECT * FROM $table_name WHERE is_synced = 0 LIMIT 1";
            $lead = $wpdb->get_row( $sql );

            // If no unsynced leads are found, return a message
            if ( empty( $lead ) ) {
                return "No leads available to sync.";
            }

            // Extract the lead's email
            $serial_id      = $lead->id;
            $email          = $lead->email;
            $first_name     = $lead->first_name;
            $last_name      = $lead->last_name;
            $city           = $lead->city;
            $country        = $lead->country;
            $guest_id       = "";
            $guest_status   = "";
            $opportunity_id = "";

            // Search for an existing guest by email in the center
            $existing_guest_response = $this->search_a_guest( $this->center_id, $email );
            // $this->put_program_logs( "Existing guest response: " . $existing_guest_response );
            if ( $existing_guest_response === false ) {
                // Throw an error if the search fails
                throw new \Exception( "Error while searching for an existing guest." );
            }

            // Decode the response to get guest details
            $existing_guest = json_decode( $existing_guest_response, true );

            // If a guest is found, retrieve the guest ID and mark the guest as existing
            if ( isset( $existing_guest['page_Info']['total'] ) && $existing_guest['page_Info']['total'] > 0 ) {
                $guest_id     = $existing_guest['guests'][0]['id'];
                $guest_status = "Existing Guest";
            }

            // If no guest ID is found, create a new guest
            if ( empty( $guest_id ) ) {
                // Get the country code based on the lead's country
                $country_code = $this->get_country_code_based_on_country( $country );

                $number = $lead->phone;
                // remove spaces
                $number = str_replace( " ", "", $number );

                // Prepare the payload for creating a new guest
                $create_guest_payload = [
                    'center_id'     => $this->center_id,
                    'personal_info' => [
                        'first_name'   => $first_name,
                        'last_name'    => $last_name,
                        'email'        => $email,
                        'mobile_phone' => [
                            'country_code' => $country_code,
                            'number'       => $number,
                        ],
                    ],
                    'address_info'  => [
                        'city' => $city,
                    ],
                ];

                // Log the guest creation payload
                $this->put_program_logs( 'Guest creation payload: ' . json_encode( $create_guest_payload ) );

                // Call the API to create the guest
                $new_guest_response = $this->create_a_guest( $create_guest_payload );
                // $this->put_program_logs( 'Guest creation response: ' . $new_guest_response );
                if ( $new_guest_response === false ) {
                    // Throw an error if the guest creation fails
                    throw new \Exception( "Error while creating a new guest." );
                }

                // Decode the response to get the new guest's ID
                $new_guest = json_decode( $new_guest_response, true );
                if ( isset( $new_guest['id'] ) ) {
                    $guest_id     = $new_guest['id'];
                    $guest_status = "New Guest"; // Mark the guest as new
                } else {
                    // Throw an error if the guest ID is not returned
                    throw new \Exception( "Failed to create a new guest. API response: $new_guest_response" );
                }
            }

            // If a valid guest ID exists, create an opportunity
            if ( !empty( $guest_id ) ) {
                // Prepare the opportunity title using the lead's name
                $opportunity_title = 'Opportunity for ' . $first_name . ' ' . $last_name;

                // Set the follow-up date to today's date
                $follow_up_date = date( 'Y-m-d' );

                // Prepare the payload for creating an opportunity
                $opportunity_payload = [
                    'center_id'         => $this->center_id,
                    'opportunity_title' => $opportunity_title,
                    'guest_id'          => $guest_id,
                    'created_by_id'     => $this->employee_id,
                    'followup_date'     => $follow_up_date,
                ];

                // Call the API to create the opportunity
                $opportunity_response = $this->create_an_opportunity( $opportunity_payload );
                // $this->put_program_logs( 'Opportunity creation response: ' . $opportunity_response );
                if ( $opportunity_response === false ) {
                    // Throw an error if the opportunity creation fails
                    throw new \Exception( "Error while creating an opportunity." );
                }

                // Decode the response to get the opportunity ID
                $opportunity = json_decode( $opportunity_response, true );
                if ( isset( $opportunity['success'] ) && $opportunity['success'] === true ) {
                    $opportunity_id = $opportunity['opportunity_id'];
                } else {
                    // Throw an error if the opportunity ID is not returned
                    throw new \Exception( "Failed to create an opportunity." );
                }

            } else {
                throw new \Exception( "Failed to create an opportunity. Guest ID is empty." );
            }

            // update the in_synced column
            $wpdb->update(
                $table_name,
                [
                    'opportunity_id' => $opportunity_id,
                    'is_synced'      => 1,
                ],
                [ 'id' => $serial_id ]
            );

            // Return a success message with the guest status and ID
            return "Lead synced successfully. Guest ID: $guest_id Opportunity ID: $opportunity_id. Status: $guest_status";

        } catch (\Exception $e) {
            // Log any errors that occur and return an error message
            $this->put_program_logs( 'Error: ' . $e->getMessage() );
            return "An error occurred: " . $e->getMessage();
        }
    }

    public function get_country_code_based_on_country( string $country ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sync_countries';
        $country_id = $wpdb->get_var( "SELECT country_id FROM $table_name WHERE country_name LIKE '%{$country}%'" );
        return $country_id;
    }

    public function get_all_employees_of_a_center() {

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/centers/{$this->center_id}/employees?page=1&size=100",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_HTTPHEADER     => array(
                'Authorization: apikey ' . $this->api_key,
                'accept: application/json',
            ),
        ) );

        $response = curl_exec( $curl );

        curl_close( $curl );

        // decode response
        $response = json_decode( $response, true );
        if ( isset( $response['employees'] ) ) {
            $employees = $response['employees'];
        }
        return $employees;
    }

    public function create_an_opportunity( array $payload ) {

        $curl = curl_init();
        curl_setopt_array( $curl, array(
            CURLOPT_URL            => "{$this->api_base_url}/opportunities",
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
                'Content-Type: application/json',
            ),
        ) );

        $response = curl_exec( $curl );

        curl_close( $curl );
        return $response;
    }

}