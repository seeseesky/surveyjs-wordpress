<?php

class SurveyJS_Client {
    private $accessKey;

    function __construct() {
    }

    /**
     * Get surveys separated by owned and shared
     * 
     * @return array Array with 'owned' and 'shared' keys containing respective surveys
     */
    public function getSurveysSeparated() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $current_username = $current_user->user_login;
        
        $result = array(
            'owned' => array(),
            'shared' => array()
        );
        
        // If user_id column exists, filter by current user for Authors, show all for Editors and Admins
        if ($this->columnExists($table_name, 'user_id')) {
            // Check if user is an Editor or Admin (they can see all surveys)
            if (current_user_can('edit_others_posts')) { // Editors and Admins have this capability
                // For admins and editors, all surveys are considered 'owned'
                $result['owned'] = $wpdb->get_results("SELECT * FROM " . $table_name);
            } else {
                // Get surveys where user is the owner
                $result['owned'] = $wpdb->get_results(
                    $wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d OR user_id IS NULL", $current_user_id)
                );
                
                // Get surveys where user is a co-owner
                if ($this->columnExists($table_name, 'co_owners')) {
                    // Get all surveys that have co-owners
                    $potential_shared_surveys = $wpdb->get_results(
                        "SELECT * FROM {$table_name} WHERE co_owners IS NOT NULL AND co_owners != ''"
                    );
                    
                    // Filter surveys where current user is a co-owner
                    foreach ($potential_shared_surveys as $survey) {
                        $co_owners = json_decode($survey->co_owners, true);
                        if (is_array($co_owners) && in_array($current_username, $co_owners)) {
                            // Make sure this survey is not already in the owned list
                            $found = false;
                            foreach ($result['owned'] as $owned_survey) {
                                if ($owned_survey->id == $survey->id) {
                                    $found = true;
                                    break;
                                }
                            }
                            
                            if (!$found) {
                                $result['shared'][] = $survey;
                            }
                        }
                    }
                }
            }
        } else {
            // Fallback to showing all surveys if column doesn't exist yet
            $result['owned'] = $wpdb->get_results("SELECT * FROM " . $table_name);
        }
        
