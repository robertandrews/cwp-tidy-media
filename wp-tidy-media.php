<?php
/*
Plugin Name: Tidy Media Organizer
Plugin URI: https://example.com/
Description: A plugin to organize media by post type or taxonomy.
Version: 1.0
Author: Your Name
Author URI: https://example.com/
 */








/**
 * Database Setup.
 * 
 * Creates a new database table for storing Tidy Media Organizer plugin settings.
 *
 * @global object $wpdb The WordPress database object.
 *
 * @return void
 */
function tidy_media_organizer_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            setting_id mediumint(9) NOT NULL AUTO_INCREMENT,
            setting_name varchar(50) NOT NULL,
            setting_value varchar(50) NOT NULL,
            PRIMARY KEY (setting_id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'tidy_media_organizer_create_table');





/**
 * Admin Menus.
 * 
 * Registers the Tidy Media Organizer admin menu and sub-menu pages in the WordPress dashboard.
 *
 * @return void
 */
function tidy_media_organizer_admin_page()
{
    add_menu_page(
        'Tidy Media Organizer',
        'Tidy Media Organizer',
        'manage_options',
        'tidy-media-organizer',
        'tidy_media_organizer_main_page'
    );

    add_submenu_page(
        'tidy-media-organizer',
        'Options',
        'Options',
        'manage_options',
        'tidy-media-organizer-options',
        'tidy_media_organizer_options_page'
    );
}
add_action('admin_menu', 'tidy_media_organizer_admin_page');








/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */







/**
 * Admin Main Page.
 * 
 * Displays the main page content for the Tidy Media Organizer plugin in the WordPress dashboard.
 *
 * @return void
 */
function tidy_media_organizer_main_page()   { ?>
<div class="wrap">
    <h1>Tidy Media Organizer</h1>
</div>
<?php }






/**
 * Admin Options Page.
 * 
 * This function creates the options page for the Tidy Media Organizer plugin.
 * 
 * It checks if the user is authorized to access the options page and saves form data when the Save button is clicked.
 * 
 * It retrieves the current settings from the database and outputs the form HTML.
 * 
 * @since 1.0.0
 */
function tidy_media_organizer_options_page()
{
    // Check if the user is authorized to access the options page
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }

    // Save form data when Save button is clicked
    if (isset($_POST['tidy_media_organizer_save'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tidy_media_organizer';

        // Update or create settings
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
            $settings = array(
                array('setting_name' => 'organize_post_img_by_type', 'setting_value' => isset($_POST['organize_post_img_by_type']) ? 1 : 0),
                array('setting_name' => 'organize_post_img_by_taxonomy', 'setting_value' => isset($_POST['organize_post_img_by_taxonomy']) ? sanitize_text_field($_POST['organize_post_img_by_taxonomy']) : ''),
            );
            foreach ($settings as $setting) {
                $existing_row = $wpdb->get_row("SELECT * FROM $table_name WHERE setting_name = '{$setting['setting_name']}'");
                if ($existing_row) {
                    $wpdb->query("UPDATE $table_name SET setting_value = '{$setting['setting_value']}' WHERE setting_name = '{$setting['setting_name']}'");
                } else {
                    $wpdb->insert($table_name, $setting);
                }
            }
            echo '<div class="updated"><p><strong>Settings saved.</strong></p></div>';
        } else {
            // Show an error message
            echo '<div class="notice notice-error"><p><strong>Save failed</strong>: <code>' . $table_name . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
        }

    }

    // Retrieve current settings from database
    global $wpdb;
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $settings = $wpdb->get_results("SELECT * FROM $table_name");
        $settings_arr = array();
        foreach ($settings as $setting) {
            $settings_arr[$setting->setting_name] = $setting->setting_value;
        }
        $organize_post_img_by_type = isset($settings_arr['organize_post_img_by_type']) ? $settings_arr['organize_post_img_by_type'] : 0;
        $organize_post_img_by_taxonomy = isset($settings_arr['organize_post_img_by_taxonomy']) ? $settings_arr['organize_post_img_by_taxonomy'] : '';
    } else {
        // Show an error message
        echo '<div class="notice notice-error"><p><strong>Plugin issue</strong>: <code>' . $table_name . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
    }

    // Output form HTML
    ?>
