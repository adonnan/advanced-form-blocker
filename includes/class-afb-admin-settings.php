<?php
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AFB_Admin_Settings {
    private $blocklist_manager;
    private $options_group = 'afb_settings_group';
    private $page_slug = 'afb-settings';

    // Option names
    private $option_api_key = 'afb_api_key';
    private $option_enable_api = 'afb_enable_api';
    private $option_blocked_email_msg = 'afb_blocked_email_message';
    private $option_blocked_domain_msg = 'afb_blocked_domain_message';

    public function __construct( AFB_Blocklist_Manager $blocklist_manager ) {
        $this->blocklist_manager = $blocklist_manager;
        add_action( 'admin_menu', array( $this, 'add_admin_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings_for_options_php' ) );
        add_action( 'admin_post_afb_upload_json', array( $this, 'handle_json_upload_action' ) );
        add_action( 'admin_post_afb_regenerate_api_key', array( $this, 'handle_regenerate_api_key_action' ) );
    }

    public function add_admin_menu_page() {
        add_options_page(
            __( 'Advanced Form Blocker Settings', 'advanced-form-blocker' ),
            __( 'Form Blocker', 'advanced-form-blocker' ),
            'manage_options',
            $this->page_slug,
            array( $this, 'render_settings_page_html' )
        );
    }

    public function register_settings_for_options_php() {
    register_setting( $this->options_group, $this->option_enable_api, 'absint' );
    register_setting( $this->options_group, $this->option_blocked_email_msg, 'sanitize_text_field' );
    register_setting( $this->options_group, $this->option_blocked_domain_msg, 'sanitize_text_field' );

    // ... rest of the function remains the same ...
    add_settings_section(
        'afb_api_options_section',
        __( 'API Configuration & Status', 'advanced-form-blocker' ),
        array($this, 'render_api_section_description'),
        $this->options_group
    );
    add_settings_field(
        $this->option_enable_api,
        __( 'Enable API Access', 'advanced-form-blocker' ),
        array( $this, 'render_enable_api_field' ),
        $this->options_group,
        'afb_api_options_section'
    );

        add_settings_section(
            'afb_messages_options_section',
            __( 'Custom Block Messages', 'advanced-form-blocker' ),
            null,
            $this->options_group
        );
        add_settings_field(
            $this->option_blocked_email_msg,
            __( 'Blocked Email Message', 'advanced-form-blocker' ),
            array( $this, 'render_blocked_email_msg_field' ),
            $this->options_group,
            'afb_messages_options_section'
        );
        add_settings_field(
            $this->option_blocked_domain_msg,
            __( 'Blocked Domain Message', 'advanced-form-blocker' ),
            array( $this, 'render_blocked_domain_msg_field' ),
            $this->options_group,
            'afb_messages_options_section'
        );
    }

    public function render_json_upload_form_only() {
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="afb_upload_json">
            <?php wp_nonce_field( 'afb_upload_json_nonce_action', 'afb_upload_json_nonce' ); ?>
            <input type="file" name="afb_blocklist_json_file" id="afb_blocklist_json_file" accept=".json" style="margin-right: 10px;">
            <?php submit_button( __( 'Upload File', 'advanced-form-blocker' ), 'secondary', 'submit_upload_json', false, array('style' => 'vertical-align: middle;') ); ?>
        </form>
        <?php
    }
    
    public function render_json_upload_description() {
        ?>
         <p class="description" style="margin-top: 5px;">
            <?php esc_html_e( 'Upload your `blocklist.json` file. Example format:', 'advanced-form-blocker' ); ?>
            <br><code>{"domains": ["blockeddomain.com"], "emails": ["user@example.com"]}</code>
        </p>
        <p style="margin-top: 5px;">
            <?php esc_html_e( 'Current file location:', 'advanced-form-blocker' ); ?>
            <code><?php echo esc_html( afb_get_json_file_path() ); ?></code>
        </p>
        <?php
    }

    public function render_current_json_field() {
        $json_content = $this->blocklist_manager->get_raw_json_content_for_display();
        ?>
        <textarea readonly rows="10" cols="70" class="widefat code" style="margin-top:10px;"><?php echo esc_textarea( $json_content ); ?></textarea>
        <p class="description"><?php esc_html_e( 'This is the current content of your blocklist file. Update by uploading a new file above.', 'advanced-form-blocker' ); ?></p>
        <?php
    }
    
    public function render_api_section_description() {
        $api_base_url = get_rest_url( null, 'afb/v1/list' );
        $api_key      = get_option( $this->option_api_key );
        $api_enabled  = (bool) get_option( $this->option_enable_api, false );

        echo '<p>' . __( 'Configure API access below. If enabled, the blocklist can be fetched externally for platforms like Pardot or other CRMs.', 'advanced-form-blocker' ) . '</p>';

        if ( $api_enabled ) {
            if ( ! empty( $api_key ) ) {
                $full_api_url = add_query_arg( 'key', $api_key, $api_base_url );
                echo '<div style="margin-top: 10px; padding: 10px; border: 1px solid #ccd0d4; background-color: #f6f7f7;">';
                echo '<p style="margin-top:0;"><strong>' . __( 'Your API Endpoint (Ready to Copy):', 'advanced-form-blocker' ) . '</strong><br>';
                echo '<input type="text" value="' . esc_url( $full_api_url ) . '" readonly onfocus="this.select();" style="width: 100%; padding: 5px; margin-top:5px;" class="code"></p>';
                echo '<p class="description" style="margin-bottom:0;">' . __( 'This URL includes your current API key. Keep it secure.', 'advanced-form-blocker' ) . '</p>';
                echo '</div>';
            } else {
                echo '<p style="margin-top:10px;"><strong>' . __( 'API is enabled, but no API Key is set.', 'advanced-form-blocker' ) . '</strong><br>';
                echo __( 'Please regenerate an API key (managed separately below) for the endpoint to be functional.', 'advanced-form-blocker' ) . '<br>';
                echo __( 'Base URL:', 'advanced-form-blocker' ) . ' <code>' . esc_url( $api_base_url ) . '</code> (' . __( 'you would append', 'advanced-form-blocker' ) . ' <code>?key=YOUR_API_KEY</code>)</p>';
            }
        } else {
            echo '<p style="margin-top:10px;"><strong>' . __( 'API access is currently disabled.', 'advanced-form-blocker' ) . '</strong><br>';
            echo __( 'Enable it below to use the API endpoint.', 'advanced-form-blocker' ) . '<br>';
            echo __( 'Base URL (when enabled):', 'advanced-form-blocker' ) . ' <code>' . esc_url( $api_base_url ) . '</code></p>';
        }
    }

    public function render_enable_api_field() {
        $value = get_option( $this->option_enable_api, 0 );
        ?>
        <input type="checkbox" id="<?php echo esc_attr( $this->option_enable_api ); ?>" name="<?php echo esc_attr( $this->option_enable_api ); ?>" value="1" <?php checked( 1, $value ); ?>>
        <label for="<?php echo esc_attr( $this->option_enable_api ); ?>"><?php esc_html_e( 'Allow external access to the blocklist via REST API.', 'advanced-form-blocker' ); ?></label>
        <?php
    }

    public function render_api_key_management_field() {
        $api_key = get_option( $this->option_api_key, '' );
        ?>
        <input type="text" value="<?php echo esc_attr( $api_key ); ?>" readonly size="40" class="regular-text code" style="vertical-align: middle;" />
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline-block; margin-left: 10px;">
            <input type="hidden" name="action" value="afb_regenerate_api_key">
            <?php wp_nonce_field( 'afb_regenerate_api_key_nonce_action', 'afb_regenerate_api_key_nonce' ); ?>
            <?php submit_button( __( 'Regenerate Key', 'advanced-form-blocker' ), 'secondary', 'submit_regenerate_key', false, array('style' => 'vertical-align: middle;') ); ?>
        </form>
        <?php
    }

    public function render_blocked_email_msg_field() {
        $value = get_option( $this->option_blocked_email_msg, __( 'This email address is not allowed for submission.', 'advanced-form-blocker' ) );
        ?>
        <input type="text" name="<?php echo esc_attr( $this->option_blocked_email_msg ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Message shown when a specific email address is blocked.', 'advanced-form-blocker' ); ?></p>
        <?php
    }

    public function render_blocked_domain_msg_field() {
        $value = get_option( $this->option_blocked_domain_msg, __( 'Submissions from this email domain are not allowed.', 'advanced-form-blocker' ) );
        ?>
        <input type="text" name="<?php echo esc_attr( $this->option_blocked_domain_msg ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e( 'Message shown when an email from a blocked domain is submitted.', 'advanced-form-blocker' ); ?></p>
        <?php
    }

    public function handle_json_upload_action() {
        if ( ! isset( $_POST['afb_upload_json_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['afb_upload_json_nonce'] ), 'afb_upload_json_nonce_action' ) ) {
            wp_die( __( 'Security check failed for JSON upload.', 'advanced-form-blocker' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to upload files.', 'advanced-form-blocker' ) );
        }

        if ( isset( $_FILES['afb_blocklist_json_file'] ) && UPLOAD_ERR_NO_FILE !== $_FILES['afb_blocklist_json_file']['error'] ) {
            $result = $this->blocklist_manager->process_uploaded_blocklist( $_FILES['afb_blocklist_json_file'] );
            if ( is_wp_error( $result ) ) {
                add_settings_error( 'afb_notices', 'json_upload_error', $result->get_error_message(), 'error' );
            } else {
                add_settings_error( 'afb_notices', 'json_upload_success', __( 'Blocklist JSON file uploaded successfully.', 'advanced-form-blocker' ), 'updated' );
            }
        } else {
            add_settings_error( 'afb_notices', 'no_file_uploaded', __( 'No file was selected for upload.', 'advanced-form-blocker' ), 'warning' );
        }
        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'options-general.php?page=' . $this->page_slug . '&settings-updated=true' ) );
        exit;
    }

    public function handle_regenerate_api_key_action() {
        if ( ! isset( $_POST['afb_regenerate_api_key_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['afb_regenerate_api_key_nonce'] ), 'afb_regenerate_api_key_nonce_action' ) ) {
            wp_die( __( 'Security check failed for API key regeneration.', 'advanced-form-blocker' ) );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to regenerate API keys.', 'advanced-form-blocker' ) );
        }

        $new_key = wp_generate_password( 40, false, false );
        update_option( $this->option_api_key, $new_key );
        add_settings_error( 'afb_notices', 'api_key_regenerated', __( 'API Key regenerated successfully.', 'advanced-form-blocker' ), 'updated' );

        set_transient( 'settings_errors', get_settings_errors(), 30 );
        wp_safe_redirect( admin_url( 'options-general.php?page=' . $this->page_slug . '&settings-updated=true' ) );
        exit;
    }

    public function render_settings_page_html() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <?php settings_errors( 'afb_notices' ); ?>
            
            <hr style="margin-top: 20px; margin-bottom:0;">

            <h2><?php _e( 'Blocklist Management', 'advanced-form-blocker' ); ?></h2>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="afb_blocklist_json_file"><?php _e( 'Upload New Blocklist', 'advanced-form-blocker' ); ?></label></th>
                        <td>
                            <?php $this->render_json_upload_form_only(); ?>
                            <?php $this->render_json_upload_description(); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Current Blocklist Content', 'advanced-form-blocker' ); ?></th>
                        <td>
                            <?php $this->render_current_json_field(); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <hr style="margin-top: 20px; margin-bottom:0;">

            <h2><?php _e( 'API Key Management', 'advanced-form-blocker' ); ?></h2>
             <table class="form-table" role="presentation">
                <tbody>
                     <tr>
                        <th scope="row"><?php _e( 'API Key', 'advanced-form-blocker' ); ?></th>
                        <td>
                            <?php $this->render_api_key_management_field(); ?>
                             <p class="description" style="margin-top: 5px;"><?php _e( 'Use this key for external API access. Regenerating creates a new key.', 'advanced-form-blocker' ); ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <hr style="margin-top: 20px; margin-bottom:0;">

            <form method="post" action="options.php">
                <?php
                settings_fields( $this->options_group );
                // This will render sections and fields registered to $this->options_group:
                // - 'afb_api_options_section' (with API description and enable API field)
                // - 'afb_messages_options_section' (with message fields)
                do_settings_sections( $this->options_group );
                submit_button( __( 'Save API & Message Settings', 'advanced-form-blocker' ) );
                ?>
            </form>
        </div>
        <?php
    }
}