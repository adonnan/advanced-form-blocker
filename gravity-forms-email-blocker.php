<?php
/**
 * Plugin Name: Gravity Forms Domain and Email Blocker
 * Description: Blocks Gravity Forms submissions based on a JSON file of prohibited domains and email addresses.
 * Version: 1.0
 * Author: Andrew Donnan
 * Text Domain: gravity-forms-domain-blocker
 * Domain Path: /languages
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants for easier management
define( 'GF_DOMAIN_BLOCKER_PATH', plugin_dir_path( __FILE__ ) );
define( 'GF_DOMAIN_BLOCKER_URL', plugin_dir_url( __FILE__ ) );
define( 'GF_DOMAIN_BLOCKER_BASENAME', plugin_basename( __FILE__ ) );

// Include necessary files
require_once GF_DOMAIN_BLOCKER_PATH . 'includes/class-gf-domain-blocker.php';
require_once GF_DOMAIN_BLOCKER_PATH . 'admin/class-gf-domain-blocker-admin.php';

// Load the plugin textdomain for internationalization
function gf_domain_blocker_load_textdomain() {
    load_plugin_textdomain( 'gravity-forms-domain-blocker', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'gf_domain_blocker_load_textdomain' );


// Initialize the plugin
function gf_domain_blocker_init() {
    $gf_domain_blocker = new GF_Domain_Blocker();
    $gf_domain_blocker->run(); // This calls the run method which adds the gform_validation filter

    if ( is_admin() ) {
        $gf_domain_blocker_admin = new GF_Domain_Blocker_Admin();
        $gf_domain_blocker_admin->run(); // This calls the run method which adds admin hooks
    }
}
add_action( 'plugins_loaded', 'gf_domain_blocker_init' );

// *** Conditionally register the REST API endpoint based on the setting ***

// Define the option name for the Pardot enable setting (must match admin class)
$gf_domain_blocker_enable_pardot_option_name = 'gf_domain_blocker_enable_pardot';
// Define option names for the custom messages (must match admin class)
$gf_domain_blocker_email_message_option_name = 'gf_domain_blocker_email_message';
$gf_domain_blocker_domain_message_option_name = 'gf_domain_blocker_domain_message';


// Get the value of the setting. Default to false if the option doesn't exist.
// We cast to boolean because get_option can return various types.
$enable_pardot = (bool) get_option( $gf_domain_blocker_enable_pardot_option_name, false );

// Check if the Pardot integration is enabled
if ( $enable_pardot ) {
    // Only register the REST API endpoint if the setting is enabled

    /**
     * Register a custom REST API endpoint to retrieve the blocked list and custom messages.
     * This creates an endpoint like /wp-json/gf-domain-blocker/v1/blocked-list
     */
    function gf_domain_blocker_register_rest_endpoint() {
        register_rest_route( 'gf-domain-blocker/v1', '/blocked-list', array(
            'methods' => 'GET', // This endpoint should only respond to GET requests
            'callback' => 'gf_domain_blocker_get_blocked_list_data', // The function that will handle the request
            'permission_callback' => '__return_true', // Allows public access to this endpoint
            'args' => array(), // No specific arguments needed for this endpoint
        ) );
    }
    // Hook the endpoint registration to the REST API initialization
    add_action( 'rest_api_init', 'gf_domain_blocker_register_rest_endpoint' );

    /**
     * Callback function for the REST API endpoint.
     * Retrieves the blocked list and custom messages and returns them.
     *
     * @param WP_REST_Request $request The REST API request object.
     * @return WP_REST_Response The REST API response containing the data.
     */
    function gf_domain_blocker_get_blocked_list_data( WP_REST_Request $request ) {
        // Get the file system path to the JSON file using the defined constant
        $json_file_path = GF_DOMAIN_BLOCKER_PATH . 'data/blocked-list.json';

        $blocked_list = array( 'domains' => array(), 'emails' => array() );

        // Check if the blocked list JSON file exists and load its content
        if ( file_exists( $json_file_path ) ) {
            $json_content = file_get_contents( $json_file_path );
            $decoded_list = json_decode( $json_content, true );

            // Ensure the decoded data is valid and contains the expected keys (domains and emails)
            if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_list ) ) {
                 if ( isset( $decoded_list['domains'] ) && is_array( $decoded_list['domains'] ) ) {
                     $blocked_list['domains'] = $decoded_list['domains'];
                 }
                 if ( isset( $decoded_list['emails'] ) && is_array( $decoded_list['emails'] ) ) {
                     $blocked_list['emails'] = $decoded_list['emails'];
                 }
            }
        }

        // *** NEW: Fetch the custom block messages from the options table ***
        // Use the option names defined at the top of this file
        $email_message = get_option( $GLOBALS['gf_domain_blocker_email_message_option_name'], '' );
        $domain_message = get_option( $GLOBALS['gf_domain_blocker_domain_message_option_name'], '' );

        // Prepare the response data, including the blocked list and the messages
        $response_data = array(
            'domains' => $blocked_list['domains'],
            'emails'  => $blocked_list['emails'],
            'messages' => array( // Include messages in a nested array
                'email' => ! empty( $email_message ) ? $email_message : esc_html__( 'Your email address is blocked.', 'gravity-forms-domain-blocker' ),
                'domain' => ! empty( $domain_message ) ? $domain_message : esc_html__( 'Emails from your domain are blocked.', 'gravity-forms-domain-blocker' ),
            ),
        );

        // Return the data as a JSON response with a 200 OK status
        return new WP_REST_Response( $response_data, 200 );
    }
}


// Add a settings link to the plugin list page.
// This function is outside the classes but needs access to the admin tab slug.
/**
 * Add a settings link to the plugin list page.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified array of links.
 */
function gf_domain_blocker_add_action_links( $links ) {
    // Instantiate the admin class to get the settings tab slug
    $admin_class = new GF_Domain_Blocker_Admin();
    $settings_tab_slug = $admin_class->get_settings_tab_slug();

    // Create the settings link URL
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=gf_settings&subview=' . $settings_tab_slug ) ) . '">' . esc_html__( 'Settings', 'gravity-forms-domain-blocker' ) . '</a>';

    // Add the settings link to the beginning of the links array
    array_unshift( $links, $settings_link );
    return $links;
}
// Hook into the plugin_action_links_{$plugin_basename} filter
add_filter( 'plugin_action_links_' . GF_DOMAIN_BLOCKER_BASENAME, 'gf_domain_blocker_add_action_links' );