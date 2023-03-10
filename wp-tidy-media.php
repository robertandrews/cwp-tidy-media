<?php
/*
Plugin Name: Tidy Media Organizer
Plugin URI: https:/www.robertandrews.co.uk
Description: A plugin to organize media by post type or taxonomy.
Version: 1.0
Author: Robert Andrews
Author URI: https:/www.robertandrews.co.uk
 */



 
/**
 * Do My Log.
 *
 * Write a log message to a log file.
 * This function writes a log message to a log file, with the current timestamp and the message.
 *
 * @param string $log_message The log message to be written to the log file.
 * @return void
 */
function do_my_log($log_message)
{
    $logging = true;
    if ($logging == true) {
        $log_file = plugin_dir_path(__FILE__) . 'wp-tidy-media.log';
        $log_timestamp = gmdate('d-M-Y H:i:s T');

        $log_entry = "[$log_timestamp] $log_message\n";
        error_log($log_entry, 3, $log_file);
    }
}



/**
 * Database Setup.
 *
 * Creates a new database table for storing Tidy Media Organizer plugin settings.
 *
 * @global object $wpdb The WordPress database object.
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
            setting_value varchar(300) NOT NULL,
            PRIMARY KEY (setting_id)
        ) $charset_collate;";
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'tidy_media_organizer_create_table');



/**
 * Clean On Deletion.
 *
 * Deletes the database table used by the Tidy Media Organizer plugin.
 *
 * This function deletes the database table used by the Tidy Media Organizer plugin when the plugin is uninstalled.
 *
 * @since 1.0.0
 */
