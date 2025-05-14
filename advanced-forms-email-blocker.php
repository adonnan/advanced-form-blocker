<?php
/**
 * Plugin Name: Advanced Form Blocker
 * Plugin URI: https://github.com/adonnan/advanced-form-blocker
 * Description: A WordPress add-on plugin for Gravity Forms to block form submissions from unwanted email addresses and domains, with a secure API for external list access.
 * Version: 1.0.0
 * Author: Andrew Donnan
 * Author URI: https://i.andrewdonnan.com/development
 * License: GPLv2 or later
 * Text Domain: advanced-form-blocker
 * Domain Path: /languages
* Requires at least: 5.2
 * Requires PHP:      7.2
 * GitHub Plugin URI: adonnan/advanced-form-blocker
 * Release Asset:     true
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'AFB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AFB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AFB_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'AFB_JSON_FILE_NAME', 'blocklist.json' ); // Name of the JSON file
define( 'AFB_UPLOAD_SUBDIR', 'advanced-form-blocker' ); // Subdirectory in wp-content/uploads

// Function to get the full path to the blocklist JSON file
function afb_get_json_file_path() {
    $upload_dir = wp_upload_dir();
    $blocker_dir = trailingslashit( $upload_dir['basedir'] ) . AFB_UPLOAD_SUBDIR;

    // Ensure the directory exists
    if ( ! file_exists( $blocker_dir ) ) {
        wp_mkdir_p( $blocker_dir );
    }
    // Optionally add an .htaccess or index.php to prevent direct listing if directory indexing is on
    if ( ! file_exists( $blocker_dir . '/index.php' ) ) {
        @file_put_contents( $blocker_dir . '/index.php', '<?php // Silence is golden.' );
    }
    if ( ! file_exists( $blocker_dir . '/.htaccess' ) ) {
        @file_put_contents( $blocker_dir . '/.htaccess', 'deny from all' );
    }

    return trailingslashit( $blocker_dir ) . AFB_JSON_FILE_NAME;
}

// Include necessary class files
require_once AFB_PLUGIN_DIR . 'includes/class-afb-blocklist-manager.php';
require_once AFB_PLUGIN_DIR . 'includes/class-afb-gravityforms-integration.php';
require_once AFB_PLUGIN_DIR . 'includes/class-afb-admin-settings.php';
require_once AFB_PLUGIN_DIR . 'includes/class-afb-rest-api-controller.php';

// Load the plugin textdomain for internationalization
function afb_load_textdomain() {
    load_plugin_textdomain( 'advanced-form-blocker', false, dirname( AFB_PLUGIN_BASENAME ) . '/languages' );
}
add_action( 'init', 'afb_load_textdomain' );

// Initialize the plugin components
function afb_init_plugin() {
    $json_file_path = afb_get_json_file_path();
    $blocklist_manager = new AFB_Blocklist_Manager( $json_file_path );

    // Initialize Gravity Forms integration if Gravity Forms is active
    if ( class_exists( 'GFCommon' ) ) {
        new AFB_GravityForms_Integration( $blocklist_manager );
    }

    // Initialize admin settings area
    if ( is_admin() ) {
        new AFB_Admin_Settings( $blocklist_manager );
    }

    // Initialize REST API controller (conditionally based on settings if desired, or always for API key check)
    // The AFB_REST_API_Controller itself can check if the API is enabled via an option
    new AFB_REST_API_Controller( $blocklist_manager );
}
add_action( 'plugins_loaded', 'afb_init_plugin', 20 ); // Priority 20 to ensure GF might be loaded

// Activation hook
register_activation_hook( __FILE__, 'afb_activate_plugin' );
function afb_activate_plugin() {
    // Generate initial API key if it doesn't exist
    if ( ! get_option( 'afb_api_key' ) ) {
        update_option( 'afb_api_key', wp_generate_password( 40, false, false ) );
    }

    // Create an empty blocklist.json if it doesn't exist in the new location
    $json_path = afb_get_json_file_path(); // this also creates the directory
    if ( ! file_exists( $json_path ) ) {
        $initial_list = array( 'domains' => array(), 'emails' => array() );
        @file_put_contents( $json_path, json_encode( $initial_list, JSON_PRETTY_PRINT ) );
    }

    // Set default error messages
    if ( false === get_option( 'afb_blocked_email_message' ) ) {
        update_option( 'afb_blocked_email_message', __( 'This email address is not allowed for submission.', 'advanced-form-blocker' ) );
    }
    if ( false === get_option( 'afb_blocked_domain_message' ) ) {
        update_option( 'afb_blocked_domain_message', __( 'Submissions from this email domain are not allowed.', 'advanced-form-blocker' ) );
    }
    if ( false === get_option( 'afb_enable_api' ) ) {
        update_option( 'afb_enable_api', '0' ); // API disabled by default
    }
}

// Deactivation hook (optional, for cleanup)
register_deactivation_hook( __FILE__, 'afb_deactivate_plugin' );
function afb_deactivate_plugin() {
    // Clear transients if any were used extensively for the blocklist
    delete_transient( 'afb_parsed_blocklist_data' );
    // Consider if options should be deleted upon deactivation or only on uninstall.
    // Typically, options are kept unless there's a specific reason to remove them.
}

// Add a settings link to the plugin list page
function afb_add_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=afb-settings' ) ) . '">' . esc_html__( 'Settings', 'advanced-form-blocker' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . AFB_PLUGIN_BASENAME, 'afb_add_action_links' );

?>