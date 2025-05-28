<?php

include_once("ajax_handler.php");

class SurveyJS_RenameSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_RenameSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'edit_posts' )) {
            $id = sanitize_key($_POST['Id']);
            $name = sanitize_text_field($_POST['Name']);
            if(!!$name) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'sjs_my_surveys';

                $result = $wpdb->update( 
                    $table_name, 
                    array( 
                        'name' => $name
                    ),
                    array( 
                        'id' => intval($id)
                    )
                );
                
                // Update the corresponding page title
                $this->update_survey_page_title($id, $name);
                
                wp_send_json( array('IsSuccess' => $result, 'name' => $name, 'id' => intval($id)) );
            }
        }
    }
    
    /**
     * Updates the title of the WordPress page associated with a survey
     * 
     * @param int $survey_id The ID of the survey
     * @param string $new_title The new title for the page
     * @return void
     */
    private function update_survey_page_title($survey_id, $new_title) {
        // Find the page with slug 'survey-{id}'
        $page_slug = 'survey-' . $survey_id;
        
        // Query for the page
        $pages = get_posts(array(
            'name' => $page_slug,
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => 1
        ));
        
        // If the page exists, update its title
        if (!empty($pages)) {
            $page = $pages[0];
            
            // Update the page title and content (to update the shortcode name attribute)
            $page_content = sprintf('[Survey id=%d name="%s"]', $survey_id, esc_attr($new_title));
            
            $updated_page = array(
                'ID' => $page->ID,
                'post_title' => $new_title,
                'post_content' => $page_content
            );
            
            // Update the page
            wp_update_post($updated_page);
        } else {
            // If the page doesn't exist, create it
            $this->create_survey_page($survey_id, $new_title);
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