<div class="wrap">
    <h1>Tidy Media Organizer</h1>

    <?php
        //Get the active tab from the $_GET param
        $default_tab = null;
        $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
        ?>

    <!--
        <nav class="nav-tab-wrapper">
        <a href="?page=tidy-media-organizer-options" class="nav-tab <?php if ($tab === null): ?>nav-tab-active<?php endif;?>">Options</a>
        <a href="?page=my-plugin&tab=tools" class="nav-tab <?php if ($tab === 'tools'): ?>nav-tab-active<?php endif;?>">Tools</a>
        </nav>
        -->


    <form method="post">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder ">
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Post media folders</h2>
                            </div>
                            <div class="inside">

                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Organize by post type?</label>
                                            </th>
                                            <td>
                                                <input type="checkbox" name="organize_post_img_by_type"
                                                    id="organize_post_img_by_type" value="1"
                                                    <?php checked($organize_post_img_by_type, 1);?>>
                                                <?php
        // Show post types
        $args = array(
            'public' => true,
            '_builtin' => false, // exclude default post types
        );
        $post_types = get_post_types($args);
        // add back default post types 'post' and 'page'
        array_push($post_types, 'post', 'page');
        echo '(' . implode(', ', array_map(function ($post_type) {
            return '<code>' . $post_type . '</code>';
        }, $post_types)) . ')';
        ?>
                                                <p class="description">All uploads attached to posts will be housed in a
                                                    corresponding folder.</strong></p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label>Organize by taxonomy:</label>
                                            </th>
                                            <td>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="radio" name="organize_post_img_by_taxonomy" value=""
                                                        <?php checked($organize_post_img_by_taxonomy, '');?>>
                                                    None
                                                </label><br>
                                                <?php
        $taxonomies = get_taxonomies(array('public' => true));
        foreach ($taxonomies as $taxonomy) {
            ?>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="radio" name="organize_post_img_by_taxonomy"
                                                        value="<?php echo esc_attr($taxonomy); ?>"
                                                        <?php checked($organize_post_img_by_taxonomy, $taxonomy);?>>
                                                    <code><?php echo esc_html($taxonomy); ?></code>
                                                </label>
                                                <br>
                                                <?php
            }
            ?>

                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row">
                                                <label>Use date folders:</label>
                                            </th>
                                            <td>Set in <a href="<?php echo admin_url(); ?>options-media.php">Media
                                                    Settings</a></td>
                                        </tr>

                                        <tr>
                                            <th scope="row">
                                                <label>Preview:</label>
                                            </th>
                                            <td>
                                                <div id="planned-path"></div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>


                            </div>
                        </div>

                        <p class="submit">
                            <input type="submit" name="tidy_media_organizer_save" class="button-primary"
                                value="Save Changes">
                        </p>
    </form>

    <script>
    // Update the planned path in real-time based on the user's selections
    function updatePlannedPath() {
        var basedir = '<?php echo esc_js(wp_upload_dir()['basedir']); ?>';
        var postTypeEnabled = document.querySelector('[name="organize_post_img_by_type"]').checked;
        var taxonomySlug = document.querySelector('[name="organize_post_img_by_taxonomy"]:checked');

        var path = basedir;

        if (postTypeEnabled) {
            path += '/<strong>{post_type}</strong>';
        }

        if (taxonomySlug && taxonomySlug.value !== '') {
            path += '/<strong>' + taxonomySlug.value + '/{term_slug}</strong>';
        }


        // Get the value of the 'uploads_use_yearmonth_folders' option using PHP and assign it to a JavaScript variable
        var uploadsUseYearMonthFolders = <?php echo get_option('uploads_use_yearmonth_folders'); ?>;

        // Check if the value of the 'uploads_use_yearmonth_folders' option is 1
        if (uploadsUseYearMonthFolders === 1) {
            // Create a new Date object with today's date
            var today = new Date();
            // Get the year and month from the Date object and format them as 'YYYY/MM'
            var year = today.getFullYear();
            var month = today.getMonth() + 1;
            var dateFolders = year + '/' + (month < 10 ? '0' + month : month);
        }
        // If dateFolders exists, append it to the path
        if (dateFolders) {
            path += '/<strong>' + dateFolders + '</strong>';
        }


        document.querySelector('#planned-path').innerHTML = path;
    }

    // Listen for changes to the form and update the planned path
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('[name="organize_post_img_by_type"]').addEventListener('change',
            updatePlannedPath);
        var radioButtons = document.querySelectorAll('[name="organize_post_img_by_taxonomy"]');
        for (var i = 0; i < radioButtons.length; i++) {
            radioButtons[i].addEventListener('change', updatePlannedPath);
        }

        updatePlannedPath();
    });
    </script>

