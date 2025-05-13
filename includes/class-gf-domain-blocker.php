<?php
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GF_Domain_Blocker {

    private $blocked_list = array();
    private $json_file_path;

    // *** NEW: Define option names for the custom messages (must match admin class) ***
    private $option_name_email_message = 'gf_domain_blocker_email_message';
    private $option_name_domain_message = 'gf_domain_blocker_domain_message';


    public function __construct() {
        $this->json_file_path = GF_DOMAIN_BLOCKER_PATH . 'data/blocked-list.json';
        $this->load_blocked_list();
    }

    public function run() {
        // Hook into Gravity Forms validation
        add_filter( 'gform_validation', array( $this, 'validate_submission' ) );
    }

    private function load_blocked_list() {
        if ( file_exists( $this->json_file_path ) ) {
            $json_content = file_get_contents( $this->json_file_path );
            $this->blocked_list = json_decode( $json_content, true );
            // Ensure keys exist even if the file is empty or malformed
            if ( ! isset( $this->blocked_list['domains'] ) || ! is_array( $this->blocked_list['domains'] ) ) {
                $this->blocked_list['domains'] = array();
            }
            if ( ! isset( $this->blocked_list['emails'] ) || ! is_array( $this->blocked_list['emails'] ) ) {
                $this->blocked_list['emails'] = array();
            }
        } else {
            // If the file doesn't exist, initialize with empty arrays
            $this->blocked_list = array(
                'domains' => array(),
                'emails'  => array(),
            );
        }
    }

    public function get_blocked_list() {
        return $this->blocked_list;
    }

    /**
     * Validates the form submission against the blocked domains and emails.
     * Hooked to 'gform_validation'.
     *
     * @param array $validation_result The validation result object.
     * @return array The modified validation result object.
     */
    public function validate_submission( $validation_result ) {
        $form = $validation_result['form'];
        $blocked_domains = $this->blocked_list['domains'];
        $blocked_emails = $this->blocked_list['emails'];

        // *** NEW: Retrieve custom block messages from options ***
        // Get saved messages or use default fallbacks if options are not set or are empty
        $blocked_email_message_option = get_option( $this->option_name_email_message, '' ); // Get saved option, default to empty string
        $blocked_domain_message_option = get_option( $this->option_name_domain_message, '' ); // Get saved option, default to empty string

        // Use the retrieved message if not empty, otherwise fall back to a hardcoded default
        $email_block_message = ! empty( $blocked_email_message_option ) ? $blocked_email_message_option : esc_html__( 'Your email address is blocked.', 'gravity-forms-domain-blocker' );
        $domain_block_message = ! empty( $blocked_domain_message_option ) ? $blocked_domain_message_option : esc_html__( 'Emails from your domain are blocked.', 'gravity-forms-domain-blocker' );


        // Iterate through form fields to find email fields
        foreach ( $form['fields'] as &$field ) { // Use '&' to modify the field object by reference
            if ( $field->type === 'email' ) {
                $field_value = rgpost( 'input_' . $field->id );

                if ( ! empty( $field_value ) ) {
                    // Check against blocked emails
                    if ( in_array( $field_value, $blocked_emails, true ) ) {
                        $validation_result['is_valid'] = false;
                        $field->failed_validation = true;
                        $field->validation_message = $email_block_message; // Use the custom message
                        break; // No need to check domains if email is blocked
                    }

                    // Check against blocked domains
                    $email_parts = explode( '@', $field_value );
                    if ( count( $email_parts ) === 2 ) {
                        $domain = strtolower( $email_parts[1] );
                        if ( in_array( $domain, $blocked_domains, true ) ) {
                            $validation_result['is_valid'] = false;
                            $field->failed_validation = true;
                            $field->validation_message = $domain_block_message; // Use the custom message
                            break; // Domain is blocked
                        }
                    }
                }
            }
        }

        $validation_result['form'] = $form; // Ensure the modified form object is returned
        return $validation_result;
    }

    // Method to update the blocked list from an array (for admin save)
    public function update_blocked_list( $new_list ) {
        $json_content = json_encode( $new_list, JSON_PRETTY_PRINT );
        if ( file_put_contents( $this->json_file_path, $json_content ) !== false ) {
            $this->blocked_list = $new_list;
            return true;
        }
        return false;
    }
}