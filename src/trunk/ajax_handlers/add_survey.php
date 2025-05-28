<?php

include_once("ajax_handler.php");

class SurveyJS_AddSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_AddSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'administrator' )) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $current_user_id = get_current_user_id();
            $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => sanitize_text_field($_POST['Name']),
                 'user_id' => $current_user_id
                ) 
            );

            wp_send_json( array('Id' => $wpdb->insert_id) );
        }
    }
}

?>