<?php
// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GF_Domain_Blocker_Admin {

    // Define a unique slug for our settings tab within Gravity Forms settings
    private $settings_tab_slug = 'gf_domain_blocker';
    // Define a unique internal page slug for Settings API registration within this tab
    private $settings_page_slug_internal = 'gf_domain_blocker_settings_page';

    // Define option names for the custom messages
    private $option_name_email_message = 'gf_domain_blocker_email_message';
    private $option_name_domain_message = 'gf_domain_blocker_domain_message';

    // Define option name for the Pardot enable setting
    private $option_name_enable_pardot = 'gf_domain_blocker_enable_pardot';


    public function run() {
        // Register settings sections and fields using the standard WordPress Settings API.
        add_action( 'admin_init', array( $this, 'settings_init' ) );

         // Enqueue admin CSS specifically for our settings tab
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );


        // Hook into the Gravity Forms settings menu filter to add our new tab
        add_filter( 'gform_settings_menu', array( $this, 'add_domain_blocker_settings_tab' ) );

        // Hook into the action triggered when our specific settings tab is viewed.
        add_action( 'gform_settings_' . $this->settings_tab_slug, array( $this, 'display_domain_blocker_settings_content' ) );

        // Handle file upload for the JSON.
        add_action( 'admin_post_gf_domain_blocker_upload_json', array( $this, 'handle_json_upload' ) );
    }

    /**
     * Get the settings tab slug.
     * Public getter to allow access from outside the class (e.g., plugin action links).
     *
     * @return string The settings tab slug.
     */
    public function get_settings_tab_slug() {
        return $this->settings_tab_slug;
    }


    // Register settings sections and fields that will be displayed within our GF settings tab
    public function settings_init() {
        // Register a setting group. Needed for settings_fields() to generate nonces and for do_settings_sections()
        // to know which fields and sections belong together.
        // The actual option name ('gf_domain_blocker_list') registered here won't be used for saving with our current upload-based approach, but registration is needed for the Settings API structure.
        register_setting(
            'gf_domain_blocker_group', // Option group name (arbitrary, but unique)
            'gf_domain_blocker_list',  // Option name (not directly used for saving the list file)
            array( $this, 'sanitize_blocked_list' ) // Sanitize callback for the group (will prevent JSON saving from options.php)
        );

        // Register settings (options) for the customizable messages
        register_setting(
             'gf_domain_blocker_group', // Belong to the same option group
             $this->option_name_email_message, // New unique option name for the blocked email message
             'sanitize_text_field' // Use WordPress's built-in sanitization for text fields
        );
         register_setting(
             'gf_domain_blocker_group', // Belong to the same option group
             $this->option_name_domain_message, // New unique option name for the blocked domain message
             'sanitize_text_field' // Use WordPress's built-in sanitization for text fields
        );

        // Register the setting for enabling Pardot integration
         register_setting(
             'gf_domain_blocker_group', // Belong to the same option group
             $this->option_name_enable_pardot, // New unique option name for the Pardot enable setting
             'intval' // Sanitize as an integer (0 or 1 for checkbox)
         );


        // Add a settings section. This organizes fields within the tab.
        add_settings_section(
            'gf_domain_blocker_section', // Section ID (unique within this page slug)
            esc_html__( 'Blocked Domains and Email Addresses', 'gravity-forms-domain-blocker' ), // Section title
            array( $this, 'settings_section_callback' ), // Callback function for section description
            $this->settings_page_slug_internal // The *internal* page slug this section belongs to
        );

        // Add settings fields for the customizable messages
         add_settings_field(
             $this->option_name_email_message, // Field ID (can use the option name as a unique ID)
             esc_html__( 'Blocked Email Message', 'gravity-forms-domain-blocker' ), // Field title displayed next to the input
             array( $this, 'blocked_email_message_callback' ), // Callback function to display the input field HTML
             $this->settings_page_slug_internal, // The *internal* page slug this field belongs to
             'gf_domain_blocker_section' // The section ID this field belongs to
         );

          add_settings_field(
              $this->option_name_domain_message, // Field ID (can use the option name as a unique ID)
              esc_html__( 'Blocked Domain Message', 'gravity-forms-domain-blocker' ), // Field title displayed next to the input
              array( $this, 'blocked_domain_message_callback' ), // Callback function to display the input field HTML
              $this->settings_page_slug_internal, // The *internal* page slug this field belongs to
              'gf_domain_blocker_section' // The section ID this field belongs to
          );

        // Add settings field for the Pardot enable checkbox
         add_settings_field(
             $this->option_name_enable_pardot, // Field ID
             esc_html__( 'Enable Pardot Integration', 'gravity-forms-domain-blocker' ), // Field title
             array( $this, 'enable_pardot_callback' ), // Callback function to display the checkbox
             $this->settings_page_slug_internal, // Page slug
             'gf_domain_blocker_section' // Section ID
         );


        // Add the settings field for the read-only textarea displaying the JSON list
        add_settings_field(
            'gf_domain_blocker_textarea', // Field ID (unique within this section)
            esc_html__( 'Current Blocked List (JSON)', 'gravity-forms-domain-blocker' ), // Field title
            array( $this, 'blocked_list_textarea_callback' ), // Callback to display the field
            $this->settings_page_slug_internal, // The *internal* page slug this field belongs to
            'gf_domain_blocker_section' // The section ID this field belongs to
        );
    }

    /**
     * Adds a new tab to the main Gravity Forms settings page.
     * Hooked to 'gform_settings_menu'.
     *
     * @param array $tabs The existing array of settings tabs.
     * @return array The modified array of settings tabs.
     */
    public function add_domain_blocker_settings_tab( $tabs ) {
        // Add our custom tab to the end of the existing tabs array.
        $tabs[] = array(
            'name'  => $this->settings_tab_slug, // The unique slug for your tab (used in the action hook name)
            'label' => esc_html__( 'Domain Blocker', 'gravity-forms-domain-blocker' ), // The text displayed as the tab title
        );
        return $tabs;
    }

    /**
     * Displays the content of the "Domain Blocker" settings tab.
     * Hooked to 'gform_settings_{$this->settings_tab_slug}'.
     */
    public function display_domain_blocker_settings_content() {
        // Gravity Forms typically handles capability checks for its settings pages,
        // but it's good practice to re-check here as well.
        if ( ! current_user_can( 'manage_options' ) ) { // Make sure this is 'manage_options'
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'gravity-forms-domain-blocker' ) );
    }

        // Display any error or success messages that were set (e.g., by the file upload handler or Settings API saves)
         settings_errors( 'gf_domain_blocker_messages' ); // Use the same error group as in handle_json_upload and sanitize_blocked_list

        ?>
        <div class="wrap gf_settings gf_settings_domain_blocker">
            <p class="about-text"><?php esc_html_e( 'Configure settings for blocking form submissions based on email domains and addresses.', 'gravity-forms-domain-blocker' ); ?></p>

            <form method="post" action="options.php">
                 <?php
                // Output necessary hidden fields for the settings group and nonce
                settings_fields( 'gf_domain_blocker_group' );
                // Output all settings sections and fields that were registered for our internal page slug
                do_settings_sections( $this->settings_page_slug_internal );
                 ?>
                 <?php submit_button( esc_html__( 'Save Settings', 'gravity-forms-domain-blocker' ) ); ?>
            </form>

            <hr> <h2><?php esc_html_e( 'Upload New Blocklist JSON File', 'gravity-forms-domain-blocker' ) ; ?></h2>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="gf_domain_blocker_upload_json">
                <?php wp_nonce_field( 'gf_domain_blocker_upload_json_nonce', 'gf_domain_blocker_upload_nonce_field' ); ?>
                <p>
                    <label for="gf_domain_blocker_json_file"><?php esc_html_e( 'Choose JSON file:', 'gravity-forms-domain-blocker' ); ?></label>
                    <input type="file" name="gf_domain_blocker_json_file" id="gf_domain_blocker_json_file">
                </p>
                <?php submit_button( esc_html__( 'Upload File', 'gravity-forms-domain-blocker' ) ); ?>
            </form>
        </div>
        <?php
    }

    // Callback function to display the description at the start of the settings section
    public function settings_section_callback() {
        esc_html_e( 'Configure the messages displayed when a submission is blocked, enable/disable the Pardot integration, and view/update the current list of blocked domains and email addresses.', 'gravity-forms-domain-blocker' );
    }

    // Callback function to display the read-only textarea field
    public function blocked_list_textarea_callback() {
        $blocked_list = ( new GF_Domain_Blocker() )->get_blocked_list();
        $json_content = json_encode( $blocked_list, JSON_PRETTY_PRINT );
        ?>
        <label for="gf_domain_blocker_textarea"><strong><?php esc_html_e( 'Current Blocked List (JSON):', 'gravity-forms-domain-blocker' ); ?></strong></label>
        <br> <textarea id="gf_domain_blocker_textarea" name="gf_domain_blocker_list_content" rows="20" cols="80" class="large-text code" readonly><?php echo esc_textarea( $json_content ); ?></textarea>
        <p class="description"><?php esc_html_e( 'This list is read-only. To update the domains and emails, upload a new JSON file in the section below.', 'gravity-forms-domain-blocker' ); ?></p>
        <?php
    }

    // Callback function for the blocked email message field
    public function blocked_email_message_callback() {
         // Get the saved option value or use a default message
         $message = get_option( $this->option_name_email_message, esc_html__( 'Your email address is blocked.', 'gravity-forms-domain-blocker' ) );
        ?>
        <input type="text" name="<?php echo esc_attr( $this->option_name_email_message ); ?>" value="<?php echo esc_attr( $message ); ?>" class="regular-text">
        <p class="description"><?php esc_html_e( 'Enter the message displayed when a submitted email address is on the block list.', 'gravity-forms-domain-blocker' ); ?></p>
        <?php
    }

     // Callback function for the blocked domain message field
    public function blocked_domain_message_callback() {
         // Get the saved option value or use a default message
         $message = get_option( $this->option_name_domain_message, esc_html__( 'Emails from your domain are blocked.', 'gravity-forms-domain-blocker' ) );
        ?>
        <input type="text" name="<?php echo esc_attr( $this->option_name_domain_message ); ?>" value="<?php echo esc_attr( $message ); ?>" class="regular-text">
        <p class="description"><?php esc_html_e( 'Enter the message displayed when a submitted email address belongs to a domain on the block list.', 'gravity-forms-domain-blocker' ); ?></p>
        <?php
    }

    // Callback function for the enable Pardot checkbox field
    public function enable_pardot_callback() {
        // Get the current value of the option (checked or not)
        $enabled = get_option( $this->option_name_enable_pardot, false ); // Default to false (unchecked)
        ?>
        <input type="checkbox" name="<?php echo esc_attr( $this->option_name_enable_pardot ); ?>" value="1" <?php checked( 1, $enabled, true ); ?>>
        <label for="<?php echo esc_attr( $this->option_name_enable_pardot ); ?>"><?php esc_html_e( 'Check this box to enable the REST API endpoint for Pardot landing pages.', 'gravity-forms-domain-blocker' ); ?></label>
        <p class="description"><?php esc_html_e( 'Enabling this option activates a public REST API endpoint that Salesforce Markeing Cloud Account Engagement (MCAE, formerly known as Pardot) landing pages can use to fetch the blocked list for client-side validation.', 'gravity-forms-domain-blocker' ); ?></p>
        <?php
    }


    /**
     * Sanitizes the blocked list data.
     * This is the callback for register_setting of the *group*.
     * It's primarily used to prevent saving the read-only JSON content from the textarea.
     * The new message and Pardot options are saved automatically by register_setting with their own sanitization callbacks.
     *
     * @param mixed $input The input value (from $_POST). This will contain values for all fields in the group.
     * @return mixed The sanitized value, or the original if validation fails. For this specific callback,
     * since we don't save the JSON list via options.php, we just return the current list.
     */
    public function sanitize_blocked_list( $input ) {
        // This function is triggered when the form containing settings_fields is submitted (e.g., when saving other GF settings tabs).
        // The new message options (email_message, domain_message) and the Pardot option are handled and saved automatically by their own
        // register_setting calls with the specified sanitization callbacks (sanitize_text_field and intval).

        // We only need to prevent the (unchanged) read-only textarea content from trying to update the JSON file here.
        // Returning the current blocked list data effectively prevents any unintended save of the JSON list via this method.
        // Any validation errors related to the message/Pardot fields would be handled by their individual sanitize callbacks.

        // Return the current blocked list data (not saving it via this callback)
        return ( new GF_Domain_Blocker() )->get_blocked_list();
    }

    /**
     * Handles the uploaded JSON file.
     * Hooked to 'admin_post_gf_domain_blocker_upload_json'.
     */
    public function handle_json_upload() {
         // Verify the nonce for security
        if ( ! isset( $_POST['gf_domain_blocker_upload_nonce_field'] ) || ! wp_verify_nonce( sanitize_key( $_POST['gf_domain_blocker_upload_nonce_field'] ), 'gf_domain_blocker_upload_json_nonce' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'gravity-forms-domain-blocker' ) );
        }

        // Check user capabilities (should be manage_options for administrators)
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to upload files.', 'gravity-forms-domain-blocker' ) );
        }

        // Check if a file was actually uploaded without errors
        if ( ! isset( $_FILES['gf_domain_blocker_json_file'] ) || $_FILES['gf_domain_blocker_json_file']['error'] !== UPLOAD_ERR_OK ) {
            add_settings_error( 'gf_domain_blocker_messages', 'gf_domain_blocker_upload_error', esc_html__( 'Error uploading file.', 'gravity-forms-domain-blocker' ), 'error' );
            // Redirect back to our settings tab with an error flag
             wp_safe_redirect( admin_url( 'admin.php?page=gf_settings&subview=' . $this->settings_tab_slug . '&settings-updated=false' ) );
            exit;
        }

        $file = $_FILES['gf_domain_blocker_json_file'];
        // Use wp_check_filetype to safely get the file extension and mime type
        $file_info = wp_check_filetype( $file['name'], array( 'json' => 'application/json' ) );

        // Validate file type based on extension
        if ( $file_info['ext'] !== 'json' ) {
            add_settings_error( 'gf_domain_blocker_messages', 'gf_domain_blocker_upload_error', esc_html__( 'Invalid file type. Please upload a JSON file.', 'gravity-forms-domain-blocker' ), 'error' );
             wp_safe_redirect( admin_url( 'admin.php?page=gf_settings&subview=' . $this->settings_tab_slug . '&settings-updated=false' ) );
            exit;
        }

        // Read the content of the uploaded temporary file
        $json_content = file_get_contents( $file['tmp_name'] );
        // Decode the JSON content
        $decoded_list = json_decode( $json_content, true );

        // Validate the structure of the decoded JSON content
        // Ensure it's an array/object and contains 'domains' and 'emails' arrays
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded_list ) ) {
            // Ensure 'domains' key exists and is an array
            if ( ! isset( $decoded_list['domains'] ) || ! is_array( $decoded_list['domains'] ) ) {
                 $decoded_list['domains'] = array(); // Default to empty array if missing or wrong type
            }
            // Ensure 'emails' key exists and is an array
            if ( ! isset( $decoded_list['emails'] ) || ! is_array( $decoded_list['emails'] ) ) {
                $decoded_list['emails'] = array(); // Default to empty array if missing or wrong type
            }

            // Basic sanitization of array elements
            $sanitized_list = array(
                'domains' => array_map( 'sanitize_text_field', $decoded_list['domains'] ), // Sanitize domain strings
                'emails'  => array_map( 'sanitize_email', $decoded_list['emails'] ),     // Sanitize email strings
            );

            // Save the sanitized data to your plugin's JSON file
            $gf_domain_blocker = new GF_Domain_Blocker();
            if ( $gf_domain_blocker->update_blocked_list( $sanitized_list ) ) {
                // Success message
                add_settings_error( 'gf_domain_blocker_messages', 'gf_domain_blocker_upload_success', esc_html__( 'Blocked list updated successfully from uploaded JSON file.', 'gravity-forms-domain-blocker' ), 'success' );
            } else {
                 // Error saving file
                 add_settings_error( 'gf_domain_blocker_messages', 'gf_domain_blocker_upload_error', esc_html__( 'Error saving the blocked list file. Check file permissions.', 'gravity-forms-domain-blocker' ), 'error' );
            }

        } else {
            // JSON decoding or structure validation failed
            add_settings_error( 'gf_domain_blocker_messages', 'gf_domain_blocker_upload_error', esc_html__( 'Invalid JSON format or structure in the uploaded file. Ensure it contains "domains" and "emails" arrays at the top level.', 'gravity-forms-domain-blocker' ), 'error' );
        }

        // Redirect back to the Gravity Forms settings page, specifically our tab
        wp_safe_redirect( admin_url( 'admin.php?page=gf_settings&subview=' . $this->settings_tab_slug . '&settings-updated=true' ) );
        exit;
    }

    /**
     * Enqueues admin CSS specifically for the plugin's settings tab.
     * Hooked to 'admin_enqueue_scripts'.
     *
     * @param string $hook_suffix The current admin page hook suffix.
     */
    public function enqueue_admin_styles( $hook_suffix ) {
        // Check if we are on the Gravity Forms settings page AND our specific tab is active
        // The hook suffix for GF settings pages is typically 'forms_page_gf_settings'
        if ( 'forms_page_gf_settings' === $hook_suffix && isset( $_GET['subview'] ) && $_GET['subview'] === $this->settings_tab_slug ) {
            wp_enqueue_style(
                'gf_domain_blocker_admin_styles', // Unique handle for your stylesheet
                GF_DOMAIN_BLOCKER_URL . 'admin/css/admin-styles.css', // URL to your CSS file using the defined constant
                array(), // Dependencies (none needed here)
                '1.0' // Version number (increment this when you make CSS changes)
            );
        }
    }
}