function tidy_media_organizer_delete_table()
{
    global $wpdb;
    global $table_name;

    $table_name = $wpdb->prefix . 'tidy_media_organizer';

    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_uninstall_hook(__FILE__, 'tidy_media_organizer_delete_table');

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
function tidy_media_organizer_main_page()
{?>
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
                // TODO: Save/retrieve a serialised array, not a single text string.
                // TODO: Use dynamic input entry/array consolidation
                array('setting_name' => 'domains_to_replace', 'setting_value' => isset($_POST['domains_to_replace']) ? sanitize_text_field($_POST['domains_to_replace']) : ''),
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
        $domains_to_replace = isset($settings_arr['domains_to_replace']) ? $settings_arr['domains_to_replace'] : '';

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
/*
    $default_tab = null;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;
    */
    ?>

    <!--
        <nav class="nav-tab-wrapper">
        <a href="?page=tidy-media-organizer-options" class="nav-tab <?php /* if ($tab === null): ?>nav-tab-active<?php endif; */?>">Options</a>
        <a href="?page=my-plugin&tab=tools" class="nav-tab <?php /* if ($tab === 'tools'): ?>nav-tab-active<?php endif; */?>">Tools</a>
        </nav>
        -->


    <form method="post">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder ">
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">

                        <!-- Post media folders -->
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
                                                    corresponding folder.</p>
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
                        </div><!-- end .postbox -->

                        <!-- Body image URLs-->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Use relative image URLs in post body</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Remove home URL</label>
                                            </th>
                                            <td>
                                                <?php echo home_url(); ?>
                                                <p class="description">eg. <strike><?php echo home_url(); ?></strike>/wp-content/uploads/path/to/image.jpeg</p></td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Additional domains</label>
                                            </th>
                                            <td>
                                                 <input type="text" name="domains_to_replace" id="domains_to_replace" size="75" value="<?php echo $domains_to_replace; ?>" />                                            
                                                 <p class="description">Separate multiple hostnames by comma (eg. "http://www.oldsite.com, "https://testsite:8080")</p>
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

    do_my_log("do_saved_post() - ".$post_id." ".get_post_type()." - ".get_the_title($post_id));

    if (!wp_is_post_revision($post_id)) {
        do_my_log("Not a revision.");
        // Only for post, page and custom post types
        $args = array(
            'public' => true,
            '_builtin' => false, // exclude default post types
        );
        $post_types = get_post_types($args);
        array_push($post_types, 'post', 'page'); // add back post and page
        $my_post_type = get_post_type($post_id);

        if (in_array($my_post_type, $post_types)) {
            do_my_log("Save is valid for action.");
            // TODO: Consider switching order - onyl tidy (move posts) when all links are set?
            tidy_post_attachments($post_id);
            make_body_imgs_relative($post_id);
            // fix_body_image_paths($post_id); // TODO: Avoid infinite loop
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

    do_my_log('🧹 tidy_post_attachments()...');

    $post_attachments = get_attached_media('', $post_id);

    if ($post_attachments) {
        foreach ($post_attachments as $post_attachment) {

            do_my_log("🖼 Attachment ".$post_attachment->ID . " - " . $post_attachment->post_title);

            // Generate source and destination path pieces
            $old_image_details = old_image_details($post_attachment);
            $new_image_details = new_image_details($post_id, $post_attachment);

            // Check if need to move
            if ($old_image_details['filepath'] == $new_image_details['filepath']) {
                do_my_log("Path ok, no need to move.");
                my_trigger_notice(3);
                // return false
            } else {
                // print_r("Paths are different! Need to move.\n");
                do_my_log("🚨 Path looks incorrect - ".$old_image_details['filepath']);
                $move_main_file_success = move_main_file($post_attachment->ID, $old_image_details, $new_image_details);
                if ($move_main_file_success == true) {
                    // TODO: Shouldn't these two be conditional on the first file being moved successfully, as per original code... ?
                    $move_sizes_files_success = move_sizes_files($post_attachment->ID, $old_image_details, $new_image_details);
                    $move_original_file_success = move_original_file($post_attachment->ID, $old_image_details, $new_image_details);
                    return $move_main_file_success;
                }
            }

        }
    } else {
        do_my_log("No attachments found.");
        return false;
    }

}







/**
 * Make In-Line Image URLs relative
 * 
 * This function takes a WordPress post ID and modifies the post's content by
 * making all local image URLs relative to the site's root directory. It does this by
 * removing any specified domains from the image URLs.
 * 
 * The function only removes the site's own scheme domain (eg. "http://www.myblog.com").
 * 
 * @param int $post_id The ID of the WordPress post to modify.
 * @return void
 */
function make_body_imgs_relative($post_id) {

    do_my_log("🔗 make_body_imgs_relative()...");

    // Get the post content
    $content = get_post_field('post_content', $post_id);
    $modified = false;

    // TODO: Check this is actually being set - may only be in scope on options page
    // Set up list of domains to strip from links - site URL is added by default
    global $wpdb;
    $setting_name = 'domains_to_replace';
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    $query = $wpdb->prepare("SELECT setting_value FROM $table_name WHERE setting_name = %s", $setting_name);
    $domains_to_replace = $wpdb->get_var($query);

    $domains_to_remove = array_map('trim', explode(",", $domains_to_replace));
    if (!in_array(get_site_url(), $domains_to_remove)) {
        array_push($domains_to_remove, get_site_url());

    }

    // For each domain we're removing
    $num_changes = 0;
    foreach ($domains_to_remove as $domain) {
        do_my_log("Checking for any <img src=\"".$domain."...");

        // Find any strings like "<img src="http://www.domain.com"
        $pattern = '/<img[^>]*src=["\']' . preg_quote($domain, '/') . '(.*?)["\']/i';
        // Replace the leading portion only, ie. "<img src="{match}"
        $replacement = '<img src="$1"';
        // Perform the replacement
        $new_content = preg_replace($pattern, $replacement, $content);

        // If the content has changed, set the modified flag to true
        if ($new_content !== $content) {
            $modified = true;
            do_my_log("Changed a link.");
            $content = $new_content;
            $num_changes++;
        }
    }
    do_my_log("Changes made: ".$num_changes++);

    // If any URLs were modified, re-save the post
    if ($modified == true) { // was if ($new_content) {

        do_my_log("Need to save post...");

        // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
        remove_action('save_post', 'do_saved_post', 10, 1);
        // Re-save the post
        wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $new_content,
        ));
        do_my_log("Post updated.");
        // Hook it back up
        add_action('save_post', 'do_saved_post', 10, 1);
    } else {
        do_my_log("Post not updated.");
    }
}





function fix_body_img_paths($post_id) {

    // Universal details
    $uploads_base = trailingslashit(wp_upload_dir()['baseurl']); // http: //context.local:8888/wp-content/uploads/
    $uploads_folder = str_replace(trailingslashit(home_url()), '', $uploads_base); // /wp-content/uploads/

    echo $post_id . " ". get_the_title($post_id) ."\n\n";

    // Get the post content
    $content = get_post_field('post_content', $post_id);
    // print_r($content);
    // Find relative URLs in the content
    $pattern = '/<img[^>]+src=["\']\/([^"\']+)/';                               // <img src="/wp-content/uploa...
    preg_match_all($pattern, $content, $matches);


    // For every src found,
    foreach ($matches[1] as $found_img_src) {                                   // /wp-content/uploads/media/folio/clients/wired/tom_heather.jpg

        $modified = null;
        
        echo "Found src attribute ". $found_img_src."\n";

        // Get found file's details
        $found_img_filepath = get_home_path() . $found_img_src;                 // /Users/robert/Sites/context.local/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg
        // echo $found_img_filepath."\n";
        $post_attachment = null;
        

        // ✅ File is where src says
        if (file_exists($found_img_filepath)) {

            echo "File exists at src location.\n";
            
            // Generate its details
            $found_img_url = trailingslashit(get_site_url()) . $found_img_src;  // http://context.local:8888/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg
            $uploads_base = trailingslashit(wp_upload_dir()['baseurl']);           
            $img_path_no_base = str_replace($uploads_base, '', $found_img_url);

            // Get attachment ID for file at this location
            $args = array(
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                // 'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'value' => $img_path_no_base,
                        'compare' => 'LIKE',
                        'key' => '_wp_attachment_metadata',
                    ),
                ),
            );
            $query = new WP_Query($args);
            if ($query->have_posts()) {
                $query->the_post();
                // Get the attachment metadata
                $attachment_id = get_the_ID();
                wp_reset_postdata();
            } else {
                // No attachments found
                echo 'No attachments found';
            }

            // Generate actual and proper path pieces
            $post_attachment = get_post($attachment_id);                        // WP_Post object
            $old_image_details = old_image_details($post_attachment);
            $new_image_details = new_image_details($post_id, $post_attachment);

            

            
            // 😩 But this is the _wrong_ location - move it, and update post and metadata
            if ($old_image_details['subdir'] !== $new_image_details['subdir_stem'] ) {


                echo "But this is the wrong location\n";

                echo "post parent " . $post_attachment->post_parent . "\n";

                // If image belongs to this post or is as yet unattached,
                if ($post_attachment->post_parent == $post_id || $post_attachment->post_parent == 0) {

                    echo "Move to location expected of this post.\n";
                    echo "from: ".$old_image_details['filepath']."\n";
                    echo "to:   ".$new_image_details['filepath']."\n";

                    // 1. Move the file
                    $move_result = move_main_file($post_attachment->ID, $old_image_details, $new_image_details);
                    echo "move: ".$move_result."\n";
                    if ($move_result === true) {

                        // Also move image variants
                        move_sizes_files($post_attachment->ID, $old_image_details, $new_image_details);
                        move_original_file($post_attachment->ID, $old_image_details, $new_image_details);

                        // 2. Update the body
                        echo "Update the body...\n";
                        $new_src = $uploads_folder . trailingslashit($new_image_details['subdir']) . $new_image_details['filename'];
                        echo $new_src."\n\n";
                        $new_content = str_replace($found_img_src, $new_src, $content);
                        // If the content has changed, set the modified flag to true
                        if ($new_content !== $content) {
                            $modified = true;
                            $content = $new_content;
                        }
                        if ($modified == true) { // was if ($new_content) {
                            // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                            remove_action('save_post', 'do_saved_post', 10, 1);
                            // Re-save the post
                            wp_update_post(array(
                                'ID' => $post_id,
                                'post_content' => $content,
                            ));
                            // Hook it back up
                            add_action('save_post', 'do_saved_post', 10, 1);
                        }

                        // 3. Attach image to this post if it was unattached
                        if ( $post_attachment->post_parent === 0 || $post_attachment->post_parent === '' ) {
                            // Set the post_parent of the image to the post ID
                            $update_args = array(
                                'ID' => $attachment_id,
                                'post_parent' => $post_id,
                            );
                            remove_action('save_post', 'do_saved_post', 10, 1);
                            wp_update_post($update_args);
                            add_action('save_post', 'do_saved_post', 10, 1);
                        }

                    }




                }



            }

            

            // TODO: Else: maybe it exists in the *right* place (so the body URL alone is wrong)...
            // $expected_filepath = trailingslashit(wp_upload_dir()['basedir']) . trailingslashit($new_image_details['subdir']) . basename($found_img_src);
            // $expected_filepath."\n";
            

        } else {
            // ❌ File is not even at src location                                  // /Users/robert/Sites/context.local/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg

            print_r("Does not exist\n\n");
        }







        



        /*
        // Is the img src path in user's preferred format?
        if (strpos($found_img_src, $new_image_details['subdir_stem']) !== false) {
            // ✅ URL is in user's expected format
        } else {
            // ❌ URL not in user's expected format
            // echo "Image URL not in correct format";

            // 1. If it's where specified, move it
        }
        */

    }

    print_r($content);


}






