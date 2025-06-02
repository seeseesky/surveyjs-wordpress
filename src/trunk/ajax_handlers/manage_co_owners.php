<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_ManageCoOwners {

    function __construct() {
        add_action('wp_ajax_SurveyJS_AddCoOwner', array($this, 'add_co_owner'));
        add_action('wp_ajax_SurveyJS_RemoveCoOwner', array($this, 'remove_co_owner'));
        add_action('wp_ajax_SurveyJS_GetCoOwners', array($this, 'get_co_owners'));
    }

    function add_co_owner() {
        // Check if user has permission to edit posts
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Access denied');
            return;
        }

        $survey_id = isset($_POST['SurveyId']) ? intval($_POST['SurveyId']) : 0;
        $email = isset($_POST['Email']) ? sanitize_email($_POST['Email']) : '';

        if (empty($survey_id) || empty($email)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        $client = new SurveyJS_Client();
        
        // Check if user has access to this survey
        if (!$client->userHasAccessToSurvey($survey_id)) {
            wp_send_json_error('Access denied');
            return;
        }

        $result = $client->addCoOwner($survey_id, $email);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Co-owner added successfully',
                'co_owners' => $client->getCoOwners($survey_id)
            ));
        } else {
            wp_send_json_error('Failed to add co-owner');
        }
    }

    function remove_co_owner() {
        // Check if user has permission to edit posts
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Access denied');
            return;
        }

        $survey_id = isset($_POST['SurveyId']) ? intval($_POST['SurveyId']) : 0;
        $email = isset($_POST['Email']) ? sanitize_email($_POST['Email']) : '';

        if (empty($survey_id) || empty($email)) {
            wp_send_json_error('Missing required parameters');
            return;
        }

        $client = new SurveyJS_Client();
        
        // Check if user has access to this survey
        if (!$client->userHasAccessToSurvey($survey_id)) {
            wp_send_json_error('Access denied');
            return;
        }

        $result = $client->removeCoOwner($survey_id, $email);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Co-owner removed successfully',
                'co_owners' => $client->getCoOwners($survey_id)
            ));
        } else {
            wp_send_json_error('Failed to remove co-owner');
        }
    }

    function get_co_owners() {
        // Check if user has permission to edit posts
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Access denied');
            return;
        }

        $survey_id = isset($_GET['SurveyId']) ? intval($_GET['SurveyId']) : 0;

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
        
        wp_send_json_success(array(
            'co_owners' => $co_owners
        ));
    }
}
?>