</div>


<?php

}






/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */




/**
 * Catch Saved Posts.
 * 
 * This function is triggered when a post is saved in WordPress. It checks whether the post is not a revision
 * and then proceeds to call the tidy_post_attachments function, passing in the post ID as a parameter.
 * 
 * @param int $post_id The ID of the post being saved.
 * @return void
 */
function do_saved_post($post_id) {

    if ( ! wp_is_post_revision( $post_id ) ) {

        // Only for post, page and custom post types
        $args = array(
            'public' => true,
            '_builtin' => false, // exclude default post types
        );
        $post_types = get_post_types($args);
        array_push($post_types, 'post', 'page');
        $my_post_type = get_post_type($post_id);

        if (in_array($my_post_type, $post_types)) {
            tidy_post_attachments($post_id);
        } else {
            // error: disallowed post type
        }

    }
}
add_action('save_post', 'do_saved_post', 10, 1);






/**
 * Tidy Post Attachments.
 * 
 * This main function is responsible for tidying up post attachments after a post is saved in WordPress. It retrieves all
 * the attachments related to the post, and checks if they are located in the preferred path. If an attachment is found
 * in a non-preferred path, it will be moved to the preferred path.
 * 
 * @param int $post_id The ID of the post to tidy up its attachments.
 * @return boolean Returns false if no attachments are found, or if any errors occur during the attachment move process.
 */
function tidy_post_attachments($post_id) {

    $post_attachments = get_saved_post_attachments($post_id);
    $preferred_path = get_preferred_post_img_path($post_id);

    if ($post_attachments) {
        foreach ($post_attachments as $post_attachment) {
            $existing_path = get_existing_post_img_path($post_attachment);
            $move_post_check = check_media_for_move($post_attachment, $existing_path, $preferred_path);
            if ($move_post_check == true) {
                // echo 'do the move';
                move_media_file($post_attachment->ID, $existing_path, $preferred_path);
            } else {
                // echo 'no move';
            }
        }
    } else {
        // echo 'No attachments?!';
        return false;
    }

}





/**
 * Get Post Attachments.
 * 
 * Retrieves saved post attachments.
 * 
 * @param int $post_id The ID of the post to retrieve attachments from.
 * @return void
 */
function get_saved_post_attachments($post_id) {

    $post_attachments = get_attached_media('', $post_id);
    // echo 'Number of post attachments: <mark>'. count($post_attachments).'</mark><br>';

    return $post_attachments;
}



/**
 * Check Media For Move.
 * 
 * This function is responsible for checking whether a given media attachment should be moved from its existing path to a
 * preferred path. It first formulates the full path of the preferred path, and then compares it to the existing path. If
 * the two paths are identical, the function does nothing. Otherwise, it returns true, indicating that the media attachment
 * should be moved.
 * 
 * @param object $post_attachment The attachment object to check for a move.
 * @param string $existing_path The current path of the attachment.
 * @param string $preferred_path The preferred path of the attachment.
 * @return boolean Returns true if the attachment should be moved, false otherwise.
 */
function check_media_for_move($post_attachment, $existing_path, $preferred_path) {

    // 2. New path: formulate full path
    // echo '<span style="color:green">Preferred img path is '.$preferred_path.'</span><br>';
    // echo '<span style="color:green">Preferred path: ' . wp_upload_dir()['basedir'] . $preferred_path . '</span><br>';

    // 3. Compare existing and new paths
    if ($existing_path === wp_upload_dir()['basedir'] . $preferred_path) {
        // echo 'That\'s the same 😄';
        // Do nothing
        // echo '<br><span style="color:blue">Doing nothing</span><br>';

            my_trigger_notice(3);

        return false;
    } else {
        // echo 'That\'s different! 🤬';
        return true;

    }
}



