<?php

include_once("ajax_handler.php");
if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class SurveyJS_DeleteFile extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_DeleteFile", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'GET') {
            $filename = sanitize_file_name($_GET["name"]);

            if (str_contains($filename, "/uploads/surveyjs") && current_user_can( 'edit_posts' ) ) {
                $uploadpath = substr($filename, strpos($filename, "/surveyjs"));
                $upload_dir=wp_upload_dir();
                $fullname = $upload_dir['basedir'] . $uploadpath;
                wp_delete_file($fullname);
            }
        }
    }
}

?>