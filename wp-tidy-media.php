<?php
/*
Plugin Name: Tidy Media Organizer
Plugin URI: https://example.com/
Description: A plugin to organize media by post type or taxonomy.
Version: 1.0
Author: Your Name
Author URI: https://example.com/
 */







// Create custom database table on plugin activation
register_activation_hook(__FILE__, 'tidy_media_organizer_create_table');
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





// Display admin page
add_action('admin_menu', 'tidy_media_organizer_admin_page');
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







/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */








function tidy_media_organizer_main_page()
{ ?>

<div class="wrap">
    <h1>Tidy Media Organizer</h1>
</div>

<?php }







/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */







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
            path += '/<strong>' + taxonomySlug.value + '</strong>';
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




    <?php

}






/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */






// Hook the 'get_saved_post_attachments' function to the 'save_post' action
add_action('save_post', 'get_saved_post_attachments', 10, 1);

function get_saved_post_attachments($post_id) {
    echo 'Post was saved!<br>';
    echo 'post_id is '. $post_id.'<br>';

    $preferred_path = get_preferred_post_img_path($post_id);
    echo '<span style="color:green">Preferred img path is '.$preferred_path.'</span><br>';

    $post_attachments = get_attached_media('', $post_id);
    // var_dump($post_attachments);

    if ($post_attachments) {
        foreach ($post_attachments as $post_attachment) {
            echo $post_attachment->ID;
            $attachment_path = get_attached_file($post_attachment->ID);
            $existing_path = dirname($attachment_path);
            echo '<span style="color:red">Attachment directory: ' . $existing_path . '</span><br>';

            if ($existing_path === $preferred_path) {
                echo 'That\'s the same 😄';
                // Do nothing
            } else {
                echo 'That\'s different! 🤬';
                move_attachment_file($post_attachment->ID, $existing_path, $preferred_path);
            }

        }
    } else {
        echo 'No attachments?!';
    }


}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */



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
    // TODO: Add error handling

    // Build preferred file path...
    // Start out with just /wp-content/uploads
    $basedir = wp_upload_dir()['basedir'];
    // $preferred_path = $basedir;
    // Add post type?
    if ($organize_post_img_by_type == 1) {
        $post_type = get_post_type($post_id);
        $preferred_path .= '/'.$post_type;
    }
    // Add taxonomy name?
    if ($organize_post_img_by_taxonomy != '') {
        $preferred_path .= '/' . $organize_post_img_by_taxonomy;
        $post_terms = get_the_terms($post_id, $organize_post_img_by_taxonomy);
        // print_r($post_terms);
        $preferred_path .= '/'.$post_terms[0]->slug;
    }
    // Date folders?
    $wp_use_date_folders = get_option('uploads_use_yearmonth_folders');
    if ($wp_use_date_folders == 1) {
        $post_date = get_post_field('post_date', $post_id);
        $formatted_date = date('Y/m', strtotime($post_date));
        $preferred_path .= '/' . $formatted_date;
    }
    return $preferred_path;
}




function move_attachment_file($attachment_id, $existing_path, $preferred_path) {

    echo 'In move function now.<br>';
    echo 'attachment is '. $attachment_id.'<br>';
    echo 'existing path is '. $existing_path.'<br>';
    echo 'so, existing subfolder would be '. str_replace(wp_upload_dir()['basedir'], '', $existing_path).'<br>';
    echo 'preferred path is ' . $preferred_path.'<br>';

    // to write


}




?>