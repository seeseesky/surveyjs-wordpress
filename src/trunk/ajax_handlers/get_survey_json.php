<?php

include_once("ajax_handler.php");

class SurveyJS_GetSurveyJson extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_GetSurveyJson", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';
            
            // Only allow retrieving surveys by UUID for security
            if (isset($_POST['Uuid'])) {
                $uuid = sanitize_text_field($_POST['Uuid']);
                $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE uuid = %s", $uuid);
            } else {
                // For backward compatibility, if Id is provided, convert it to error
                if (isset($_POST['Id'])) {
                    wp_send_json_error(array('error' => 'Access by ID is no longer supported. Please use UUID instead.'));
                } else {
                    wp_send_json_error(array('error' => 'Missing UUID identifier'));
                }
                return;
            }
            
            $survey = $wpdb->get_row($query);
            
            if (!$survey) {
                wp_send_json_error(array('error' => 'Survey not found'));
                return;
            }
            
            $json = $survey->json;
            $theme = $survey->theme;

            wp_send_json( array(
                'id' => $survey->id,
                'uuid' => $survey->uuid,
                'json' => $json, 
                'theme' => $theme
            ) );
        }
    }
}

?>