/**
 * Generate Existing Image Details
 *
 * Retrieves various details of an old image attachment for a post.
 * This is designed to make the partial folder and filepath parts available to other functions
 * in a singular array. This avoids needing to generate those parts in those functions.
 *
 * @param WP_Post $post_attachment The WordPress post object representing the attachment (i.e., the image).
 * @return array An associative array containing details of the image's old location (e.g., 'dirname', 'filepath', 'subdir', 'filename', 'guid').
 */

function old_image_details($post_attachment)
{

    $filepath = get_attached_file($post_attachment->ID);
    $upload_dir = wp_upload_dir();
    $subdir = str_replace($upload_dir['basedir'], '', dirname($filepath));
    $subdir = ltrim($subdir, '/');
    $guid = $post_attachment->guid;

    $old_image = array();
    $old_image['filepath'] = $filepath; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $old_image['dirname'] = dirname($filepath); // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/
    $old_image['subdir'] = $subdir; // post/client/contentnext/2011/12
    $old_image['filename'] = basename($filepath); // netflix-on-tv-in-living-room-o.jpg
    $old_image['guid'] = $guid; // http://context.local:8888/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    // print_r($old_image);
    return $old_image;

}




/**
 * Generate New Image Details
 *
 * Formulates various details of the new image attachment for a post.
 *
 * This is designed to make the partial folder and filepath parts available to other functions
 * in a singular array. This avoids needing to generate those parts in those functions.
 *
 * @param int $post_id The ID of the post where the image is attached.
 * @param object $post_attachment The WP_Post object representing the attached image.
 * @return array An associative array containing the details of the new image.
 */
