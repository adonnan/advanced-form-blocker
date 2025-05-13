<?php
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AFB_REST_API_Controller extends WP_REST_Controller {
    protected $namespace = 'afb/v1'; // Plugin specific namespace
    protected $rest_base = 'list';   // Endpoint base e.g. /wp-json/afb/v1/list
    private $blocklist_manager;

    // Option names for messages (must match AFB_Admin_Settings)
    private $option_blocked_email_msg = 'afb_blocked_email_message';
    private $option_blocked_domain_msg = 'afb_blocked_domain_message';


    public function __construct( AFB_Blocklist_Manager $blocklist_manager ) {
        $this->blocklist_manager = $blocklist_manager;
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE, // GET requests
                    'callback'            => array( $this, 'get_blocklist_items' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args'                => array(
                        'key' => array(
                            'required'          => true,
                            'type'              => 'string',
                            'description'       => __( 'API key for authentication.', 'advanced-form-blocker' ),
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => function( $param, $request, $key ){
                                return !empty( $param );
                            }
                        ),
                    ),
                ),
                'schema' => array( $this, 'get_public_item_schema' ),
            )
        );
    }

    public function permissions_check( WP_REST_Request $request ) {
        // ... (permissions_check method remains the same)
        $api_enabled = (bool) get_option( 'afb_enable_api', false );
        if ( ! $api_enabled ) {
            return new WP_Error(
                'rest_api_disabled',
                __( 'API access is disabled.', 'advanced-form-blocker' ),
                array( 'status' => 403 )
            );
        }

        $supplied_key = $request->get_param( 'key' );
        $stored_key   = get_option( 'afb_api_key' );

        if ( empty( $supplied_key ) ) {
             return new WP_Error(
                'rest_missing_key',
                __( 'API key is missing.', 'advanced-form-blocker' ),
                array( 'status' => 401 )
            );
        }

        if ( empty( $stored_key ) ) {
            return new WP_Error(
                'rest_server_key_not_set',
                __( 'API key not configured on the server.', 'advanced-form-blocker' ),
                array( 'status' => 500 )
            );
        }

        if ( ! hash_equals( (string) $stored_key, (string) $supplied_key ) ) {
            return new WP_Error(
                'rest_invalid_key',
                __( 'Invalid API key.', 'advanced-form-blocker' ),
                array( 'status' => 403 )
            );
        }
        return true;
    }

    public function get_blocklist_items( WP_REST_Request $request ) {
        $list_data = $this->blocklist_manager->get_blocklist_data();

        // Get custom messages
        $email_message = get_option(
            $this->option_blocked_email_msg,
            __( 'This email address is not allowed for submission.', 'advanced-form-blocker' ) // Default fallback
        );
        $domain_message = get_option(
            $this->option_blocked_domain_msg,
            __( 'Submissions from this email domain are not allowed.', 'advanced-form-blocker' ) // Default fallback
        );

        $response_data = array(
            'domains' => isset($list_data['domains']) ? $list_data['domains'] : array(),
            'emails'  => isset($list_data['emails']) ? $list_data['emails'] : array(),
            'messages' => array( // New 'messages' key
                'blocked_email'  => $email_message,
                'blocked_domain' => $domain_message,
            ),
        );

        return new WP_REST_Response( $response_data, 200 );
    }

    public function get_public_item_schema() {
        // if ( $this->schema ) { // Schema should be built fresh or be a static property
        //     return $this->schema;
        // }
        $schema = array( // Changed from $this->schema to local $schema
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'blocklist_with_messages', // Updated title
            'type'       => 'object',
            'properties' => array(
                'domains' => array(
                    'description' => esc_html__( 'Array of blocked domain names.', 'advanced-form-blocker' ),
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'context'     => array( 'view' ),
                ),
                'emails' => array(
                    'description' => esc_html__( 'Array of blocked email addresses.', 'advanced-form-blocker' ),
                    'type'        => 'array',
                    'items'       => array( 'type' => 'string' ),
                    'context'     => array( 'view' ),
                ),
                'messages' => array( // Schema for the new messages object
                    'description' => esc_html__( 'Custom messages for blocked submissions.', 'advanced-form-blocker' ),
                    'type' => 'object',
                    'context' => array( 'view' ),
                    'properties' => array(
                        'blocked_email' => array(
                            'description' => esc_html__( 'Message shown when a specific email address is blocked.', 'advanced-form-blocker' ),
                            'type' => 'string',
                            'context' => array( 'view' ),
                        ),
                        'blocked_domain' => array(
                            'description' => esc_html__( 'Message shown when an email from a blocked domain is submitted.', 'advanced-form-blocker' ),
                            'type' => 'string',
                            'context' => array( 'view' ),
                        ),
                    ),
                ),
            ),
        );
        return $schema; // Return local $schema
    }
}