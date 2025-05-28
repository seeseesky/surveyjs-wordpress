<?php

include_once("ajax_handler.php");

class SurveyJS_CloneSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_CloneSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'edit_posts' )) {
            global $wpdb;
            $surveyId = sanitize_key($_POST['SurveyParentId']);
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $query = "SELECT * FROM " . $table_name . " WHERE id=" . $surveyId;
            $json =  $wpdb->get_row($query)->json;
            $name =  $wpdb->get_row($query)->name;
            $cloned_name = 'Copy of ' . $name;
            $current_user_id = get_current_user_id();
            
            $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => sanitize_text_field($cloned_name),
                 'json' => $json,
                 'user_id' => $current_user_id
                ) 
            );
            
            $new_survey_id = $wpdb->insert_id;
            
            // Create a new page with the survey shortcode for the cloned survey
            $this->create_survey_page($new_survey_id, $cloned_name);

            wp_send_json( array('Id' => $new_survey_id) );
        }
    }
    
    /**
     * Creates a new WordPress page with the survey shortcode embedded
     * 
     * @param int $survey_id The ID of the survey
     * @param string $survey_name The name of the survey
     * @return int|WP_Error The ID of the created page or WP_Error on failure
     */
    private function create_survey_page($survey_id, $survey_name) {
        // Create the page slug in the format 'survey-{id}'
        $page_slug = 'survey-' . $survey_id;
        
        // Create the page content with the shortcode
        $page_content = sprintf('[Survey id=%d name="%s"]', $survey_id, esc_attr($survey_name));
        
        // Set up the page data
        $page_data = array(
            'post_title'    => $survey_name,
            'post_content'  => $page_content,
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_name'     => $page_slug,
            'comment_status' => 'closed'
        );
        
        // Insert the page into the database
        $page_id = wp_insert_post($page_data);
        
        return $page_id;
    }
}

?>