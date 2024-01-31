<?php

include_once("ajax_handler.php");
if ( ! function_exists( 'wp_handle_upload' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class SurveyJS_UploadFiles extends SurveyJS_AJAX_Handler {
    
    function __construct() {
        parent::__construct("SurveyJS_UploadFiles", false);  
    }
        
    function callback() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = [];

            foreach ($_FILES as $key=>$value) {
                $uploadedfile = $value;
                $upload_overrides = array( 'test_form' => false );
                $movefile = wp_handle_upload( $uploadedfile, $upload_overrides );

                if ( !$movefile || isset( $movefile['error'] ) ) {
                    wp_send_json( array('error' => $movefile['error']) );
                    return;
                }

                $result[$key] = $movefile['url'];
            }

            wp_send_json( $result );
        }
    }
}

?>