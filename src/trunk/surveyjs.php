<?php
/*
Plugin Name: SurveyJS
Plugin URI: https://wordpress.org/plugins/surveyjs
Description: Easy to use, drag & drop Survey Builder with myriad options.
Version: 1.12.20
Author: Devsoft Baltic OÜ
Author URI: http://devsoftbaltic.com/
*/
?>
<?php
    function wps_install() {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        createMySurveysTable();
        createResultsTable();
    }

    function createMySurveysTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sjs_my_surveys';

        //var_dump( $table_name );

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            uuid varchar(36) DEFAULT NULL,
            name text NOT NULL,
            json LONGTEXT,
            theme LONGTEXT,
            user_id bigint(20) DEFAULT NULL,
            co_owners LONGTEXT DEFAULT NULL,
            UNIQUE KEY id (id)
        ) $charset_collate;";
        
        // Add index on uuid for faster lookups
        $sql_index = "CREATE INDEX idx_uuid ON $table_name (uuid);";
        dbDelta( $sql_index );

        dbDelta( $sql );
    }

    function createResultsTable() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sjs_results';

        //var_dump( $table_name );

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            surveyId mediumint(9) NOT NULL,
            json longtext,
            UNIQUE KEY id (id)
        ) $charset_collate;";

        dbDelta( $sql );
    }

    function wps_uninstall() {

    }
    register_activation_hook( __FILE__, 'wps_install');
    register_deactivation_hook( __FILE__, 'wps_uninstall');

    include("ajax_handlers/insert_survey.php");
    new SurveyJS_InsertSurveyHandler();

    include("ajax_handlers/save_survey.php");
    new SurveyJS_SaveSurvey();

    include("ajax_handlers/rename_survey.php");
    new SurveyJS_RenameSurvey();

    include("ajax_handlers/add_survey.php");
    new SurveyJS_AddSurvey();

    include("ajax_handlers/delete_survey.php");
    new SurveyJS_DeleteSurvey();

    include("ajax_handlers/clone_survey.php");
    new SurveyJS_CloneSurvey();

    include("ajax_handlers/get_survey_json.php");
    new SurveyJS_GetSurveyJson();

    include("ajax_handlers/save_result.php");
    new SurveyJS_SaveResult();

    include("ajax_handlers/delete_result.php");
    new SurveyJS_DeleteResult();

    include("ajax_handlers/upload_file.php");
    new SurveyJS_UploadFile();

    include("ajax_handlers/upload_files.php");
    new SurveyJS_UploadFiles();

    include("ajax_handlers/delete_file.php");
    new SurveyJS_DeleteFile();
    
    include("ajax_handlers/manage_co_owners.php");
    new SurveyJS_ManageCoOwners();

    include("initializer.php");
    new SurveyJS_SurveyJS();
?>
