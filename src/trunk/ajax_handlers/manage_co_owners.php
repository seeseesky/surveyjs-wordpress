<?php
if (!defined('ABSPATH')) exit;

class SurveyJS_ManageCoOwners {

    function __construct() {
        add_action('wp_ajax_SurveyJS_AddCoOwner', array($this, 'addCoOwner'));
        add_action('wp_ajax_SurveyJS_RemoveCoOwner', array($this, 'removeCoOwner'));
        add_action('wp_ajax_SurveyJS_GetCoOwners', array($this, 'getCoOwners'));
    }

    /**
     * AJAX handler for adding a co-owner
     */
    public function addCoOwner() {
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
            return;
        }

        // Get and sanitize parameters
        $survey_id = isset($_POST['SurveyId']) ? sanitize_key($_POST['SurveyId']) : 0;
        $username = isset($_POST['Username']) ? sanitize_user($_POST['Username']) : '';

        if (empty($survey_id) || empty($username)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        $client = new SurveyJS_Client();
        
        // Check if user has access to this survey
        if (!$client->userHasAccessToSurvey($survey_id)) {
            wp_send_json_error('Access denied');
            return;
        }

        $result = $client->addCoOwner($survey_id, $username);
        
        if ($result === true) {
            $co_owners = $client->getCoOwners($survey_id);
            wp_send_json_success(array('co_owners' => $co_owners));
        } else {
            // If result is a string, it's an error message
            wp_send_json_error(is_string($result) ? $result : 'Failed to add co-owner');
        }
    }

    /**
     * AJAX handler for removing a co-owner
     */
    public function removeCoOwner() {
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
            return;
        }

        // Get and sanitize parameters
        $survey_id = isset($_POST['SurveyId']) ? sanitize_key($_POST['SurveyId']) : 0;
        $username = isset($_POST['Username']) ? sanitize_user($_POST['Username']) : '';

        if (empty($survey_id) || empty($username)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        $client = new SurveyJS_Client();
        
        // Check if user has access to this survey
        if (!$client->userHasAccessToSurvey($survey_id)) {
            wp_send_json_error('Access denied');
            return;
        }

        $result = $client->removeCoOwner($survey_id, $username);
        
        if ($result === true) {
            $co_owners = $client->getCoOwners($survey_id);
            wp_send_json_success(array('co_owners' => $co_owners));
        } else {
            wp_send_json_error('Failed to remove co-owner');
        }
    }

    /**
     * AJAX handler for getting co-owners
     */
    public function getCoOwners() {
        // Check if user has permission
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
            return;
        }

        // Get and sanitize parameters
        $survey_id = isset($_GET['SurveyId']) ? sanitize_key($_GET['SurveyId']) : 0;
        
        if (empty($survey_id)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        $client = new SurveyJS_Client();
        
        // Check if user has access to this survey
        if (!$client->userHasAccessToSurvey($survey_id)) {
            wp_send_json_error('Access denied');
            return;
        }

        $co_owners = $client->getCoOwners($survey_id);
        
        wp_send_json_success(array('co_owners' => $co_owners));
    }
}
?>
