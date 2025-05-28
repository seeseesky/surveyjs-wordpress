<?php

class SurveyJS_Client {
    private $accessKey;

    function __construct() {
    }

    public function getSurveys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        
        // If user_id column exists, filter by current user for Authors, show all for Editors and Admins
        if ($this->columnExists($table_name, 'user_id')) {
            // Check if user is an Editor or Admin (they can see all surveys)
            if (current_user_can('edit_others_posts')) { // Editors and Admins have this capability
                $query = "SELECT * FROM " . $table_name;
            } else {
                // Authors can only see their own surveys
                $query = $wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d OR user_id IS NULL", $current_user_id);
            }
        } else {
            // Fallback to showing all surveys if column doesn't exist yet
            $query = "SELECT * FROM " . $table_name;
        }
        
        return $wpdb->get_results( $query );
    }
    
    // Helper function to check if a column exists in a table
    private function columnExists($table_name, $column_name) {
        global $wpdb;
        $column = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $table_name,
            $column_name
        ));
        return !empty($column);
    }
    
    /**
     * Checks if the current user has access to a specific survey
     * 
     * @param int $survey_id The ID of the survey to check
     * @return bool True if the user has access, false otherwise
     */
    public function userHasAccessToSurvey($survey_id) {
        // Admins and editors can access all surveys
        if (current_user_can('edit_others_posts')) {
            return true;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $current_user_id = get_current_user_id();
        
        // Check if the user_id column exists
        if ($this->columnExists($table_name, 'user_id')) {
            // Query to check if this survey belongs to the current user
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_name} WHERE id = %d AND (user_id = %d OR user_id IS NULL)", 
                $survey_id, 
                $current_user_id
            );
            
            $count = $wpdb->get_var($query);
            return $count > 0;
        } else {
            // If the user_id column doesn't exist, only admins and editors can access
            return false;
        }
    }

}

?>