function new_image_details($post_id, $post_attachment)
{

    // Get user's path preferences from database
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
        // These will formulate the preferred path
    } else {
        // No database settings
    }

    // Build new subdir
    $new_subdir = '';
    // a. Use post type?
    if ($organize_post_img_by_type == 1) {
        $post_type = get_post_type($post_id);
        $new_subdir .= $post_type;
    }
    // b. Use taxonomy name and term?
    if ($organize_post_img_by_taxonomy != '') {
        $new_subdir .= '/' . $organize_post_img_by_taxonomy;
        $post_terms = get_the_terms($post_id, $organize_post_img_by_taxonomy);
        // print_r($post_terms);
        if ($post_terms) {
            $new_subdir .= '/' . $post_terms[0]->slug;
            $new_subdir_stem = $new_subdir;
        }
    }
    // c. Are date-folders in use?
    $wp_use_date_folders = get_option('uploads_use_yearmonth_folders');
    if ($wp_use_date_folders == 1) {
        $post_date = get_post_field('post_date', $post_id);
        $formatted_date = date('Y/m', strtotime($post_date));
        $new_subdir .= '/' . $formatted_date;
    }
    // new subdir is now generated

    $filepath = get_attached_file($post_attachment->ID);

    $upload_dir = wp_upload_dir();
    $subdir = $new_subdir;

    // Populate bits of $new_image
    $new_image = array();
    $new_image['subdir'] = $subdir; // post/client/contentnext/2011/12
    $new_image['subdir_stem'] = $new_subdir_stem; // post/client/contentnext
    $new_image['filepath'] = trailingslashit(trailingslashit($upload_dir['basedir']) . $subdir) . basename($filepath); // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $new_image['dirname'] = trailingslashit($upload_dir['basedir']) . $subdir; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/
    $new_image['filename'] = basename($filepath); // netflix-on-tv-in-living-room-o.jpg
    $new_image['guid'] = trailingslashit(trailingslashit($upload_dir['baseurl']) . $subdir) . basename($filepath); // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    // print_r($new_image);
    return $new_image;

}




