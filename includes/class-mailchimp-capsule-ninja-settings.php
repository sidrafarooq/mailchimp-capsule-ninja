<?php

class MailChimp_Capsule_Ninja_Settings{

    private $options;

    public function setup(){

        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );

    }

    public function add_plugin_page()
    {
        // This page will be under "Settings"
        add_options_page(
            'MailChimp / Capsule Settings Admin', 
            'MailChimp / Capsule Settings', 
            'manage_options', 
            'mailchimp-capsule-settings-admin', 
            array( $this, 'create_admin_page' )
        );
    }    

    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('mailchimp_capsule_options_values');
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>MailChimp / Capsule API Settings</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'mailchimp_capsule_options_group' );   
                do_settings_sections( 'mailchimp-capsule-settings-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    } 

   /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'mailchimp_capsule_options_group', // Option group
            'mailchimp_capsule_options_values', // Option name
            array($this, 'sanitize') // Sanitize
        );

        add_settings_section(
            'mailchimp_section', // ID
            'Mail Chimp Settings', // Title
            array($this, 'print_mailchimp_section_info'), // Callback
            'mailchimp-capsule-settings-admin' // Page
        );  

        add_settings_field(
            'mailchimp_key', // ID
            'MailChimp API Key', // Title 
            array($this, 'mailchimp_api_key_callback'), // Callback
            'mailchimp-capsule-settings-admin', // Page
            'mailchimp_section' // Section           
        );      

        add_settings_field(
            'mailchimp_listid', 
            'MailChimp List ID', 
            array($this, 'mailchimp_list_id_callback'), 
            'mailchimp-capsule-settings-admin', 
            'mailchimp_section'
        );  

        add_settings_section(
            'capsule_section', // ID
            'Capsule Settings', // Title
            array($this, 'print_capsule_section_info'), // Callback
            'mailchimp-capsule-settings-admin' // Page
        );  

        add_settings_field(
            'capsule_user', 
            'Capsule User', 
            array($this, 'capsule_user_callback'), 
            'mailchimp-capsule-settings-admin', 
            'capsule_section'
        );      

        add_settings_field(
            'capsule_token', 
            'Capsule Token', 
            array($this, 'capsule_auth_token_callback'), 
            'mailchimp-capsule-settings-admin', 
            'capsule_section'
        );          

    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        
        $new_input = array();

        if(isset($input['mailchimp_key'])){
            $new_input['mailchimp_key'] = sanitize_text_field($input['mailchimp_key'] );
        }

        if(isset($input['mailchimp_listid'])){
            $new_input['mailchimp_listid'] = sanitize_text_field($input['mailchimp_listid']);
        }

        if(isset($input['capsule_user'])){
            $new_input['capsule_user'] = sanitize_text_field($input['capsule_user']);
        }        

        if(isset($input['capsule_token'])){
            $new_input['capsule_token'] = sanitize_text_field($input['capsule_token']);
        }        

        return $new_input;

    }

    /** 
     * Print the Section text
     */
    public function print_mailchimp_section_info()
    {
        print 'Enter your MailChimp API configuration settings below:';
    }


    /** 
     * Print the Section text
     */
    public function print_capsule_section_info()
    {
        print 'Enter your Capsule api configuration settings below:';
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function mailchimp_api_key_callback()
    {
        printf(
            '<input type="text" id="mailchimp_key" name="mailchimp_capsule_options_values[mailchimp_key]" value="%s" />',
            isset( $this->options['mailchimp_key'] ) ? esc_attr( $this->options['mailchimp_key']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function mailchimp_list_id_callback()
    {
        printf(
            '<input type="text" id="mailchimp_listid" name="mailchimp_capsule_options_values[mailchimp_listid]" value="%s" />',
            isset( $this->options['mailchimp_listid'] ) ? esc_attr( $this->options['mailchimp_listid']) : ''
        );
    }

    /** 
     * Get the settings option array and print one of its values
     */
    public function capsule_user_callback()
    {
        printf(
            '<input type="text" id="capsule_user" name="mailchimp_capsule_options_values[capsule_user]" value="%s" />',
            isset( $this->options['capsule_user'] ) ? esc_attr( $this->options['capsule_user']) : ''
        );
    }    


    /** 
     * Get the settings option array and print one of its values
     */
    public function capsule_auth_token_callback()
    {
        printf(
            '<input type="text" id="capsule_token" name="mailchimp_capsule_options_values[capsule_token]" value="%s" />',
            isset( $this->options['capsule_token'] ) ? esc_attr( $this->options['capsule_token']) : ''
        );
    }    

}