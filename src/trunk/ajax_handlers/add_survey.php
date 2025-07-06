<?php

include_once("ajax_handler.php");

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

class SurveyJS_AddSurvey extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_AddSurvey", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST' && current_user_can( 'edit_posts' )) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'sjs_my_surveys';

            $current_user_id = get_current_user_id();
            $survey_name = sanitize_text_field($_POST['Name']);
            $uuid = generate_uuid_v4();
            
            $wpdb->insert( 
                $table_name, 
                array( 
                 'name' => $survey_name,
                 'user_id' => $current_user_id,
                 'uuid' => $uuid
                ) 
            );
            
            $survey_id = $wpdb->insert_id;
            
            // Create a new page with the survey shortcode
            $this->create_survey_page($survey_id, $survey_name, $uuid);

            wp_send_json( array('Id' => $survey_id, 'Uuid' => $uuid) );
        }
    }
    
    /**
     * Creates a new WordPress page with the survey shortcode embedded
     * 
     * @param int $survey_id The ID of the survey
     * @param string $survey_name The name of the survey
     * @param string $uuid The UUID of the survey (optional)
     * @return int|WP_Error The ID of the created page or WP_Error on failure
     */
    private function create_survey_page($survey_id, $survey_name, $uuid = null) {
        // Create the page slug in the format 'survey-{uuid}' if UUID is provided, otherwise use 'survey-{id}'
        $page_slug = $uuid ? 'survey-' . $uuid : 'survey-' . $survey_id;
        
        // Create the page content with the shortcode
        $page_content = sprintf('[Survey uuid="%s" name="%s"]', $uuid, esc_attr($survey_name));
        
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