/**
 * Get Image's Existing Path.
 * 
 * This function is responsible for retrieving the existing path of a given post attachment. It first retrieves the
 * attachment file path using the get_attached_file function, and then returns the directory name of the path using
 * the dirname function.
 * 
 * @param object $post_attachment The attachment object to retrieve the existing path for.
 * @return string Returns the existing path of the attachment.
 */
function get_existing_post_img_path($post_attachment) {
    // 1. Existing path: find it
    $attachment_path = get_attached_file($post_attachment->ID);
    $existing_path = dirname($attachment_path);
    // echo '<span style="color:red">Existing path foo: ' . $existing_path . '</span><br>';

    return $existing_path;
}



/**
 * Preferred File Path Getter.
 * 
 * Retrieves the preferred file path for a post's images based on settings.
 *
 * This fetches the user's stored preference from the database and formulates a corresponding file path.
 *
 * @param int $post_id The ID of the post to retrieve the preferred path for.
 *
 * @return string The preferred file path for the post's images.
 */
function get_preferred_post_img_path($post_id) {

    // Retrieve current settings from database
    global $wpdb;
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $settings = $wpdb->get_results("SELECT * FROM $table_name");
        $settings_arr = array();
        foreach ($settings as $setting) {
            $settings_arr[$setting->setting_name] = $setting->setting_value;
        }
        $organize_post_img_by_type = isset($settings_arr['organize_post_img_by_type']) ? $settings_arr['organize_post_img_by_type'] : 0;
        $organize_post_img_by_taxonomy = isset($settings_arr['organize_post_img_by_taxonomy']) ? $settings_arr['organize_post_img_by_taxonomy'] : '';
    }

    // Build preferred file path...
    // a. Start out with just /wp-content/uploads
    $basedir = wp_upload_dir()['basedir'];
    // $preferred_path = $basedir;
    $preferred_path = '';
    // b. Add post type?
    if ($organize_post_img_by_type == 1) {
        $post_type = get_post_type($post_id);
        $preferred_path .= '/'.$post_type;
    }
    // c. Add taxonomy name?
    if ($organize_post_img_by_taxonomy != '') {
        $preferred_path .= '/' . $organize_post_img_by_taxonomy;
        $post_terms = get_the_terms($post_id, $organize_post_img_by_taxonomy);
        // echo '<mark>post terms:</mark><br>';
        // print_r($post_terms);
        $preferred_path .= '/' . $post_terms[0]->slug;
    }
    // d. Date folders?
    $wp_use_date_folders = get_option('uploads_use_yearmonth_folders');
    if ($wp_use_date_folders == 1) {
        $post_date = get_post_field('post_date', $post_id);
        $formatted_date = date('Y/m', strtotime($post_date));
        $preferred_path .= '/' . $formatted_date;
    }
    // Return short preferred path, not inc basedir
    return $preferred_path;
}






/**
 * Move Media File
 * 
 * Move an attachment file from one directory to another and update metadata accordingly.
 * 
 * @param int $attachment_id The ID of the attachment to move.
 * @param string $existing_path The current path of the attachment file.
 * @param string $preferred_path The preferred path of the attachment file.
 * @return void
 */
