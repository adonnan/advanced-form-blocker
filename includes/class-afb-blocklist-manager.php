<?php
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AFB_Blocklist_Manager {
    private $json_file_path;
    private $blocklist_data_cache = null; // Internal cache for the current request
    private $transient_name = 'afb_parsed_blocklist_data';

    public function __construct( $json_file_path ) {
        $this->json_file_path = $json_file_path;
    }

    /**
     * Loads the blocklist from the JSON file, with caching.
     * Ensures 'domains' and 'emails' keys exist.
     *
     * @param bool $force_refresh Skip cache and reload from file.
     * @return array The blocklist data (['domains' => [], 'emails' => []]).
     */
    public function get_blocklist_data( $force_refresh = false ) {
        if ( ! $force_refresh && null !== $this->blocklist_data_cache ) {
            return $this->blocklist_data_cache;
        }

        $cached_list = get_transient( $this->transient_name );
        if ( ! $force_refresh && false !== $cached_list && is_array($cached_list) ) {
            $this->blocklist_data_cache = $cached_list;
            return $cached_list;
        }

        $default_list_structure = array( 'domains' => array(), 'emails' => array() );

        if ( ! file_exists( $this->json_file_path ) ) {
            // Create an empty file if it doesn't exist to prevent repeated checks
            // The activation hook should ideally create this.
            @file_put_contents( $this->json_file_path, json_encode( $default_list_structure, JSON_PRETTY_PRINT ) );
            $this->blocklist_data_cache = $default_list_structure;
            set_transient( $this->transient_name, $default_list_structure, HOUR_IN_SECONDS ); // Cache for 1 hour
            return $default_list_structure;
        }

        $json_content = @file_get_contents( $this->json_file_path );
        if ( false === $json_content ) {
            error_log( 'Advanced Form Blocker: Could not read blocklist file: ' . $this->json_file_path );
            $this->blocklist_data_cache = $default_list_structure;
            // Don't set transient if file read fails, to allow retry
            return $default_list_structure;
        }

        $decoded_list = json_decode( $json_content, true );

        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded_list ) ) {
            error_log( 'Advanced Form Blocker: Invalid JSON in blocklist file: ' . $this->json_file_path . ' - Error: ' . json_last_error_msg() );
            $this->blocklist_data_cache = $default_list_structure;
            // Don't set transient if JSON is invalid, to allow retry after fix
            return $default_list_structure;
        }

        // Ensure keys exist and are arrays, and lowercase all entries for consistent matching
        $this->blocklist_data_cache = array(
            'domains' => isset( $decoded_list['domains'] ) && is_array( $decoded_list['domains'] )
                         ? array_map( 'strtolower', array_map( 'trim', $decoded_list['domains'] ) )
                         : array(),
            'emails'  => isset( $decoded_list['emails'] ) && is_array( $decoded_list['emails'] )
                         ? array_map( 'strtolower', array_map( 'trim', $decoded_list['emails'] ) )
                         : array(),
        );

        set_transient( $this->transient_name, $this->blocklist_data_cache, HOUR_IN_SECONDS ); // Cache for 1 hour
        return $this->blocklist_data_cache;
    }

    /**
     * Checks if an email address or its domain is in the blocklist.
     *
     * @param string $email_address The email address to check.
     * @return array ['blocked' => bool, 'reason' => string|null ('email' or 'domain')]
     */
    public function check_email_against_list( $email_address ) {
        $email_address = strtolower( trim( $email_address ) );
        if ( ! is_email( $email_address ) ) {
            return array( 'blocked' => false, 'reason' => null ); // Not a valid email, can't block
        }

        $list = $this->get_blocklist_data(); // Ensures data is loaded (and possibly cached)

        // Check full email address (case-insensitive due to strtolower on list and input)
        if ( !empty($list['emails']) && in_array( $email_address, $list['emails'], true ) ) {
            return array( 'blocked' => true, 'reason' => 'email' );
        }

        // Check domain (case-insensitive)
        $email_parts = explode( '@', $email_address );
        if ( count( $email_parts ) === 2 ) {
            $domain = $email_parts[1];
            if ( !empty($list['domains']) && in_array( $domain, $list['domains'], true ) ) {
                return array( 'blocked' => true, 'reason' => 'domain' );
            }
        }

        return array( 'blocked' => false, 'reason' => null );
    }

    /**
     * Handles the upload of a new JSON blocklist file.
     *
     * @param array $file_array The $_FILES array for the uploaded file.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function process_uploaded_blocklist( $file_array ) {
        if ( ! isset( $file_array['tmp_name'] ) || empty( $file_array['tmp_name'] ) ) {
            return new WP_Error( 'no_file', __( 'No file was uploaded.', 'advanced-form-blocker' ) );
        }

        // Validate file type (should be JSON)
        $file_info = wp_check_filetype_and_ext( $file_array['tmp_name'], $file_array['name'], array( 'json' => 'application/json' ) );
        if ( 'json' !== $file_info['ext'] && 'application/json' !== $file_info['type'] ) {
             // Fallback for systems that might not correctly identify JSON mime type from tmp_name
            if (pathinfo($file_array['name'], PATHINFO_EXTENSION) !== 'json') {
                return new WP_Error( 'invalid_file_type', __( 'Invalid file type. Please upload a .json file.', 'advanced-form-blocker' ) );
            }
        }

        $json_content = @file_get_contents( $file_array['tmp_name'] );
        if ( false === $json_content ) {
            return new WP_Error( 'file_read_error', __( 'Could not read the uploaded file.', 'advanced-form-blocker' ) );
        }

        $decoded_list = json_decode( $json_content, true );

        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $decoded_list ) ) {
            return new WP_Error( 'invalid_json_format', __( 'Invalid JSON format in the uploaded file.', 'advanced-form-blocker' ) );
        }

        // Ensure 'domains' and 'emails' keys exist, even if empty
        $sanitized_list = array(
            'domains' => isset( $decoded_list['domains'] ) && is_array( $decoded_list['domains'] )
                         ? array_values( array_unique( array_map( 'sanitize_text_field', array_map( 'trim', $decoded_list['domains'] ) ) ) )
                         : array(),
            'emails'  => isset( $decoded_list['emails'] ) && is_array( $decoded_list['emails'] )
                         ? array_values( array_unique( array_filter( array_map( 'sanitize_email', array_map( 'trim', $decoded_list['emails'] ) ), 'is_email' ) ) )
                         : array(),
        );

        // Attempt to write the new list to the designated file path
        $result = @file_put_contents( $this->json_file_path, json_encode( $sanitized_list, JSON_PRETTY_PRINT ) );

        if ( false === $result ) {
            return new WP_Error( 'file_write_error', __( 'Could not write the blocklist file. Please check file permissions for the uploads directory.', 'advanced-form-blocker' ) . ' Path: ' . esc_html($this->json_file_path) );
        }

        // Clear cache after successful update
        delete_transient( $this->transient_name );
        $this->blocklist_data_cache = null; // Clear internal cache

        return true;
    }

    /**
     * Gets the raw content of the JSON file for display.
     *
     * @return string JSON content or an error message.
     */
    public function get_raw_json_content_for_display() {
        if ( ! file_exists( $this->json_file_path ) ) {
            return json_encode( array( 'domains' => array(), 'emails' => array() ), JSON_PRETTY_PRINT );
        }
        $content = @file_get_contents( $this->json_file_path );
        if (false === $content) {
            return esc_html__('Error: Could not read the blocklist file.', 'advanced-form-blocker');
        }
        // Try to pretty print if it's valid JSON, otherwise return raw
        $decoded = json_decode($content);
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_PRETTY_PRINT);
        }
        return $content; // Return raw if not perfectly valid but readable
    }
}