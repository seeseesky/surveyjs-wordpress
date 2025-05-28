<?php

include_once("ajax_handler.php");

class SurveyJS_DeleteSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_DeleteSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'edit_posts' )) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';
            $id = sanitize_key($_POST['Id']);

            // Delete the corresponding page before deleting the survey
            $this->delete_survey_page($id);

            $result = $wpdb->delete( 
                $table_name, 
                array( 
                 'id' => $id
                ) 
            );

            wp_send_json( array('IsSuccess' => $result, 'id' => intval($id)) );
        }
    }
    
    /**
     * Deletes the WordPress page associated with a survey
     * 
     * @param int $survey_id The ID of the survey
     * @return void
     */
    private function delete_survey_page($survey_id) {
        // Find the page with slug 'survey-{id}'
        $page_slug = 'survey-' . $survey_id;
        
        // Query for the page
        $pages = get_posts(array(
            'name' => $page_slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => 1
        ));
        
        // If the page exists, delete it
        if (!empty($pages)) {
            $page_id = $pages[0]->ID;
            wp_delete_post($page_id, true); // true = force delete (bypass trash)
        }
    }
}

?>