function move_media_file($attachment_id, $existing_path, $preferred_path) {

    // echo '<br><span style="color:orange">Doing move_media_file</span><br>';

    /*
    * This code must update upto three images or sets of images:
    * 1. A scaled-down, big-file original (filename-scaled.jpeg - created by WordPress if there was a big [original_image])
    * 2. Multiple, smaller thumbnail [sizes]
    * 3. The [original_image], if it was big
    *
    * The process involves:
    * A. Moving the files for each
    * B. Updating metadata in either wp_postmeta or wp_posts, depending on the file and on the metadata
    */

    /*
    * Existing location
    */
    // Get the attachment metadata
    $attachment_metadata = wp_get_attachment_metadata($attachment_id);
    // Get old subfolder part
    $old_subfolder = dirname($attachment_metadata['file']);

    /*
    * Set the scene
    */
    // Get the WordPress uploads directory path
    $uploads_dir = wp_upload_dir();
    $uploads_dir_path = $uploads_dir['basedir']; // eg. /Users/robert/Sites/context.local/wp-content/uploads

    /*
    * New location
    */
    // Set the name of the new sub-folder to move the files to
    $new_subfolder = $preferred_path;
    // Generate the new sub-folder path based on the WordPress uploads directory
    $new_file_dir = $uploads_dir_path . trailingslashit($new_subfolder); // eg. /Users/robert/Sites/context.local/wp-content/uploads/new-subfolder/

    if ($attachment_metadata) {

        /* * * * * * * * * * * * * * * * * * * * *
        *
        *      MOVE IMAGES
        *
        /* * * * * * * * * * * * * * * * * * * * */

        // Get the current file path of the attachment
        $current_file = get_attached_file($attachment_id); // eg. /Users/robert/Sites/context.local/wp-content/uploads/2023/02/sgalagaev-5iSCtrJX5o-unsplash-scaled.jpeg

        /*
        * Make new subfolder
        */
        // Create the new sub-folder if it doesn't exist
        if (!file_exists($new_file_dir)) {
            wp_mkdir_p($new_file_dir);
        }

        /*
        * 1. Main image
        */
        // echo '<br>new_file: '.$new_file_dir.'<br>';
        // Generate the new file path for the attachment based on the new sub-folder
        $new_file = $new_file_dir . basename($current_file); // eg. /Users/robert/Sites/context.local/wp-content/uploads/new-subfolder/sgalagaev-5iSCtrJX5o-unsplash-scaled.jpeg
        // Move the files to the new sub-folder
        // echo '<br>current: '.$current_file.'<br>';
        // echo 'new: ' . $new_file . '<br>';
        
        // echo '<hr><br><span style="color:red">from: '.$current_file.'</style><br>';
        // echo '<br><span style="color:green">to: ' . $new_file . '</style><br>';


        $result = rename($current_file, $new_file);

        if ($result) {

            // Show success notice on post.php edit page only

                    my_trigger_notice(1);




            /*
            * 2. Sized images
            */
            foreach ($attachment_metadata['sizes'] as $size => $data) {
                // Get the current file path of the size variant
                $current_size_file = trailingslashit(dirname($current_file)) . $data['file'];
                // Generate the new file path for the size variant based on the new sub-folder
                $new_size_file = $new_file_dir . $data['file'];
                // Move the size variant to the new sub-folder
                $result = rename($current_size_file, $new_size_file);
                /*
            if ($result) {
            // Update the attachment metadata with the new file path for the size variant
            // TODO: This is a needless str_replace, as 'file' for sized images appears to only be the filename
            $attachment_metadata['sizes'][$size]['file'] = str_replace($old_subfolder, $new_subfolder, $data['file']);
            } else {
            // Handle error case
            error_log('Error moving file: ' . $error['message']);
            }
            */
            }

            /*
            * 3. Original image (ie. not filename-scaled.jpeg)
            */
            // a. In wp_postmeta value "_wp_attachment_metadata" (a serialised array), [original_image] carries no subfolder, only image name - no update required there.
            // b. However, the original file should be moved.
            if (isset($attachment_metadata['original_image'])) {
                $current_original_file = trailingslashit($uploads_dir_path) . trailingslashit($old_subfolder) . $attachment_metadata['original_image'];
                // echo $current_original_file ."\n";
                $new_original_file = trailingslashit($uploads_dir_path) . trailingslashit($new_subfolder) . $attachment_metadata['original_image'];
                // echo $new_original_file;
                if (file_exists($current_original_file)) {
                    $result = rename($current_original_file, $new_original_file);
                    if (!$result) {
                        echo "Error moving original file.";
                    }
                } else {
                    echo "Original file does not exist.";
                }
            } else {
                echo "[original_image] not found";
            }

            /* * * * * * * * * * * * * * * * * * * * *
            *
            *     UPDATE DATABASE
            *
            /* * * * * * * * * * * * * * * * * * * * */

            /*
            * A. wp_postmeta: "_wp_attached_file" (with 1. Main image)
            * - If [original_image], then it's "2023/02/myfile-scaled.jpeg"
            * - If no [original_image], then it's "2023/02/myfile.jpeg"
            * This is determined automatically (?)
            */
            // Check if the file exists
            if (file_exists($new_file)) {
                /*
                // Update the attachment meta data in the WordPress database
                $new_location_partial = trailingslashit($new_subfolder) . basename($current_file);
                update_post_meta($attachment_id, '_wp_attached_file', $new_location_partial);
                */
                // Update the attachment meta data in the WordPress database
                $new_location_partial = trailingslashit($new_subfolder) . basename($current_file);
                $att_location_without_leading_slash = ltrim($new_location_partial, '/');
                // remove_action('save_post', 'move_media_file');
                update_post_meta($attachment_id, '_wp_attached_file', $att_location_without_leading_slash);
                // add_action('save_post', 'move_media_file');


            } else {
                // File doesn't exist, show an error message
                echo "The specified file does not exist.";
            }

            /*
            * B. wp_postmeta: "_wp_attachment_metadata" (serialised array)
            */
            // Update the attachment metadata with the new file path for the original file
            $file_value_without_leading_slash = ltrim($new_subfolder, '/');
            $attachment_metadata['file'] = str_replace(
                $old_subfolder, // eg. /Users/robert/Sites/context.local/wp-content/uploads
                $file_value_without_leading_slash, // eg. /Users/robert/Sites/context.local/wp-content/uploads/new-subfolder/
                $attachment_metadata['file']
            );
            wp_update_attachment_metadata($attachment_id, $attachment_metadata);

            // c. wp_posts: "guid"
            // - If [original_image] (big size upload), then it is "http://context.local:8888/wp-content/uploads/2023/02/letters-scaled.jpeg")
            // - If no [original_image] (no big size), then it is "http://context.local:8888/wp-content/uploads/2023/02/letters.jpeg")
            // Regardless, isn't it always the value of [file]?
            // guid is simply a unique identifier. Most people advise not to change.
            // However, newly-uploaded images, at the least, benefit from being accurately represented.
            $new_guid = trailingslashit($uploads_dir['baseurl']) . $attachment_metadata['file'];
            // echo "new guid: ".$new_guid . "\n";
            // Update the GUID value in the database
            global $wpdb;
            $table_name = $wpdb->prefix . 'posts';
            $data = array('guid' => $new_guid);
            $where = array('ID' => $attachment_id);
            $result = $wpdb->update($table_name, $data, $where);

            // Check if the update was successful
            if ($result === false) {
                // Error handling
            } elseif ($result === 0) {
                // No rows were updated
            } else {
                // The GUID was updated successfully
            }

        } else {
            // Handle error case
            my_trigger_notice(2);
            echo 'Could not move main image.';
        }
    }


}