        return $result;
    }
    
    public function getSurveys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $current_user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        $current_username = $current_user->user_login;
        
        // If user_id column exists, filter by current user for Authors, show all for Editors and Admins
        if ($this->columnExists($table_name, 'user_id')) {
            // Check if user is an Editor or Admin (they can see all surveys)
            if (current_user_can('edit_others_posts')) { // Editors and Admins have this capability
                $query = "SELECT * FROM " . $table_name;
            } else {
                // Authors can see their own surveys and surveys where they are co-owners
                $surveys = array();
                
                // First get surveys where user is the owner
                $owned_surveys = $wpdb->get_results(
                    $wpdb->prepare("SELECT * FROM {$table_name} WHERE user_id = %d OR user_id IS NULL", $current_user_id)
                );
                
                if (!empty($owned_surveys)) {
                    $surveys = array_merge($surveys, $owned_surveys);
                }
                
                // Then get surveys where user is a co-owner
                if ($this->columnExists($table_name, 'co_owners')) {
                    // Get all surveys that have co-owners
                    $potential_shared_surveys = $wpdb->get_results(
                        "SELECT * FROM {$table_name} WHERE co_owners IS NOT NULL AND co_owners != ''"
                    );
                    
                    // Filter surveys where current user is a co-owner
                    foreach ($potential_shared_surveys as $survey) {
                        $co_owners = json_decode($survey->co_owners, true);
                        if (is_array($co_owners) && in_array($current_username, $co_owners)) {
                            // Check if this survey is already in the list (avoid duplicates)
                            $found = false;
                            foreach ($surveys as $existing_survey) {
                                if ($existing_survey->id == $survey->id) {
                                    $found = true;
                                    break;
                                }
                            }
                            
                            if (!$found) {
                                $surveys[] = $survey;
                            }
                        }
                    }
                }
                
                return $surveys;
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
        $current_user = wp_get_current_user();
        $current_username = $current_user->user_login;
        
        // Check if the user_id column exists
        if ($this->columnExists($table_name, 'user_id')) {
            // First check if user is the owner
            $query = $wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE id = %d", 
                $survey_id
            );
            
            $survey = $wpdb->get_row($query);
            
            if (!$survey) {
                return false;
            }
            
            // Check if user is the owner
            if ($survey->user_id == $current_user_id || $survey->user_id === null) {
                return true;
            }
            
            // Check if user is a co-owner
            if ($this->columnExists($table_name, 'co_owners') && !empty($survey->co_owners)) {
                $co_owners = json_decode($survey->co_owners, true);
                if (is_array($co_owners) && in_array($current_username, $co_owners)) {
                    return true;
                }
            }
            
            return false;
        } else {
            // If the user_id column doesn't exist, only admins and editors can access
            return false;
        }
    }
    
    /**
     * Add a co-owner to a survey
     * 
     * @param int $survey_id The ID of the survey
     * @param string $username The username of the co-owner to add
     * @return bool|string True if successful, error message if failed
     */
    public function addCoOwner($survey_id, $username) {
        // Validate username (check if user exists)
        $user = get_user_by('login', $username);
        if (!$user) {
            return 'User not found: ' . $username;
        }
        
        // Check if current user has access to the survey
        if (!$this->userHasAccessToSurvey($survey_id)) {
            return 'Access denied';
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        
        // Get current co-owners
        $query = $wpdb->prepare(
            "SELECT co_owners FROM {$table_name} WHERE id = %d",
            $survey_id
        );
        
        $co_owners_json = $wpdb->get_var($query);
        $co_owners = !empty($co_owners_json) ? json_decode($co_owners_json, true) : array();
        
        // Add new co-owner if not already in the list
        if (!is_array($co_owners)) {
            $co_owners = array();
        }
        
        if (!in_array($username, $co_owners)) {
            $co_owners[] = $username;
            
            // Update the co-owners in the database
            $result = $wpdb->update(
                $table_name,
                array('co_owners' => json_encode($co_owners)),
                array('id' => $survey_id),
                array('%s'),
                array('%d')
            );
            
            return $result !== false;
        }
        
        return true; // Already a co-owner
    }
    
    /**
     * Remove a co-owner from a survey
     * 
     * @param int $survey_id The ID of the survey
     * @param string $username The username of the co-owner to remove
     * @return bool True if successful, false otherwise
     */
    public function removeCoOwner($survey_id, $username) {
        // Check if current user has access to the survey
        if (!$this->userHasAccessToSurvey($survey_id)) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        
        // Get current co-owners
        $query = $wpdb->prepare(
            "SELECT co_owners FROM {$table_name} WHERE id = %d",
            $survey_id
        );
        
        $co_owners_json = $wpdb->get_var($query);
        $co_owners = !empty($co_owners_json) ? json_decode($co_owners_json, true) : array();
        
        // Remove co-owner if in the list
        if (is_array($co_owners) && in_array($username, $co_owners)) {
            $co_owners = array_diff($co_owners, array($username));
            
            // Update the co-owners in the database
            $result = $wpdb->update(
                $table_name,
                array('co_owners' => json_encode(array_values($co_owners))),
                array('id' => $survey_id),
                array('%s'),
                array('%d')
            );
            
            return $result !== false;
        }
        
        return true; // Not a co-owner
    }
    
    /**
     * Get co-owners for a survey
     * 
     * @param int $survey_id The ID of the survey
     * @return array Array of co-owner emails
     */
    public function getCoOwners($survey_id) {
        // Check if current user has access to the survey
        if (!$this->userHasAccessToSurvey($survey_id)) {
            return array();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        
        // Get current co-owners
        $query = $wpdb->prepare(
            "SELECT co_owners FROM {$table_name} WHERE id = %d",
            $survey_id
        );
        
        $co_owners_json = $wpdb->get_var($query);
        $co_owners = !empty($co_owners_json) ? json_decode($co_owners_json, true) : array();
        
        return is_array($co_owners) ? $co_owners : array();
    }

}

?>