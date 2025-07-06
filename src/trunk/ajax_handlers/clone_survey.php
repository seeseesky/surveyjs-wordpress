<?php

include_once("ajax_handler.php");

// Include UUID generation function if not already defined
if (!function_exists('generate_uuid_v4')) {
    /**
     * Generates a UUID v4
     * 
     * @return string UUID v4 string
     */
    function generate_uuid_v4() {
        // Generate 16 bytes (128 bits) of random data
        $data = random_bytes(16);
        
        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

class SurveyJS_CloneSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_CloneSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'edit_posts' )) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';
            $survey = null;
            
            // Get survey either by ID or UUID
            if (isset($_POST['SurveyParentId'])) {
                $surveyId = sanitize_key($_POST['SurveyParentId']);
                $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $surveyId);
                $survey = $wpdb->get_row($query);
            } elseif (isset($_POST['SurveyParentUuid'])) {
                $uuid = sanitize_text_field($_POST['SurveyParentUuid']);
                $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE uuid = %s", $uuid);
                $survey = $wpdb->get_row($query);
            } else {
                wp_send_json_error(array('error' => 'Missing survey identifier'));
                return;
            }
            
            if (!$survey) {
                wp_send_json_error(array('error' => 'Survey not found'));
                return;
            }
            
            $json = $survey->json;
            $name = $survey->name;
            $cloned_name = 'Copy of ' . $name;
            $current_user_id = get_current_user_id();
            $new_uuid = generate_uuid_v4();
            
            $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => sanitize_text_field($cloned_name),
                 'json' => $json,
                 'user_id' => $current_user_id,
                 'uuid' => $new_uuid
                ) 
            );
            
            $new_survey_id = $wpdb->insert_id;
            
            // Create a new page with the survey shortcode for the cloned survey
            $this->create_survey_page($new_survey_id, $cloned_name);

            wp_send_json( array('Id' => $new_survey_id, 'Uuid' => $new_uuid) );
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