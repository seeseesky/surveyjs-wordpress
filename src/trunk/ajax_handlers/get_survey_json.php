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
            
            // Check if we're getting survey by ID or UUID
            if (isset($_POST['Id'])) {
                $surveyId = sanitize_key($_POST['Id']);
                $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $surveyId);
            } elseif (isset($_POST['Uuid'])) {
                $uuid = sanitize_text_field($_POST['Uuid']);
                $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE uuid = %s", $uuid);
            } else {
                wp_send_json_error(array('error' => 'Missing survey identifier'));
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