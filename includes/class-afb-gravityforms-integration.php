<?php
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AFB_GravityForms_Integration {
    private $blocklist_manager;

    public function __construct( AFB_Blocklist_Manager $blocklist_manager ) {
        $this->blocklist_manager = $blocklist_manager;
        add_filter( 'gform_validation', array( $this, 'validate_gf_submission' ) );
    }

    /**
     * Validates Gravity Forms submission against the blocklist.
     * Hooked to 'gform_validation'.
     *
     * @param array $validation_result The validation result object from Gravity Forms.
     * @return array The modified validation result object.
     */
    public function validate_gf_submission( $validation_result ) {
        $form = $validation_result['form'];

        // Iterate through form fields to find email fields
        foreach ( $form['fields'] as &$field ) { // Use '&' to modify the field object by reference
            // Check standard email field type and also inputType for HTML5 email fields
            if ( $field->type === 'email' || ( isset( $field->inputType ) && $field->inputType === 'email' ) ) {
                $field_id    = $field->id;
                $field_value = rgpost( "input_{$field_id}" ); // Get value from POST using GF helper

                if ( ! empty( $field_value ) && is_email( $field_value ) ) {
                    $block_check = $this->blocklist_manager->check_email_against_list( $field_value );

                    if ( $block_check['blocked'] ) {
                        $validation_result['is_valid'] = false; // Mark form as invalid
                        $field->failed_validation = true;

                        if ( $block_check['reason'] === 'email' ) {
                            $field->validation_message = get_option(
                                'afb_blocked_email_message',
                                __( 'This email address is not allowed for submission.', 'advanced-form-blocker' )
                            );
                        } elseif ( $block_check['reason'] === 'domain' ) {
                            $field->validation_message = get_option(
                                'afb_blocked_domain_message',
                                __( 'Submissions from this email domain are not allowed.', 'advanced-form-blocker' )
                            );
                        } else {
                            // Generic message if reason is somehow not set but blocked
                             $field->validation_message = get_option(
                                'afb_blocked_email_message', // Default to email message
                                __( 'This email address or domain is not allowed for submission.', 'advanced-form-blocker' )
                            );
                        }
                         // Break the loop for this field if blocked (no need to check other block reasons)
                        // but continue checking other email fields in the form
                    }
                }
            }
        }
        // Gravity Forms expects the form object to be part of the validation_result
        $validation_result['form'] = $form;
        return $validation_result;
    }
}