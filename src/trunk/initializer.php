<?php

include( "views/settings.php" );

if ( is_admin() ) {
    include( "views/mysurveys.php" );
    include( "views/editor.php" );
    include( "views/results.php" );
}

class SurveyJS_SurveyJS {
    public static $prefix = "sjs";
    public $plugin_version = "0.1.0";

    function __construct() {
        add_action('admin_menu', array( $this, 'wps_add_menu'));
        add_filter('media_buttons', array($this, 'wps_media_button'));
        add_shortcode('Survey', array($this, 'wps_process_shortcode'));


        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        add_action('init', array($this, 'register_sjs_gutenberg_block'));
    }

    public function register_sjs_gutenberg_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        $blockname = 'sjs/gutenberg-block';
        $blockscriptname = 'sjs-block-js';

        $client = new SurveyJS_Client();
        $surveys = $client->getSurveys();

        if ( is_admin() ) {
            wp_enqueue_script(
                $blockscriptname,
                plugins_url( 'block/block.js', __FILE__),
                array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
                true // Enqueue the script in the footer.
            );

            wp_localize_script(
                $blockscriptname,
                'surveys',
                $surveys
            );
        }

        register_block_type(
            $blockname,
            array(
                'attributes'      => array(
                    'shortcode'          => array(
                        'type' => 'string',
                        'default' => '',
                    ),
                    'surveyid'   => array(
                        'type' => 'string',
                        'default' => 'none',
                    ),
                ),
                'editor_script' => $blockscriptname,
                'render_callback' => array( self::class, 'render_callback_shortcode' ),
            )
        );
    }

    public static function render_callback_shortcode( $attributes ) {
		return do_shortcode( sanitize_text_field( $attributes['shortcode'] ) );
	}

    public function enqueue_admin_scripts() {
        if ( isset( $_GET["page"] ) &&
            ($_GET["page"] == "sjs-settings" ||
            $_GET['page'] == "sjs-main-menu" ||
            $_GET['page'] == "surveyjs_editor" ||
            $_GET['page'] == "surveyjs_results")
        ) {
            wp_enqueue_style('wps-adm-bootstrap-css', plugins_url('libs/bootstrap.min.css', __FILE__) );
            
            wp_enqueue_style('wps-adm-normalize-css', plugins_url('/normalize.css', __FILE__) );
            wp_enqueue_style('wps-adm-index-css', plugins_url('/index.css', __FILE__) );

            wp_enqueue_script('wps-adm--babel.min.js', plugins_url('libs/babel.min.js', __FILE__));

            wp_enqueue_script('wps-adm--survey.core.min.js', plugins_url('libs/library/survey.core.min.js', __FILE__));
            wp_enqueue_script('wps-adm--survey.i18n.min.js', plugins_url('libs/library/survey.i18n.min.js', __FILE__));
            wp_enqueue_script('wps-adm--themes-index.min.js', plugins_url('libs/library/themes/index.min.js', __FILE__));
            wp_enqueue_script('wps-adm--survey-react-ui.min.js', plugins_url('libs/library/survey-react-ui.min.js', __FILE__));

            wp_enqueue_script('wps-adm--survey-creator-core.min.js', plugins_url('libs/creator/survey-creator-core.min.js', __FILE__));
            wp_enqueue_script('wps-adm--survey-creator-core.i18n.min.js', plugins_url('libs/creator/survey-creator-core.i18n.min.js', __FILE__));
            wp_enqueue_script('wps-adm--survey-creator-react.min.js', plugins_url('libs/creator/survey-creator-react.min.js', __FILE__));

            wp_enqueue_style('wps-adm--defaultV2.min.css', plugins_url('libs/library/defaultV2.min.css', __FILE__));

            wp_enqueue_style('wps-adm--survey-creator-core.min.css', plugins_url('libs/creator/survey-creator-core.min.css', __FILE__));


            // wp_enqueue_script('wps-adm-knockout-js', plugins_url('libs/knockout.min.js', __FILE__));

            // wp_enqueue_style('wps-adm-survey-css', plugins_url('libs/modern.css', __FILE__) );
            // wp_enqueue_script('wps-adm-survey-ko-js', plugins_url('libs/survey.ko.min.js', __FILE__), array('wps-adm-knockout-js'));
            // wp_enqueue_style('wps-adm-surveyjseditor-css', plugins_url('libs/survey-creator.css', __FILE__) );
            // wp_enqueue_script('wps-adm-surveyjseditor-js', plugins_url('libs/survey-creator.min.js', __FILE__), array('wps-adm-survey-ko-js'));

            wp_enqueue_style('wps-adm-datatables-css', plugins_url('libs/datatables/dataTables.min.css', __FILE__) );
            wp_enqueue_script('wps-adm-sdatatables-js', plugins_url('libs/datatables/dataTables.min.js', __FILE__) );
            wp_enqueue_script('wps-adm-datatables-js-1', plugins_url('libs/datatables/dataTables.buttons.min.js', __FILE__) );
            wp_enqueue_style('wps-adm-datatables-css-1', plugins_url('libs/datatables/buttons.dataTables.min.css', __FILE__) );
            wp_enqueue_script('wps-adm-sdatatables-js-2', plugins_url('libs/datatables/buttons.flash.min.js', __FILE__) );
            wp_enqueue_script('wps-adm-sdatatables-js-3', plugins_url('libs/datatables/jszip.min.js', __FILE__) );
            // wp_enqueue_script('wps-adm-sdatatables-js-4', plugins_url('libs/datatables/pdfmake.min.js', __FILE__)  );
            wp_enqueue_script('wps-adm-sdatatables-js-5', plugins_url('libs/datatables/vfs_fonts.js', __FILE__)   );
            wp_enqueue_script('wps-adm-sdatatables-js-6', plugins_url('libs/datatables/buttons.html5.min.js', __FILE__)   );
            wp_enqueue_script('wps-adm-sdatatables-js-7', plugins_url('libs/datatables/buttons.print.min.js', __FILE__)   );

            wp_enqueue_style('thickbox');
            wp_enqueue_script('thickbox');
        }

        if ( isset( $_GET["page"] ) && $_GET['page'] == "surveyjs_results") {
            wp_enqueue_script('wps-adm-jquery-js', plugins_url('libs/library/survey.jquery.min.js', __FILE__)); // for "Show in Survey btn"
        }
    }

    public function enqueue_frontend_scripts() { 
        wp_enqueue_style('wps-survey-override-css', plugins_url('/survey.css', __FILE__) );
        wp_enqueue_script('wps-front-jquery-js', plugins_url('libs/library/survey.jquery.min.js', __FILE__), array('jquery'));
        wp_enqueue_script('wps-front--themes-index.min.js', plugins_url('libs/library/themes/index.min.js', __FILE__));
        wp_enqueue_style('wps-front--defaultV2.min.css', plugins_url('libs/library/defaultV2.min.css', __FILE__));        
    }
  
    function wps_add_menu() {
        add_menu_page( 'My surveys', 'SurveyJS', 'edit_posts', 'sjs-main-menu', array(
                        "SurveyJS_MySurveys", 'render'
                        ), plugins_url('images/logo_20х20.png', __FILE__));
        // add_submenu_page( 'sjs-main-menu', __( 'My Surveys', 'sjs-main-menu' ), __( 'My Surveys', 'sjs-main-menu' ), 'edit_posts', 'sjs-my-surveys', array(
        //                 __CLASS__, 'wps_mysurveys_page'
        //                 ));
        
        // Add the Settings page with the manage_options capability check
        // WordPress will automatically hide it from users who don't have this capability
        add_submenu_page( 'sjs-main-menu', __( 'Settings', 'sjs-main-menu' ), __( 'Settings', 'sjs-main-menu' ), 'manage_options', 'sjs-settings', array( 'SurveyJS_SettingsPage', 'sjs_render_settings' ) );
        
        add_submenu_page('', '', '', 'edit_posts', 'surveyjs_editor', array('SurveyJS_Editor', 'render'));
        add_submenu_page('', '', '', 'edit_posts', 'surveyjs_results', array('SurveyJS_Results', 'render'));
    }
  
    // function wps_mysurveys_page() {
    // }

    function wps_media_button() {
        $url = add_query_arg(array('action' => 'SurveyJS_InsertSurvey'), admin_url('admin-ajax.php'));
        ?>
        <a onclick="tb_click.call(this); return false;" href="<?php echo esc_url($url); ?>" class="button" title="<?php _e('Insert Survey', SurveyJS_SurveyJS::$prefix); ?>">
            <?php _e('Add Survey', SurveyJS_SurveyJS::$prefix); ?>
        </a>
        <?php
    }

    /**
     * Generates a version 4 UUID
     * 
     * @return string UUID v4
     */
    function generate_uuid_v4() {
        // Generate 16 random bytes
        $data = random_bytes(16);
        
        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        // Format the UUID
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    function wps_process_shortcode($attrs) {
        ob_start();
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sjs_my_surveys';
        $uuid = null;
        $id = null;
        
        // Check if we have a UUID or ID in the shortcode
        if (isset($attrs["uuid"])) {
            $uuid = esc_attr($attrs["uuid"]);
            // Get the ID from the database using the UUID for backward compatibility
            $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table_name} WHERE uuid = %s", $uuid));
        } elseif (isset($attrs["id"])) {
            // For backward compatibility with existing shortcodes
            $id = esc_attr($attrs["id"]);
            // Get the UUID from the database using the survey ID
            $uuid = $wpdb->get_var($wpdb->prepare("SELECT uuid FROM {$table_name} WHERE id = %d", $id));
            
            // If UUID is not found (old surveys without UUID), generate one and update the database
            if (!$uuid) {
                $uuid = $this->generate_uuid_v4();
                $wpdb->update(
                    $table_name,
                    array('uuid' => $uuid),
                    array('id' => $id),
                    array('%s'),
                    array('%d')
                );
            }
        } else {
            return "Error: Survey shortcode must include either 'uuid' or 'id' attribute.";
        }
        
        // If we couldn't find the survey, show an error
        if (!$id || !$uuid) {
            return "Error: Survey not found.";
        }
        
        $getSurveyJsonUri = add_query_arg(array('action' => 'SurveyJS_GetSurveyJson'), admin_url('admin-ajax.php'));
        $saveResultUri = add_query_arg(array('action' => 'SurveyJS_SaveResult'), admin_url('admin-ajax.php'));
        $uploadFileUri = add_query_arg(array('action' => 'SurveyJS_UploadFiles'), admin_url('admin-ajax.php'));
        $deleteFileUri = add_query_arg(array('action' => 'SurveyJS_DeleteFile'), admin_url('admin-ajax.php'));
        ?>
        <div class="wp-sjs-plugin" id="surveyContainer-<?php echo esc_attr($id) ?>">
            <div id="surveyElement-<?php echo $id ?>">Survey is loading...</div>
            <div id="surveyResult-<?php echo $id ?>"></div>
        </div>
        <script>
            jQuery.ajax({
                url:  "<?php echo esc_url($getSurveyJsonUri)  ?>",
                type: "POST",
                data: { Uuid: "<?php echo esc_js($uuid) ?>" },
                success: function (data) {
                    var json = {}
                    let theme;
                    if (data.json) {
                        json = JSON.parse(data.json.replace(/\\\"/g, "\"").replace(/\\\\/g, "\\").replace(/\\'/g, "'"));
                    }
                    if (data.theme) {
                        theme = JSON.parse(data.theme.replace(/\\\"/g, "\"").replace(/\\\\/g, "\\").replace(/\\'/g, "'"));
                    } else {
                        theme = null
                    }
                    jQuery(document).ready(()=>{
                        initSurvey<?php echo $id ?>(json, theme);
                    });
                }
            });

            function initSurvey<?php echo $id ?>(json, theme) {
                const survey<?php echo $id ?> = new Survey.Model(json);
                if (!!theme) {
                    survey<?php echo $id ?>.applyTheme(theme);
                }
                window.survey<?php echo $id ?> = survey<?php echo $id ?>;               
                survey<?php echo $id ?>
                    .onComplete
                    .add(function (sender, options) {
                        options.showSaveInProgress();
                        jQuery.ajax({
                            url:  "<?php echo esc_url($saveResultUri) ?>",
                            type: "POST",
                            data: { SurveyUuid: '<?php echo esc_js($uuid) ?>', Json : JSON.stringify(sender.data) },
                            success: function (data) {options.showSaveSuccess();},
                            error: function (xhr) {options.showSaveError(xhr.responseText);}
                        });
                        //document
                        //    .querySelector("#surveyResult-<?php echo $id ?>")
                        //    .innerHTML = "result: " + JSON.stringify(sender.data);
                    });

                survey<?php echo $id ?>.onUploadFiles.add((_, options) => {
                    const formData = new FormData();
                    options.files.forEach((file) => {
                        formData.append(file.name, file);
                    });

                    fetch("<?php echo esc_url($uploadFileUri) ?>", {
                        method: "POST",
                        body: formData
                    })
                        .then((response) => response.json())
                        .then((data) => {
                            options.callback(
                                options.files.map((file) => {
                                    return {
                                        file: file,
                                        content: data[file.name]
                                    };
                                })
                            );
                        })
                        .catch((error) => {
                            console.error("Error: ", error);
                            options.callback([], [ 'An error occurred during file upload.' ]);
                        });
                });

                function deleteFile(fileURL, options) {
                    try {
                        const apiUrl = `<?php echo esc_url($deleteFileUri) ?>&name=${fileURL}`;
                        fetch(apiUrl);
                    } catch (error) {
                        options.callback("error");
                    }
                }

                survey<?php echo $id ?>.onClearFiles.add((_, options) => {
                    if (!options.value || options.value.length === 0) {
                        options.callback("success");
                        return;
                    }

                    if (!options.fileName && !!options.value) {
                        for (const item of options.value) {
                            deleteFile(item.content, options);
                        }
                    } else {
                        const fileToRemove = options.value.find(
                            (item) => item.name === options.fileName
                        );
                        if (fileToRemove) {
                            deleteFile(fileToRemove.content, options);
                        } else {
                            console.error(`File with name ${options.fileName} is not found`);
                        }
                    }
                    options.callback("success");
                });

                jQuery("#surveyElement-<?php echo $id ?>").Survey({model: survey<?php echo $id ?>/*, css: customCss*/});
            }
        </script>        
        <?php
                        
        return ob_get_clean();
    }
  
  }
  
?>
