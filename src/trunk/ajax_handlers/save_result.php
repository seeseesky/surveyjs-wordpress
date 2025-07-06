<?php

include_once("ajax_handler.php");

class SurveyJS_SaveResult extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_SaveResult", true);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $Json = sanitize_text_field($_POST['Json']);
            $TableName = 'sjs_results';
            $SurveyId = null;
            
            // Only allow saving results using UUID for security
            if (isset($_POST['SurveyUuid'])) {
                $SurveyUuid = sanitize_text_field($_POST['SurveyUuid']);
                global $wpdb;
                $surveys_table = $wpdb->prefix . 'sjs_my_surveys';
                $query = $wpdb->prepare("SELECT id FROM {$surveys_table} WHERE uuid = %s", $SurveyUuid);
                $result = $wpdb->get_row($query);
                
                if ($result) {
                    $SurveyId = $result->id;
                } else {
                    wp_send_json_error(array('error' => 'Survey not found'));
                    return;
                }
            } else {
                // For backward compatibility, if SurveyId is provided, convert it to error
                if (isset($_POST['SurveyId'])) {
                    wp_send_json_error(array('error' => 'Access by ID is no longer supported. Please use UUID instead.'));
                } else {
                    wp_send_json_error(array('error' => 'Missing UUID identifier'));
                }
                return;
            }
            
            if (function_exists('surveyjs_save_result'))
            {
                do_action('wp_surveyjs_save_result', $SurveyId, $Json, $TableName);
            } else {
                global $wpdb;
                $table_name = $wpdb->prefix . $TableName;
    
                $wpdb->insert( 
                    $table_name, 
                    array( 
                     'surveyId' => $SurveyId,
                     'json' => $Json
                    ) 
                );
                
                wp_send_json_success(array('resultId' => $wpdb->insert_id));
            }
        }
    }
}

/**
 * Custom Save Survey Result function
 * file functions.php
 */
/*function surveyjs_save_result($SurveyId=null, $Json=null, $TableName=null) {
	do stuff
}
add_action( 'wp_surveyjs_save_result', 'surveyjs_save_result', 10, 3 );*/

?>