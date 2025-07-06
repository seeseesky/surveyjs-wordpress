<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyJS_MySurveys {

    function __construct() {
    }

    public static function render() {
        $client = new SurveyJS_Client();
        $editSurveyUri = add_query_arg(array('page' => 'surveyjs_editor'), admin_url('admin.php'));
        $addSurveyUri = add_query_arg(array('action' => 'SurveyJS_AddSurvey'), admin_url('admin-ajax.php'));
        $deleteSurveyUri = add_query_arg(array('action' => 'SurveyJS_DeleteSurvey'), admin_url('admin-ajax.php'));
        $cloneSurveyUri = add_query_arg(array('action' => 'SurveyJS_CloneSurvey'), admin_url('admin-ajax.php'));
        ?>
            <script>
                function addNewSurvey() {
                    var surveyName = prompt("Enter a name for your new survey:", "New Survey");
                    if (!surveyName) return; // User cancelled the prompt
                    
                    jQuery.ajax({
                        url:  "<?php echo esc_url($addSurveyUri) ?>",
                        type: "POST",
                        data: { Name: surveyName },
                        success: function (data) {
                            window.location = "<?php echo esc_url($editSurveyUri) ?>&id=" + data.Id + "&name=" + encodeURIComponent(surveyName);
                        }
                    });
                }
                function deleteSurvey(id) {
                    var res = confirm("This action CANNOT be undone! Are you ABSOLUTELY sure?");
                    if (!res) return;
                    jQuery.ajax({
                        url:  "<?php echo esc_url($deleteSurveyUri)  ?>",
                        type: "POST",
                        data: { Id: id },
                        success: function (data) {
                            window.location = "";
                        }
                    });
                }
                function cloneSurvey(id) {
                    jQuery.ajax({
                        url:  "<?php echo esc_url($cloneSurveyUri)  ?>",
                        type: "POST",
                        data: { SurveyParentId: id },
                        success: function (data) {
                            window.location = "";
                        }
                    });
                }
                
                function openCoOwnersModal(id, surveyName) {
                    // Set the survey ID and name in the modal
                    jQuery('#co-owners-survey-id').val(id);
                    jQuery('#co-owners-modal-title').text('Manage Co-owners for: ' + surveyName);
                    
                    // Clear existing co-owners list
                    jQuery('#co-owners-list').empty();
                    
                    // Get current co-owners
                    jQuery.ajax({
                        url: "<?php echo add_query_arg(array('action' => 'SurveyJS_GetCoOwners'), admin_url('admin-ajax.php')) ?>",
                        type: "GET",
                        data: { SurveyId: id },
                        success: function(response) {
                            if (response.success) {
                                var coOwners = response.data.co_owners;
                                updateCoOwnersList(coOwners);
                            }
                        }
                    });
                    
                    // Show the modal
                    jQuery('#co-owners-modal').show();
                }
                
                function closeCoOwnersModal() {
                    jQuery('#co-owners-modal').hide();
                }
                
                function addCoOwner() {
                    var surveyId = jQuery('#co-owners-survey-id').val();
                    var usernameInput = jQuery('#co-owner-username').val();
                    
                    if (!usernameInput) {
                        alert('Please enter at least one valid username');
                        return;
                    }
                    
                    // Split by comma and trim whitespace
                    var usernames = usernameInput.split(',').map(function(username) {
                        return username.trim();
                    }).filter(function(username) {
                        return username !== '';
                    });
                    
                    if (usernames.length === 0) {
                        alert('Please enter at least one valid username');
                        return;
                    }
                    
                    // Show loading indicator
                    jQuery('#co-owners-loading').show();
                    
                    // Process each username sequentially
                    processEmails(surveyId, usernames, 0);
                }
                
                function processEmails(surveyId, usernames, index) {
                    // If we've processed all usernames, hide loading and finish
                    if (index >= usernames.length) {
                        jQuery('#co-owner-username').val('');
                        jQuery('#co-owners-loading').hide();
                        return;
                    }
                    
                    var username = usernames[index];
                    
                    // Skip empty usernames
                    if (!username) {
                        processEmails(surveyId, usernames, index + 1);
                        return;
                    }
                    
                    jQuery.ajax({
                        url: "<?php echo add_query_arg(array('action' => 'SurveyJS_AddCoOwner'), admin_url('admin-ajax.php')) ?>",
                        type: "POST",
                        data: { 
                            SurveyId: surveyId,
                            Username: username 
                        },
                        success: function(response) {
                            if (response.success) {
                                // Update the co-owners list
                                updateCoOwnersList(response.data.co_owners);
                                
                                // Process next username
                                processEmails(surveyId, usernames, index + 1);
                            } else {
                                alert(response.data || 'Failed to add co-owner: ' + username);
                                jQuery('#co-owners-loading').hide();
                            }
                        },
                        error: function() {
                            alert('An error occurred while adding the co-owner: ' + username);
                            jQuery('#co-owners-loading').hide();
                        }
                    });
                }
                
                function removeCoOwner(username) {
                    var surveyId = jQuery('#co-owners-survey-id').val();
                    
                    if (confirm('Are you sure you want to remove ' + username + ' as a co-owner?')) {
                        jQuery.ajax({
                            url: "<?php echo add_query_arg(array('action' => 'SurveyJS_RemoveCoOwner'), admin_url('admin-ajax.php')) ?>",
                            type: "POST",
                            data: { 
                                SurveyId: surveyId,
                                Username: username 
                            },
                            success: function(response) {
                                if (response.success) {
                                    // Update the co-owners list
                                    updateCoOwnersList(response.data.co_owners);
                                } else {
                                    alert(response.data || 'Failed to remove co-owner');
                                }
                            },
                            error: function() {
                                alert('An error occurred while removing the co-owner');
                            }
                        });
                    }
                }
                
                function updateCoOwnersList(coOwners) {
                    var list = jQuery('#co-owners-list');
                    list.empty();
                    
                    if (!coOwners || coOwners.length === 0) {
                        list.html('<p>No co-owners yet</p>');
                        return;
                    }
                    
                    var html = '<ul>';
                    for (var i = 0; i < coOwners.length; i++) {
                        html += '<li>' + coOwners[i] + ' <button type="button" onclick="removeCoOwner(\'' + coOwners[i] + '\')" class="remove-co-owner">Remove</button></li>';
                    }
                    html += '</ul>';
                    
                    list.html(html);
                }
            </script>
            
            <style>
                /* Section styles */
                .survey-section-title {
                    margin-top: 25px;
                    margin-bottom: 15px;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #1ab394;
                    color: #333;
                    font-size: 18px;
                }
                
                .no-surveys-message {
                    padding: 15px;
                    background-color: #f9f9f9;
                    border-left: 4px solid #1ab394;
                    margin-bottom: 20px;
                }
                
                /* Modal styles */
                .co-owners-modal {
                    display: none;
                    position: fixed;
                    z-index: 1000;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    overflow: auto;
                    background-color: rgba(0,0,0,0.4);
                }
                
                .co-owners-modal-content {
                    background-color: #fefefe;
                    margin: 10% auto;
                    padding: 20px;
                    border: 1px solid #888;
                    width: 50%;
                    max-width: 500px;
                    border-radius: 5px;
                }
                
                .co-owners-modal-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 15px;
                }
                
                .co-owners-modal-header h3 {
                    margin: 0;
                }
                
                .co-owners-close {
                    color: #aaa;
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                }
                
                .co-owners-close:hover {
                    color: black;
                }
                
                .co-owners-form {
                    margin-bottom: 20px;
                }
                
                .co-owners-form input[type="email"] {
                    width: 70%;
                    padding: 8px;
                    margin-right: 10px;
                }
                
                .co-owners-form button {
                    padding: 8px 15px;
                    background-color: #1ab394;
                    color: white;
                    border: none;
                    border-radius: 3px;
                    cursor: pointer;
                }
                
                .co-owners-loading {
                    margin-top: 10px;
                    color: #666;
                    display: flex;
                    align-items: center;
                }
                
                .co-owners-loading .spinner {
                    display: inline-block;
                    width: 20px;
                    height: 20px;
                    margin-right: 10px;
                    background: url(<?php echo admin_url('images/spinner.gif'); ?>) no-repeat;
                    background-size: 20px 20px;
                    vertical-align: middle;
                }
                
                .co-owners-help {
                    margin-top: 5px;
                    font-size: 12px;
                    color: #666;
                    font-style: italic;
                }
                
                .co-owners-list {
                    max-height: 300px;
                    overflow-y: auto;
                    border-top: 1px solid #eee;
                    padding-top: 10px;
                }
                
                .co-owner-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 8px 0;
                    border-bottom: 1px solid #f5f5f5;
                }
                
                .co-owner-remove {
                    background-color: #f44336;
                    color: white;
                    border: none;
                    padding: 5px 10px;
                    border-radius: 3px;
                    cursor: pointer;
                }
                
                .no-co-owners {
                    padding: 15px 0;
                    text-align: center;
                    color: #666;
                }
            </style>
            <div class="wp-sjs-plugin">
                <div class="sv_main sv_frame sv_default_css">
                    <div class="sv_custom_header"></div>
                    <div class="sv_container">
                        <div class="sv_header">
                            <h3><?php _e( 'SurveyJS Wordpress plugin', 'sjs' ); ?></h2></h3>
                            <p>Below you can see the list of available surveys you can edit, run and see the results</p>
                        </div>
                        <div class="sv_body">
                            <div id="surveys-list" class="surveys-list">
                                <section>
                                    <button style='min-width: 100px; color: white;background-color: #1ab394;border: none;padding: 6px;border-radius: 5px;' onclick="addNewSurvey()">Add Survey</button>
                                </section>
                                
                                <?php
                                // Get surveys separated by owned and shared
                                $surveys = $client->getSurveysSeparated();
                                $owned_surveys = $surveys['owned'];
                                $shared_surveys = $surveys['shared'];
                                
                                /**
                                 * Creates a new WordPress page with the survey shortcode embedded
                                 * 
                                 * @param int $survey_id The ID of the survey
                                 * @param string $survey_name The name of the survey
                                 * @param string $uuid The UUID of the survey (optional)
                                 * @return int|WP_Error The ID of the created page or WP_Error on failure
                                 */
                                function create_survey_page($survey_id, $survey_name, $uuid = null) {
                                    // Create the page slug in the format 'survey-{uuid}' if UUID is provided, otherwise use 'survey-{id}'
                                    $page_slug = $uuid ? 'survey-' . $uuid : 'survey-' . $survey_id;
                                    
                                    // Check if page already exists
                                    $existing_page = get_page_by_path($page_slug);
                                    if ($existing_page) {
                                        return $existing_page->ID;
                                    }
                                    
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
                                
                                // Function to render a survey row
                                function render_survey_row($surveyDefinition, $editUrl, $resultsUrl) {
                                    global $wpdb;
                                    $page = null;
                                    $page_url = '#';
                                    
                                    // First check if the survey has a UUID
                                    if (isset($surveyDefinition->uuid) && !empty($surveyDefinition->uuid)) {
                                        // Try to find the page with the UUID-based slug
                                        $page_slug = 'survey-' . $surveyDefinition->uuid;
                                        $page = get_page_by_path($page_slug);
                                    }
                                    
                                    // If no page found with UUID or no UUID exists, try with ID (for backward compatibility)
                                    if (!$page) {
                                        $page_slug = 'survey-' . $surveyDefinition->id;
                                        $page = get_page_by_path($page_slug);
                                    }
                                    
                                    // Set the page URL if a page was found
                                    if ($page) {
                                        $page_url = get_permalink($page->ID);
                                    } else {
                                        // If no page exists, create one
                                        $page_id = create_survey_page($surveyDefinition->id, $surveyDefinition->name, $surveyDefinition->uuid);
                                        if ($page_id) {
                                            $page_url = get_permalink($page_id);
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo sanitize_text_field($surveyDefinition->name) ?></td>
                                        <td>
                                            <?php if ($page): ?>
                                                <a href="<?php echo esc_url($page_url) ?>" target="_blank"><?php echo esc_url($page_url) ?></a>
                                            <?php else: ?>
                                                <em>Page not found</em>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- <a class="sv_button_link" href="<?php echo sanitize_key($surveyDefinition->id) ?>">Run</a> -->
                                            <a class="sv_button_link" href="<?php echo esc_url($editUrl) ?>">Edit</a>
                                            <a class="sv_button_link" href="<?php echo esc_url($resultsUrl) ?>">Results</a>
                                            <span class="sv_button_link" onclick="cloneSurvey(<?php echo sanitize_key($surveyDefinition->id) ?>)">Clone</span>
                                            <span class="sv_button_link" onclick="openCoOwnersModal(<?php echo sanitize_key($surveyDefinition->id) ?>, '<?php echo esc_js($surveyDefinition->name) ?>')">Co-owners</span>
                                            <span class="sv_button_link sv_button_delete" onclick="deleteSurvey(<?php echo sanitize_key($surveyDefinition->id) ?>)">Delete</span>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                                
                                <!-- My Surveys Section -->
                                <h3 class="survey-section-title">My Surveys</h3>
                                <?php if (empty($owned_surveys)): ?>
                                    <p class="no-surveys-message">You don't have any surveys yet. Click "Add Survey" to create one.</p>
                                <?php else: ?>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <td>Name</td>
                                                <td>Page URL</td>
                                                <td>Actions</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($owned_surveys as $surveyDefinition) {
                                                $editUrl = add_query_arg(array('page' => 'surveyjs_editor', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                                                $resultsUrl = add_query_arg(array('page' => 'surveyjs_results', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                                                render_survey_row($surveyDefinition, $editUrl, $resultsUrl);
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                                
                                <!-- Shared With Me Section -->
                                <?php if (!empty($shared_surveys)): ?>
                                    <h3 class="survey-section-title">Shared With Me</h3>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <td>Name</td>
                                                <td>Page URL</td>
                                                <td>Actions</td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            foreach ($shared_surveys as $surveyDefinition) {
                                                $editUrl = add_query_arg(array('page' => 'surveyjs_editor', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                                                $resultsUrl = add_query_arg(array('page' => 'surveyjs_results', 'id' => $surveyDefinition->id, 'name' => $surveyDefinition->name), admin_url('admin.php'));
                                                render_survey_row($surveyDefinition, $editUrl, $resultsUrl);
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Co-owners Modal -->
            <div id="co-owners-modal" class="co-owners-modal">
                <div class="co-owners-modal-content">
                    <div class="co-owners-modal-header">
                        <h3 id="co-owners-modal-title">Manage Co-owners</h3>
                        <span class="co-owners-close" onclick="closeCoOwnersModal()">&times;</span>
                    </div>
                    <div class="co-owners-modal-body">
                        <p>Add WordPress usernames of users who should have access to this survey. Co-owners can view, edit, and see results of this survey.</p>
                        <div class="co-owners-form">
                            <input type="hidden" id="co-owners-survey-id" value="">
                            <input type="text" id="co-owner-username" placeholder="Enter usernames (comma separated)" required>
                            <button type="button" onclick="addCoOwner()">Add</button>
                            <div id="co-owners-loading" class="co-owners-loading" style="display: none;">
                                <span class="spinner"></span> Adding co-owners...
                            </div>
                        </div>
                        <p class="co-owners-help">You can enter multiple usernames separated by commas (e.g., john, mary, admin)</p>
                        <div id="co-owners-list" class="co-owners-list">
                            <!-- Co-owners will be listed here -->
                        </div>
                    </div>
                </div>
            </div>
        <?php
    }
}

?>