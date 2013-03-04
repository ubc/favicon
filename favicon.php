<?php
/*
Plugin Name: UBC Favicon Plugin
Plugin URI: 
Description: Allows you to change the favicon on your site
Version: 1
Author: Amir Entezaralmahdi | Arts ISIT
Licence: GPLv2
Author URI: http://isit.arts.ubc.ca
*/

Class UBC_Favicon{


    function init(){
        // Initialize Theme options
       add_action( 'after_setup_theme', array( __CLASS__,'favicon_options_init') );    
       
       add_action( 'admin_init', array( __CLASS__,'favicon_options_setup') );
       
        // Load the Admin Options page
        add_action('admin_menu', array( __CLASS__,'favicon_menu_options') ); 
        
        add_action('admin_enqueue_scripts', array( __CLASS__,'favicon_options_enqueue_scripts') );
        
        add_action( 'admin_init', array( __CLASS__,'favicon_options_settings_init') );
        
        add_action("wp_head", array( __CLASS__,'display_favicon') );
        
        
       
    }
    function favicon_get_default_options() {
            $options = array(
                    'favicon' => '//cdn.ubc.ca/clf/7.0.2/img/favicon.ico'
            );
            return $options;
    }


    function favicon_options_init() {
         $favicon_options = get_option( 'theme_favicon_options' );

             // Are our options saved in the DB?
         if ( false === $favicon_options ) {
                      // If not, we'll save our default options
              $favicon_options = UBC_Favicon::favicon_get_default_options();
                      add_option( 'theme_favicon_options', $favicon_options );
         }

         // In other case we don't need to update the DB
    }


    function favicon_options_setup() {
            global $pagenow;
            if ('media-upload.php' == $pagenow || 'async-upload.php' == $pagenow) {
                    // Now we'll replace the 'Insert into Post Button inside Thickbox' 
                    add_filter( 'gettext', 'replace_thickbox_text' , 1, 2 );
            }
    }
    

    function replace_thickbox_text($translated_text, $text ) {	
            if ( 'Insert into Post' == $text ) {
                    $referer = strpos( wp_get_referer(), 'favicon-settings' );
                    if ( $referer != '' ) {
                            return __('I want this to be my favicon!', 'favicon' );
                    }
            }

            return $translated_text;
    }

    // Add "Favicon" link to the "Appearance" menu
    function favicon_menu_options() {
            //add_theme_page( $page_title, $menu_title, $capability, $menu_slug, $function);
         add_theme_page('Favicon', 'Favicon', 'edit_theme_options', 'favicon-settings', array( __CLASS__,'favicon_admin_options_page') );
    }


    function favicon_admin_options_page() {
            ?>
                    <!-- 'wrap','submit','icon32','button-primary' and 'button-secondary' are classes 
                    for a good WP Admin Panel viewing and are predefined by WP CSS -->



                    <div class="wrap">

                            <div id="icon-themes" class="icon32"><br /></div>

                            <h2><?php _e( 'Favicon', 'favicon' ); ?></h2>

                            <!-- If we have any error by submitting the form, they will appear here -->
                            <?php settings_errors( 'favicon-settings-errors' ); ?>

                            <form id="form-favicon-options" action="options.php" method="post" enctype="multipart/form-data">

                                    <?php
                                            settings_fields('theme_favicon_options');
                                            do_settings_sections('favicon');
                                    ?>

                                    <p class="submit">
                                            <input name="theme_favicon_options[submit]" id="submit_options_form" type="submit" class="button-primary" value="<?php esc_attr_e('Save Settings', 'favicon'); ?>" />
                                            <input name="theme_favicon_options[reset]" type="submit" class="button-secondary" value="<?php esc_attr_e('Reset Defaults', 'favicon'); ?>" />		
                                    </p>

                            </form>

                    </div>
            <?php
    }

    function favicon_options_validate( $input ) {
            $default_options = UBC_Favicon::favicon_get_default_options();
            $valid_input = $default_options;

            $favicon_options = get_option('theme_favicon_options');

            $submit = ! empty($input['submit']) ? true : false;
            $reset = ! empty($input['reset']) ? true : false;
            $delete_favicon = ! empty($input['delete_favicon']) ? true : false;

            if ( $submit ) {
                    if ( $favicon_options['favicon'] != $input['favicon']  && $favicon_options['favicon'] != '' )
                            UBC_Favicon::delete_image( $favicon_options['favicon'] );

                    $valid_input['favicon'] = $input['favicon'];
            }
            elseif ( $reset ) {
                    UBC_Favicon::delete_image( $favicon_options['favicon'] );
                    $valid_input['favicon'] = $default_options['favicon'];
            }
            elseif ( $delete_favicon ) {
                    UBC_Favicon::delete_image( $favicon_options['favicon'] );
                    $valid_input['favicon'] = '';
            }

            return $valid_input;
    }

    function delete_image( $image_url ) {
            global $wpdb;

            // We need to get the image's meta ID..
            $query = "SELECT ID FROM wp_posts where guid = '" . esc_url($image_url) . "' AND post_type = 'attachment'";  
            $results = $wpdb -> get_results($query);

            // And delete them (if more than one attachment is in the Library
            foreach ( $results as $row ) {
                    wp_delete_attachment( $row -> ID );
            }	
    }

    /********************* JAVASCRIPT ******************************/
    function favicon_options_enqueue_scripts() {
            wp_register_script( 'favicon-upload', plugins_url( '/js/favicon.js' , __FILE__ ), array('jquery','media-upload','thickbox') );	

            if ( 'appearance_page_favicon-settings' == get_current_screen() -> id ) {
                    wp_enqueue_script('jquery');

                    wp_enqueue_script('thickbox');
                    wp_enqueue_style('thickbox');

                    wp_enqueue_script('media-upload');
                    wp_enqueue_script('favicon-upload');

            }

    }
    


    function favicon_options_settings_init() {
            register_setting( 'theme_favicon_options', 'theme_favicon_options', array( __CLASS__,'favicon_options_validate') );

            // Add a form section for the Favicon
            add_settings_section('favicon_settings_header', __( 'Favicon Options', 'favicon' ), array( __CLASS__,'favicon_settings_header_text'), 'favicon');

            // Add Favicon uploader
            add_settings_field('favicon_setting_favicon',  __( 'Favicon', 'favicon' ), array( __CLASS__,'favicon_setting_favicon'), 'favicon', 'favicon_settings_header');

    }
    


    

    function display_favicon(){
            $favicon_options = get_option( 'theme_favicon_options' );
            $default_favicon_option = UBC_Favicon::favicon_get_default_options();

            if( isset($favicon_options) && !empty($favicon_options)):
                    echo '<link rel="shortcut icon" href="'.esc_url($favicon_options['favicon']).'" />';
            else:
                    echo '<link rel="shortcut icon" href="'.$default_favicon_option['favicon'].'" />';
            endif;
    }

    function favicon_settings_header_text() {
            ?>
                    <p><?php _e( '', 'favicon' ); ?></p>
            <?php
    }

    function favicon_setting_favicon() {
            $favicon_options = get_option( 'theme_favicon_options' );
            
            if(!isset($favicon_options['favicon'])){
                $favicon_options['favicon'] = '';
            }
            
            ?>
                    <img style="margin-bottom: -4px;" width="16" height="16" src="<?php echo esc_url( $favicon_options['favicon'] ); ?>" />
                    <input type="url" size="79" id="favicon_url" name="theme_favicon_options[favicon]" value="<?php echo esc_attr( $favicon_options['favicon'] ); ?>" />
                    <?php if ( isset($favicon_options['favicon']) ): ?>
                            <input id="delete_favicon_button" name="theme_favicon_options[delete_favicon]" type="submit" class="button" value="<?php _e( 'Delete Favicon', 'favicon' ); ?>" />
                    <?php endif; ?>
                    <br /><span class="description"><?php _e('Insert the URL to the favicon.', 'favicon' ); ?></span>
            <?php
    }


}
UBC_Favicon::init();