/**
 * Move Media File
 *
 * Move the main image file from its old location to a new location and update related metadata in the WordPress database.
 *
 * @param int $attachment_id The ID of the attachment (i.e., the image).
 * @param array $old_image_details An associative array containing details of the image's old location (e.g., 'dirname', 'filepath', 'subdir', 'filename').
 * @return bool True if the move and database updates were successful, false otherwise.
 */
function move_main_file($attachment_id, $old_image_details, $new_image_details) {

    do_my_log("🔧 move_main_file()...");

    // A. Move file
    // Get the WordPress uploads directory path
    $uploads_dir = wp_upload_dir();
    $uploads_dir_path = $uploads_dir['basedir']; // eg. /Users/robert/Sites/context.local/wp-content/uploads
    // Create the new sub-folder if it doesn't exist
    if (!file_exists($new_image_details['dirname'])) {
        do_my_log("Making directory ".$new_image_details['dirname']."...");
        wp_mkdir_p($new_image_details['dirname']);
    }
    // If folder now exists
    if (file_exists($new_image_details['dirname'])) {

        // If source file actually exists
        if (file_exists($old_image_details['filepath'])) {
            do_my_log("Source file exists at given location - ".$old_image_details['filepath']);

            // Move file
            do_my_log("Move to " . $new_image_details['filepath']);
            $result = rename($old_image_details['filepath'], $new_image_details['filepath']);

            if ($result) {
                do_my_log("Moved: " . $result);
                do_my_log("Updating attachment's database fields.");

                // B. Update database
                // Update database #1 - image wp_postmeta, _wp_attached_file (eg. post/client/clarity/2018/06/146343_photo-1486312338219-ce68d2c6f44d-4959-art.jpe)
                update_post_meta($attachment_id, '_wp_attached_file', trailingslashit($new_image_details['subdir']) . $new_image_details['filename']);
                // Update database #2 - image wp_postmeta, _wp_attachment_metadata (eg. [file] => post/client/clarity/2018/06/146343_photo-1486312338219-ce68d2c6f44d-4959-art.jpe)
                $attachment_metadata = wp_get_attachment_metadata($attachment_id);
                $attachment_metadata['file'] = trailingslashit($new_image_details['subdir']) . $new_image_details['filename'];
                wp_update_attachment_metadata($attachment_id, $attachment_metadata);

                // Update database #3 - image wp_postmeta, guid - does not alter hostname part
                $old_guid_full = get_post_field('guid', $attachment_id);
                $new_guid_full = str_replace($old_image_details['subdir'], $new_image_details['subdir'], $old_image_details['guid']);
                global $wpdb;
                $wpdb->update(
                    $wpdb->posts,
                    array('guid' => $new_guid_full),
                    array('ID' => $attachment_id),
                    array('%s'),
                    array('%d')
                );
                my_trigger_notice(1);
                do_my_log("Database fields should be updated.");
                return true;
            } else {
                my_trigger_notice(2);
                do_my_log("Moved failed");
                return false;
            }
        } else {
            do_my_log("File does not exist.");
        }
    } else {
        my_trigger_notice(2);
        do_my_log("Folder does not exist.");

        return false;
    }

}




/**
 * Move File Sizes
 *
 * Moves files for all files found in an attachment object's [sizes] array.
 * The function then moves the files from the old directory to the new directory for each size variant.
 *
 * @param int $attachment_id The attachment ID of the image.
 * @param array $old_image_details An array of the old image details generated by the old_image_details function.
 * @param array $new_image_details An array of the new image details generated by the new_image_details function.
 * @return bool $success Whether or not the move was successful.
 */

function move_sizes_files($attachment_id, $old_image_details, $new_image_details) {

    do_my_log("🔧 move_sizes_files() - " .$attachment_id . "...");

    // Get the _wp_attachment_metadata serialised array
    $attachment_metadata = wp_get_attachment_metadata($attachment_id);

    // Any [sizes]?
    $success = false;
    if (isset($attachment_metadata['sizes'])) {
        do_my_log("Metadata has [sizes]. Number of sizes: ".count($attachment_metadata['sizes']));
        $num_sizes=0;
        foreach ($attachment_metadata['sizes'] as $size => $data) {
            $num_sizes++;
            do_my_log("Size: ".$data['file']);
            // A. Move files
            // Generate the old and new filepaths for size variants
            $old_size_filename = trailingslashit($old_image_details['dirname']) . $data['file'];
            $new_size_filename = trailingslashit($new_image_details['dirname']) . $data['file'];
            // Do the move
            $result = rename($old_size_filename, $new_size_filename);
            do_my_log("Move result: ".$result);
            if ($result) {
                // Great
                do_my_log("Moved ".$data['file']);
                $success = true;
            } else {
                // Error
                $success = false;
                do_my_log("Failed to move" . $data['file']);
            }
            // B. Update database
            // No metadata to update - [sizes] filenames do not contain folders, only filenames.
        }
        do_my_log("Sizes handled: ".$num_sizes);
        // I want to access $success here
        return $success;
    } else {
        // No sizes here
        do_my_log("No [sizes].");
        return $success;
    }

}




/**
 * Move Original File
 *
 * Move the [original_image] file for a WordPress attachment to a new location.
 *
 * Since WordPress 5.3, large image uploads generate a filename-scaled.jpeg as the primary
 * file for delivery. The originally remains as initially named, whose value is stored as
 * [original_image] in the wp_postmeta _wp_attachment_metadata serialised array.
 * 
 * A. Move file.
 * B. Update database - wp_postmeta: like [sizes], [original_image] is a filename only,
 *    with no initial folder specified. No update is required.
 * C. Update database - wp_post: we already update the image's 'guid' in move_main_file() by
 *    simply correcting the subdir. This leaves in place the initial filename, whether
 *    it is filename-scaled.jpeg or filename.jpeg (original). In short, no need to udpate
 *    the 'guid'.
 *
 * @param int $attachment_id The ID of the attachment to move.
 * @param array $old_image_details An array of details about the attachment's current location.
 * @param array $new_image_details An array of details about the attachment's new location.
 * @return void
 */
function move_original_file($attachment_id, $old_image_details, $new_image_details) {

    do_my_log("🔧 move_original_file()...");

    // Get the _wp_attachment_metadata serialised array
    $attachment_metadata = wp_get_attachment_metadata($attachment_id);

    if (isset($attachment_metadata['original_image'])) {
        do_my_log("[original_image] is present.");

        // A. Move the file
        // Generate the old and new filepaths for the original
        $old_original_filename = trailingslashit($old_image_details['dirname']) . $attachment_metadata['original_image'];
        // print_r("old original: ".$old_original_filename."\n");
        $new_original_filename = trailingslashit($new_image_details['dirname']) . $attachment_metadata['original_image'];
        // print_r("new original: " . $new_original_filename . "\n");

        // Do the move
        if (file_exists($old_original_filename)) {
            do_my_log("File exists.");
            $result = rename($old_original_filename, $new_original_filename);
            if ($result) {
                // Move succeeded
                do_my_log("Moved ".$old_original_filename." to ". $new_original_filename);
            } else {
                // Move failed
                do_my_log("Move failed.");
            }
            // echo $result;
        } else {
            // Old original not found.
            do_my_log("File not found.");
        }

    } else {
        // "[original_image] not found";
        do_my_log("No [original_image].");
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
    <p><?php echo esc_html($all_notices[$notice_key]); ?>
    </p>
</div>
<?php
}
add_action('admin_notices', 'my_admin_notices');

?>