/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */


/**
 * Notice Query Sender
 * 
 * my_trigger_notice - Adds a query arg to the redirect post location URL to trigger a notice.
 * 
 *  This function adds a filter to the 'redirect_post_location' hook that modifies the URL of the post redirect location by adding a query arg with the specified notice key. This is useful for triggering notices after a post has been updated or created.
 * 
 * @param string $key The notice key to add to the URL. Default is an empty string.
 * 
 * @return void
 */
function my_trigger_notice($key = '')
{

    global $pagenow;

    if ($pagenow === 'post.php') {

        add_filter(
            'redirect_post_location',
            function ($location) use ($key) {
                $key = sanitize_text_field($key);
                return add_query_arg(array('notice_key' => rawurlencode(sanitize_key($key))), $location);
            }
        );

    }

}



/**
 * Notify Post Moved.
 * 
 * Displays an admin notice with a specific message based on the notice key provided in the URL parameter.
 * 
 * @since 1.0.0
 * 
 * @return void
 */
function my_admin_notices()
{
    if (!isset($_GET['notice_key'])) {
        return;
    }
    $notice_key = wp_unslash(sanitize_text_field($_GET['notice_key']));
    $all_notices = [
        1 => 'Moved attached image to preferred folder',
        2 => 'Could not move attached image to preferred folder',
        3 => 'Attached image already in preferred media path - not moved',
    ];
    if (empty($all_notices[$notice_key])) {
        return;
    }
    if ($notice_key == 1) {
        $notice_class = "success";
    } elseif ($notice_key == 2) {
        $notice_class = "error";
    } elseif ($notice_key == 3) {
        $notice_class = "info";
    }
    ?>
<div class="notice notice-<?php echo $notice_class; ?> is-dismissible">
    <p><?php echo esc_html($all_notices[$notice_key]);?>
    </p>
</div>
<?php
}
add_action('admin_notices', 'my_admin_notices');



?>