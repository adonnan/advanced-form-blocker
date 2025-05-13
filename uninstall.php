<?php
/**
 * Uninstall routines for Advanced Form Blocker
 *
 * This file is executed when the plugin is uninstalled (deleted) from the WordPress admin.
 * It should clean up all plugin-specific data.
 *
 * @package Advanced_Form_Blocker
 * @since 1.1.0
 */

// If uninstall.php is not called by WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// --- Define Option Names (must match those used in the plugin) ---
$option_api_key = 'afb_api_key';
$option_enable_api = 'afb_enable_api';
$option_blocked_email_msg = 'afb_blocked_email_message';
$option_blocked_domain_msg = 'afb_blocked_domain_message';

// --- Define Transient Name ---
$transient_name = 'afb_parsed_blocklist_data';

// --- Define Constants for File Paths (must match your main plugin file) ---
// We can't rely on the plugin being active, so redefine them or hardcode paths carefully.
// It's safer to reconstruct the path to the JSON file.
define( 'AFB_UNINSTALL_JSON_FILE_NAME', 'blocklist.json' );
define( 'AFB_UNINSTALL_UPLOAD_SUBDIR', 'advanced-form-blocker' );

/**
 * Get the full path to the blocklist JSON file for uninstallation.
 * This function is self-contained for the uninstall script.
 *
 * @return string The path to the JSON file.
 */
function afb_uninstall_get_json_file_path() {
    $upload_dir_info = wp_upload_dir(); // Get WordPress upload directory info
    $blocker_dir_path = trailingslashit( $upload_dir_info['basedir'] ) . AFB_UNINSTALL_UPLOAD_SUBDIR;
    return trailingslashit( $blocker_dir_path ) . AFB_UNINSTALL_JSON_FILE_NAME;
}

// --- Delete Plugin Options ---
delete_option( $option_api_key );
delete_option( $option_enable_api );
delete_option( $option_blocked_email_msg );
delete_option( $option_blocked_domain_msg );

// --- Delete Transients ---
delete_transient( $transient_name );

// --- Delete the JSON Blocklist File and its Directory ---
$json_file_path = afb_uninstall_get_json_file_path();
$blocker_directory_path = dirname( $json_file_path );

// Delete the JSON file
if ( file_exists( $json_file_path ) ) {
    @unlink( $json_file_path );
}

// Attempt to delete the plugin's specific subdirectory in uploads,
// but only if it's empty after deleting the JSON file.
// We check for 'index.php' and '.htaccess' as they might have been created by the plugin.
if ( is_dir( $blocker_directory_path ) ) {
    // Check if the directory is empty (or only contains files we added like index.php/.htaccess)
    $is_empty = true;
    $files_in_dir = @scandir( $blocker_directory_path );

    if ( $files_in_dir ) {
        foreach ( $files_in_dir as $file ) {
            if ( $file !== '.' && $file !== '..' && $file !== 'index.php' && $file !== '.htaccess' && $file !== AFB_UNINSTALL_JSON_FILE_NAME /* already unlinked */ ) {
                $is_empty = false; // Found other files/directories
                break;
            }
        }
    } else {
        // Could not scan directory, assume not safe to delete or already gone.
        $is_empty = false;
    }

    if ( $is_empty ) {
        // Clean up index.php and .htaccess if they exist before trying to remove the directory
        if ( file_exists( $blocker_directory_path . '/index.php' ) ) {
            @unlink( $blocker_directory_path . '/index.php' );
        }
        if ( file_exists( $blocker_directory_path . '/.htaccess' ) ) {
            @unlink( $blocker_directory_path . '/.htaccess' );
        }

        // Now try to remove the directory itself
        @rmdir( $blocker_directory_path );
    }
    // If the directory is not empty (contains unexpected files), we don't remove it to be safe.
    // You might want to log this scenario if you have logging capabilities.
}

// --- Future Cleanup ---
// If you add custom database tables or other persistent data, add their cleanup logic here.
// Example:
// global $wpdb;
// $table_name = $wpdb->prefix . 'my_custom_table';
// $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );