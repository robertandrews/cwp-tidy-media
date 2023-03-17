<?php
/*
Plugin Name: Tidy Media Organizer
Plugin URI: https:/www.robertandrews.co.uk
Description: A plugin to organize media by post type or taxonomy.
Version: 1.0
Author: Robert Andrews
Author URI: https:/www.robertandrews.co.uk
 */

function do_my_log($log_message)
{
    /**
     * Do My Log.
     *
     * Write a log message to a log file.
     * This function writes a log message to a log file, with the current timestamp and the message.
     *
     * @param string $log_message The log message to be written to the log file.
     * @return void
     */
    // $logging = true;
    // Retrieve current settings from database
    $settings = get_tidy_media_settings();

    if ($settings['use_log'] == 1) {
        $log_file = plugin_dir_path(__FILE__) . 'wp-tidy-media.log';
        $log_timestamp = gmdate('d-M-Y H:i:s T');

        $log_entry = "[$log_timestamp] $log_message\n";
        error_log($log_entry, 3, $log_file);
    }
}

function tidy_media_organizer_create_table()
{
    /**
     * Database Setup.
     *
     * Creates a new database table for storing Tidy Media Organizer plugin settings.
     *
     * @global object $wpdb The WordPress database object.
     * @return void
     */
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

function tidy_media_organizer_delete_table()
{
    /**
     * Clean On Deletion.
     *
     * Deletes the database table used by the Tidy Media Organizer plugin.
     *
     * This function deletes the database table used by the Tidy Media Organizer plugin when the plugin is uninstalled.
     *
     * @since 1.0.0
     */
    global $wpdb;
    global $table_name;

    $table_name = $wpdb->prefix . 'tidy_media_organizer';

    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_uninstall_hook(__FILE__, 'tidy_media_organizer_delete_table');

function tidy_media_organizer_admin_page()
{
    /**
     * Admin Menus.
     *
     * Registers the Tidy Media Organizer admin menu and sub-menu pages in the WordPress dashboard.
     *
     * @return void
     */
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

function tidy_media_organizer_main_page()
{
    /**
     * Admin Main Page.
     *
     * Displays the main page content for the Tidy Media Organizer plugin in the WordPress dashboard.
     *
     * @return void
     */
    ?>
<div class="wrap">
    <h1>Tidy Media Organizer</h1>
</div>
<?php
}

function get_tidy_media_settings()
{
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
        $organize_post_img_by_post_slug = isset($settings_arr['organize_post_img_by_post_slug']) ? $settings_arr['organize_post_img_by_post_slug'] : 0;
        $domains_to_replace = isset($settings_arr['domains_to_replace']) ? $settings_arr['domains_to_replace'] : '';
        $use_tidy_attachments = isset($settings_arr['use_tidy_attachments']) ? $settings_arr['use_tidy_attachments'] : 0;
        $use_tidy_body_imgs = isset($settings_arr['use_tidy_body_imgs']) ? $settings_arr['use_tidy_body_imgs'] : 0;
        $use_relative = isset($settings_arr['use_relative']) ? $settings_arr['use_relative'] : 0;
        $use_localise = isset($settings_arr['use_localise']) ? $settings_arr['use_localise'] : 0;
        $use_delete = isset($settings_arr['use_delete']) ? $settings_arr['use_delete'] : 0;
        $use_log = isset($settings_arr['use_log']) ? $settings_arr['use_log'] : 0;

        return array(
            'organize_post_img_by_type' => $organize_post_img_by_type,
            'organize_post_img_by_taxonomy' => $organize_post_img_by_taxonomy,
            'organize_post_img_by_post_slug' => $organize_post_img_by_post_slug,
            'domains_to_replace' => $domains_to_replace,
            'use_tidy_attachments' => $use_tidy_attachments,
            'use_tidy_body_imgs' => $use_tidy_body_imgs,
            'use_relative' => $use_relative,
            'use_localise' => $use_localise,
            'use_delete' => $use_delete,
            'use_log' => $use_log,
        );
    } else {
        // Show an error message
        echo '<div class="notice notice-error"><p>Plugin issue: <code>' . $table_name . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
        return array();
    }
}

function tidy_media_organizer_options_page()
{
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
                array('setting_name' => 'organize_post_img_by_post_slug', 'setting_value' => isset($_POST['organize_post_img_by_post_slug']) ? 1 : 0),
                // TODO: Save/retrieve a serialised array, not a single text string.
                // TODO: Use dynamic input entry/array consolidation
                array('setting_name' => 'domains_to_replace', 'setting_value' => isset($_POST['domains_to_replace']) ? sanitize_text_field($_POST['domains_to_replace']) : ''),
                array('setting_name' => 'use_tidy_attachments', 'setting_value' => isset($_POST['use_tidy_attachments']) ? sanitize_text_field($_POST['use_tidy_attachments']) : ''),
                array('setting_name' => 'use_tidy_body_imgs', 'setting_value' => isset($_POST['use_tidy_body_imgs']) ? sanitize_text_field($_POST['use_tidy_body_imgs']) : ''),
                array('setting_name' => 'use_relative', 'setting_value' => isset($_POST['use_relative']) ? sanitize_text_field($_POST['use_relative']) : ''),
                array('setting_name' => 'use_localise', 'setting_value' => isset($_POST['use_localise']) ? sanitize_text_field($_POST['use_localise']) : ''),
                array('setting_name' => 'use_delete', 'setting_value' => isset($_POST['use_delete']) ? sanitize_text_field($_POST['use_delete']) : ''),
                array('setting_name' => 'use_log', 'setting_value' => isset($_POST['use_log']) ? sanitize_text_field($_POST['use_log']) : ''),
            );
            foreach ($settings as $setting) {
                $existing_row = $wpdb->get_row("SELECT * FROM $table_name WHERE setting_name = '{$setting['setting_name']}'");
                if ($existing_row) {
                    $wpdb->query("UPDATE $table_name SET setting_value = '{$setting['setting_value']}' WHERE setting_name = '{$setting['setting_name']}'");
                } else {
                    $wpdb->insert($table_name, $setting);
                }
            }
            echo '<div class="updated"><p>Settings saved.</p></div>';
        } else {
            // Show an error message
            echo '<div class="notice notice-error"><p>Save failed: <code>' . $table_name . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
        }

    }

    // Retrieve current settings from database
    $settings = get_tidy_media_settings();

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

                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Components</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Core functions</label>
                                            </th>
                                            <td>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="checkbox" name="use_tidy_attachments"
                                                        id="use_tidy_attachments" value="1"
                                                        <?php checked($settings['use_tidy_attachments'], 1);?>>
                                                    Tidy post attachments
                                                    <p class="description">Post-attached images will be moved
                                                        to a folder structure that mirrors your content structure.
                                                        Attachment metadata will be updated.</p>
                                                </label>
                                                <br>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="checkbox" name="use_tidy_body_imgs"
                                                        id="use_tidy_body_imgs" value="1"
                                                        <?php checked($settings['use_tidy_body_imgs'], 1);?>>
                                                    Tidy body image URLs
                                                    <p class="description">Attachments of all local image URLs will be
                                                        moved to your custom folder structure. <code>src</code> in post
                                                        body will be
                                                        updated accordingly.</p>
                                                </label>
                                                <br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Other functions</label>
                                            </th>
                                            <td>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="checkbox" name="use_relative" id="use_relative"
                                                        value="1" <?php checked($settings['use_relative'], 1);?>>
                                                    Convert body image URLs from absolute to relative
                                                    <p class="description">In post content, any of your own images
                                                        called via absolute URLs (eg.
                                                        <code>&lt;img src="http://www.yourblog.com/wp-content/uploads/2023/03/image.jpeg"&gt;</code>)
                                                        will be replaced by a corresponding relative URL (eg.
                                                        <code>&lt;img src="/wp-content/uploads/2023/03/image.jpeg"&gt;</code>).
                                                    </p>
                                                </label>
                                                <br>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="checkbox" name="use_localise" id="use_localise"
                                                        value="1" <?php checked($settings['use_localise'], 1);?>>
                                                    Localise remote body images
                                                    <p class="description">In post content, all off-site images
                                                        will be pulled to your site. Applies to all
                                                        <code>&lt;img src=</code>
                                                        URLs except your own site and "additional home domains".
                                                        Organisation will be as per the
                                                        other settings.
                                                    </p>
                                                </label>
                                                <br>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="checkbox" name="use_delete" id="use_delete" value="1"
                                                        <?php checked($settings['use_delete'], 1);?>>
                                                    Delete attachments with posts
                                                    <p class="description">When a post is deleted, any attachments will
                                                        also be deleted. Only deletes if attachment is unused elsewhere.
                                                    </p>
                                                </label>
                                                <br>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Logging</label>
                                            </th>
                                            <td>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="checkbox" name="use_log" id="use_log" value="1"
                                                        <?php checked($settings['use_log'], 1);?>>
                                                    Log operations
                                                    <p class="description">Keep a log of all operations in
                                                        <code><?php echo plugin_dir_path(__FILE__); ?><a href="<?php echo plugins_url('/', __FILE__); ?>/wp-tidy-media.log" target="_new">wp-tidy-media.log</a></code>
                                                    </p>
                                                </label>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>


                        <!-- Post media folders -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Custom attachment filepath</h2>
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
                                                    <?php checked($settings['organize_post_img_by_type'], 1);?>
                                                    class="media-folder-input">
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
                                                        <?php checked($settings['organize_post_img_by_taxonomy'], '');?>
                                                        class="media-folder-input">
                                                    None
                                                </label><br>
                                                <?php
$taxonomies = get_taxonomies(array('public' => true));
    foreach ($taxonomies as $taxonomy) {
        ?>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="radio" name="organize_post_img_by_taxonomy"
                                                        value="<?php echo esc_attr($taxonomy); ?>"
                                                        <?php checked($settings['organize_post_img_by_taxonomy'], $taxonomy);?>
                                                        class="media-folder-input">
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
                                            <td><?php
if (get_option('uploads_use_yearmonth_folders') === '1') {
        echo "Yes";
    } else {
        echo "No";
    }?>
                                                (Set in <a href="<?php echo admin_url(); ?>options-media.php">Media
                                                    Settings</a>)</td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_post_slug">Organize by post
                                                    slug?</label>
                                            </th>
                                            <td>
                                                <input type="checkbox" name="organize_post_img_by_post_slug"
                                                    id="organize_post_img_by_post_slug" value="1"
                                                    <?php checked($settings['organize_post_img_by_post_slug'], 1);?>
                                                    class="media-folder-input"> (eg. <code>my-awesome-post</code>)
                                                <!--<p class="description">All uploads attached to posts will be housed in a
                                                    corresponding folder.</p>-->
                                            </td>
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
                                <h2>Make body <code>img src</code> URLs relative</h2>
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
                                                <p class="description">eg.
                                                    <strike><?php echo home_url(); ?></strike>/wp-content/uploads/path/to/image.jpeg
                                                </p>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Additional home domains</label>
                                            </th>
                                            <td>
                                                <input type="text" name="domains_to_replace" id="domains_to_replace"
                                                    size="75" value="<?php echo $settings['domains_to_replace']; ?>" />
                                                <p class="description">Separate multiple hostnames by comma (eg.
                                                    "http://www.oldsite.com, https://testsite:8080") - no trailing
                                                    slash.</p>
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
        var postSlugEnabled = document.querySelector('[name="organize_post_img_by_post_slug"]').checked;

        var path = basedir;

        if (postTypeEnabled) {
            path += '/<span style="color:#d63638">post_type</span>';
        }

        if (taxonomySlug && taxonomySlug.value !== '') {
            path += '/<span style="color:#00a32a">' + taxonomySlug.value +
                '</span>/<span style="color:#2271b1">term_slug</span>';
        }

        var uploadsUseYearMonthFolders =
            <?php echo get_option('uploads_use_yearmonth_folders') === '1' ? 'true' : 'false'; ?>;

        if (uploadsUseYearMonthFolders) {
            var today = new Date();
            var year = today.getFullYear();
            var month = today.getMonth() + 1;
            var dateFolders = year + '/' + (month < 10 ? '0' + month : month);
            path += '/' + dateFolders;
        }

        if (postSlugEnabled) {
            path += '/<span style="color:#dba617">my-awesome-post</span>';
        }

        path += '/image.jpeg';

        document.querySelector('#planned-path').innerHTML = path;
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('[name="organize_post_img_by_type"]').addEventListener('change',
            updatePlannedPath);
        document.querySelector('[name="organize_post_img_by_post_slug"]').addEventListener('change',
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

function tidy_media_enqueue_scripts()
{
    /**
     * Enqueue Posts Screen Scripts
     *
     * Enqueues the Tidy Media JavaScript and sets up localization for AJAX requests.
     * Only enqueues the script on the edit post screen or on any edit screen for a custom post type.
     *
     * @return void
     */
    $current_screen = get_current_screen();
    if ($current_screen->id === 'edit-post' || ($current_screen->base === 'edit' && isset($_GET['post_type']))) {
        wp_enqueue_script('tidy-media', plugin_dir_url(__FILE__) . 'js/tidy-media.js', array('jquery'), '1.0', true);
        wp_localize_script('tidy-media', 'tidy_media_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('tidy_media_nonce'),
        ));
    }
}
add_action('admin_enqueue_scripts', 'tidy_media_enqueue_scripts');

function tidy_media_add_button()
{
    /**
     * Add Tidy Media Button
     *
     * Adds the Tidy Media button to the post list table navigation if the user is on the edit post screen or on any edit screen for a custom post type.
     *
     * @return void
     */

    $current_screen = get_current_screen();
    if ($current_screen->id === 'edit-post' || ($current_screen->base === 'edit' && isset($_GET['post_type']))) {
        ?>
<div class="alignleft actions">
    <button id="tidy-media-button" class="button"><?php _e('Tidy Media', 'tidy-media');?></button>
</div>
<?php
}
}
add_action('manage_posts_extra_tablenav', 'tidy_media_add_button');

function tidy_media_ajax_handler()
{
/**
 * Button AJAX Handler
 *
 * Handles the AJAX request to tidy up media for selected posts.
 * Checks the nonce to ensure security and passes an array of post IDs to do_saved_post() function.
 *
 * @return void
 */

    check_ajax_referer('tidy_media_nonce', 'nonce');
    $post_ids = $_POST['post_ids'];
    foreach ($post_ids as $post_id) {
        do_saved_post($post_id);
    }
    die();
}
add_action('wp_ajax_tidy_media', 'tidy_media_ajax_handler');

function do_saved_post($post_id)
{
    /**
     * Catch Saved Posts.
     *
     * This function is triggered when a post is saved in WordPress. It checks whether the post is not a revision
     * and then proceeds to call the tidy_post_attachments function, passing in the post ID as a parameter.
     *
     * @param int $post_id The ID of the post being saved.
     * @return void
     */
    do_my_log("💾 do_saved_post() - " . $post_id . " " . get_post_field('post_type', $post_id) . ": " . get_the_title($post_id));

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
            do_my_log("🔚");

            // Retrieve current settings from database
            $settings = get_tidy_media_settings();

            // Core functions
            if ($settings['use_localise'] == 1) {
                localise_remote_images($post_id);
            }
            if ($settings['use_relative'] == 1) {
                relative_body_imgs($post_id);
            }
            if ($settings['use_tidy_body_imgs'] == 1) {
                tidy_body_imgs($post_id);
            }
            if ($settings['use_tidy_attachments'] == 1) {
                tidy_post_attachments($post_id);
            }
            // delete_attached_images_on_post_delete($post_id);
            do_my_log("🏁 Complete.");
            do_my_log("🔚");

        } else {
            // error: disallowed post type
        }

    }
}
add_action('save_post', 'do_saved_post', 10, 1);

function tidy_post_attachments($post_id)
{
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

    do_my_log('🧩 tidy_post_attachments()...');

    $post_attachments = get_attached_media('', $post_id);

    if ($post_attachments) {
        foreach ($post_attachments as $post_attachment) {

            do_my_log("🖼 Attachment " . $post_attachment->ID . " - " . $post_attachment->post_title);

            // Check file location, move if needed
            $move_attachment_outcome = custom_path_controller($post_id, $post_attachment);
            return $move_attachment_outcome;

        }
    } else {
        do_my_log("No attachments found.");
        return false;
    }

}

function tidy_body_imgs($post_id)
{
    /**
     * Fix Body Img Paths
     *
     * Fixes all local image URLs in the post's body to point to the expected location, based on the post's ID.
     * eg. Maybe /wp-content/uploads/image.jpeg should be /wp-content/uploads/post/taxonomy/term/image.jpeg
     *
     * When a malformed img src is found, function will:
     *  - Check if it exists in specified location form - move it and change the URL.
     *  - Check if it exists in intended location form  - just update URL to reflect.
     *
     * @param int $post_id The ID of the post whose body should be fixed.
     *
     * @return void
     */

    do_my_log("🧩 tidy_body_imgs()...");

    do_my_log("Getting post content...");
    // Get the post content
    $content = get_post_field('post_content', $post_id);

    do_my_log("Checking for local img src URLs...");
    // Find local URLs in the content - either 1) a relative URL or 2) begins with absolute home URL
    $pattern = '/<img[^>]+src=["\'](?:\/|\b' . preg_quote(home_url(), '/') . ')([^"\']+)/'; // <img src="/wp-content/uploads/... or <img src="https://example.com/wp-content/uploads/...
    preg_match_all($pattern, $content, $matches);
    do_my_log("👀 Local img URLs found: " . count($matches[0]));

    $num_tidied_in_body = 0;

    // For every src found,
    foreach ($matches[1] as $found_img_src) { // /wp-content/uploads/media/folio/clients/wired/tom_heather.jpg or https://example.com/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg

        $modified = null;

        // Get found file's details
        do_my_log("🌄 Found src attribute " . $found_img_src);
        if (strpos($found_img_src, home_url()) === 0) {
            // if URL is an absolute local URL, strip the domain part
            $found_img_src = preg_replace('/^' . preg_quote(home_url(), '/') . '/', '', $found_img_src);
        }
        $found_img_filepath = get_home_path() . ltrim($found_img_src, '/'); // /Users/robert/Sites/context.local/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg
        do_my_log("Filepath would be " . $found_img_filepath);
        $post_attachment = null;

        // ✅ File is where src says - move it and update body
        if (file_exists($found_img_filepath)) {

            do_my_log("File does exist at src. Getting its attachment object...");

            // Upload folder parts, used to generate attachment
            $uploads_base = trailingslashit(wp_upload_dir()['baseurl']); // http://context.local:8888/wp-content/uploads/
            $uploads_folder = str_replace(trailingslashit(home_url()), '', $uploads_base); // /wp-content/uploads/

            // Get file's attachment object
            $found_img_url = trailingslashit(get_site_url()) . $found_img_src; // http://context.local:8888/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg
            // Correct for double-slash that happens when an abolute URL was input
            $found_img_url = str_replace('//wp-content', '/wp-content', $found_img_url);
            // Remove the start to just work with a local child of /uploads/
            $img_path_no_base = str_replace($uploads_base, '', $found_img_url);

            do_my_log("Searching database _wp_attachment_metadata to find " . $img_path_no_base);
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
                $attachment_id = get_the_ID(); // 128824
                do_my_log("Found attachment ID " . $attachment_id . ".");
                wp_reset_postdata();
                $post_attachment = get_post($attachment_id); // WP_Post object: attachment

                if ($post_attachment) {
                    do_my_log("🖼 Found attachment object " . $post_attachment->ID . " - " . $post_attachment->post_title);

                    // 1. Check file location, move if needed
                    $move_attachment_outcome = custom_path_controller($post_id, $post_attachment);
                    if ($move_attachment_outcome === true) {

                        // 2. Update the body
                        do_my_log("Update the body...");
                        $new_image_details = new_image_details($post_id, $post_attachment);
                        $new_src = $uploads_folder . trailingslashit($new_image_details['subdir']) . $new_image_details['filename'];
                        // TODO: #13 https://github.com/robertandrews/wp-tidy-media/issues/13#issuecomment-1472329131
                        // $found_img_src = str_replace('/wp-content', 'wp-content', $found_img_src);
                        do_my_log("Replace " . $found_img_src . " with " . $new_src);
                        $new_content = str_replace($found_img_src, $new_src, $content, $num_replacements);
                        // do_my_log("✅ Replacements made: " . $num_replacements);
                        // If the content has changed, set the modified flag to true
                        if ($new_content !== $content) {
                            $modified = true;
                            $content = $new_content;
                            $num_tidied_in_body++;
                        }
                        // TODO: Should the save happen here, repeatedly, or outside?
                        if ($modified == true) { // was if ($new_content) {
                            do_my_log("Updating post...");
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
                        if ($post_attachment->post_parent === 0 || $post_attachment->post_parent === '') {
                            do_my_log("Image " . $attachment_id . " not attached to any post - attach it to this (" . $post_id . ").");
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

                    /*
                // Generate actual and intended path pieces, used for comparison
                $old_image_details = old_image_details($post_attachment);
                $new_image_details = new_image_details($post_id, $post_attachment);
                do_my_log("🔬 Comparing found " . $old_image_details['subdir'] . " vs user-specified pattern " . $new_image_details['subdir']);
                 */

                } else {
                    // No attachment found
                    do_my_log("Could not find attachment object.");
                }

            } else {
                // No attachment ID found
                do_my_log("❌ No attachment ID found.");
            }

            // TODO: Else: maybe it exists in the *right* place (so the body URL alone is wrong)...
            // $expected_filepath = trailingslashit(wp_upload_dir()['basedir']) . trailingslashit($new_image_details['subdir']) . basename($found_img_src);
            // $expected_filepath."\n";

        } else {
            // ❌ File is not even at src location                                  // /Users/robert/Sites/context.local/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg
            do_my_log("❌ File does not exist.");
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
    do_my_log("🧮 Tidied from body: " . $num_tidied_in_body);

    do_my_log("Finished tidy_body_imgs().");
    do_my_log("🔚");

    // print_r($content);

}

function relative_body_imgs($post_id)
{
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

    do_my_log("🧩 relative_body_imgs()...");

    // Get the post content
    $content = get_post_field('post_content', $post_id);
    $modified = false;

    // Set up list of domains to strip from links - site URL is added by default
    $settings = get_tidy_media_settings();
    $domains_to_replace = $settings['domains_to_replace'];
    $local_domains = array_map('trim', explode(",", $domains_to_replace));
    if (!in_array(get_site_url(), $local_domains)) {
        array_push($local_domains, get_site_url());
    }

    // For each domain we're removing
    $num_rel_changes = 0;
    foreach ($local_domains as $domain) {
        // do_my_log("Checking for any <img src=\"" . $domain . "...");

        // Find any strings like "<img src="http://www.domain.com"
        $pattern = '/<img[^>]*src=["\']' . preg_quote($domain, '/') . '(.*?)["\']/i';
        // Replace the leading portion only, ie. "<img src="{match}"
        $replacement = '<img src="$1"';
        // Perform the replacement
        $new_content = preg_replace_callback($pattern, function ($matches) use (&$num_rel_changes) {
            $num_rel_changes++;
            do_my_log("🌃 Found: " . $matches[0]);
            do_my_log("📝 Replacement: " . $matches[1]);
            return '<img src="' . $matches[1] . '"';
        }, $content);

        // If the content has changed, set the modified flag to true
        if ($new_content !== $content) {
            $modified = true;
            do_my_log("Changed a link.");
            $content = $new_content;
            // $num_rel_changes++;
        }
    }
    do_my_log("🧮 Relative URL conversions: " . $num_rel_changes++);

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
        do_my_log("✅ Post updated.");
        my_trigger_notice(4);
        // Hook it back up
        add_action('save_post', 'do_saved_post', 10, 1);
    } else {
        do_my_log("🚫 Post not updated.");
    }

    do_my_log("Finished relative_body_imgs().");
    do_my_log("🔚");

}

function localise_remote_images($post_id)
{
    /**
     * Localise Remote Images
     *
     * Slurps any remote images in a given post by downloading them to the media library
     * and updating the image src attribute to use a relative URL.
     *
     * @param int $post_id The ID of the post to be checked for remote images.
     *
     * @return void
     *
     * @throws Exception If there is an error downloading an image or updating the post.
     */

    do_my_log("🧩 localise_remote_images()...");

    $post_content = get_post_field('post_content', $post_id);
    $dom = new DOMDocument();
    @$dom->loadHTML($post_content);

    $image_tags = $dom->getElementsByTagName('img');

    $num_localised = 0;

    foreach ($image_tags as $image_tag) {
        $image_src = $image_tag->getAttribute('src');

        // "Remote" img i) starts with "http" but ii) not including http://www.yoursite.com
        if (strpos($image_src, 'http') === 0 && strpos($image_src, home_url()) === false) {

            do_my_log("🎆 Found img src " . $image_src);

            // Download the image file contents
            $image_data = file_get_contents($image_src);

            if ($image_data) {
                do_my_log("🛬 Downloaded file.");

                // Check if the downloaded file is an image
                $image_info = getimagesizefromstring($image_data);
                if (!$image_info) {
                    do_my_log("❌ Not an image.");
                    continue;
                }

                // Generate path info
                $image_info = pathinfo($image_src);
                $image_name = $image_info['basename'];
                // Generate uploads directory info
                $upload_dir = wp_upload_dir();
                $image_file = $upload_dir['path'] . '/' . $image_name;

                do_my_log("Save to " . $image_file);

                if (file_put_contents($image_file, $image_data) !== false) {

                    do_my_log("Saved file.");

                    // Get the post date of the parent post
                    $post_date = get_post_field('post_date', $post_id);
                    // Create attachment post object
                    do_my_log("Creating attachment for this...");
                    $attachment = array(
                        // TODO: Ensure the correct URL is used for guid
                        'guid' => $upload_dir['url'] . '/' . $image_name,
                        'post_title' => $image_name,
                        'post_mime_type' => wp_check_filetype($image_name)['type'],
                        'post_content' => '',
                        'post_status' => 'inherit',
                        'post_parent' => $post_id,
                        'post_date' => $post_date,
                    );
                    // Insert the attachment into the media library
                    $attach_id = wp_insert_attachment($attachment, $image_file, $post_id);

                    // Set the attachment metadata
                    do_my_log("Set attachment metadata...");
                    $attach_data = wp_generate_attachment_metadata($attach_id, $image_file);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    do_my_log("📎 Attachment created.");
                    $num_localised++;

                    // Replace the image src with the new attachment URL
                    do_my_log("📝 Replacing remote src with local URL " . wp_get_attachment_url($attach_id));
                    $image_tag->setAttribute('src', wp_get_attachment_url($attach_id));

                    // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                    remove_action('save_post', 'do_saved_post', 10, 1);
                    // Update the post content
                    $post_content = $dom->saveHTML();
                    wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));
                    do_my_log("✅ Updated post.");
                    // Hook it back up
                    add_action('save_post', 'do_saved_post', 10, 1);

                } else {
                    do_my_log("❌ File save failed.");
                }

            } else {
                do_my_log("❌ File download failed.");

            }

        } else {
            do_my_log("🚫 Image not remote - " . $image_src);
        }
    }
    do_my_log("🧮 Localised images: " . $num_localised);
    do_my_log("Finished localise_remote_images().");
    do_my_log("🔚");

}

function old_image_details($post_attachment)
{
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
    // TODO: Ensure the correct URL is used for guid
    $old_image['guid'] = $guid; // http://context.local:8888/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    // print_r($old_image);
    return $old_image;

}

function new_image_details($post_id, $post_attachment)
{
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
    // Get user's path preferences from database
    // TODO: Use get_tidy_media_settings() instead here...
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
        $organize_post_img_by_post_slug = isset($settings_arr['organize_post_img_by_post_slug']) ? $settings_arr['organize_post_img_by_post_slug'] : 0;
        // These will formulate the preferred path
    } else {
        // No database settings
        do_my_log("Could not get database settings.");
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
    } else {
        $new_subdir = '';
        $new_subdir_stem = '';
    }
    // c. Are date-folders in use?
    $wp_use_date_folders = get_option('uploads_use_yearmonth_folders');
    if ($wp_use_date_folders == 1) {
        $post_date = get_post_field('post_date', $post_id);
        $formatted_date = date('Y/m', strtotime($post_date));
        if (!empty($new_subdir)) {
            $new_subdir .= '/' . $formatted_date;
        } else {
            $new_subdir .= $formatted_date;
        }
    }
    // new subdir is now generated

    // d. Use post slug?
    if ($organize_post_img_by_post_slug == 1) {
        $post_slug = get_post_field('post_name', get_post($post_id));
        do_my_log("Org by post slug - " . $post_slug);

        if (!empty($new_subdir)) {
            $new_subdir .= '/' . $post_slug;
        } else {
            $new_subdir .= $post_slug;
        }
    }

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
    // TODO: Ensure the correct URL is used for guid
    $new_image['guid'] = trailingslashit(trailingslashit($upload_dir['baseurl']) . $subdir) . basename($filepath); // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    // print_r($new_image);
    return $new_image;

}

function custom_path_controller($post_id, $post_attachment)
{

    /**
     * Custom Path Controller
     *
     * Handles the logic for moving image files from one path to another and updating post and metadata.
     * Checks if a given attachment is in the folder intended by the user's specified custom format.
     * If it is not, the main file (plus any sized files and original file) are moved, and attachment
     * metadata is updated accordingly.
     * Files will not be moved if they are already attached to another post.
     *
     * @param int $post_id - The ID of the post to which the attachment belongs.
     * @param object $post_attachment - The attachment object to be moved.
     * @return bool - Returns a boolean value of true if the main file move is successful, otherwise false.
     */

    do_my_log("custom_path_controller()...");

    // Generate source and destination path pieces
    $old_image_details = old_image_details($post_attachment);
    // TODO: Failed to generate any custom-path details
    $new_image_details = new_image_details($post_id, $post_attachment);
    do_my_log("🔬 Comparing " . $old_image_details['filepath'] . " vs " . $new_image_details['filepath']);

    // Check if need to move
    if ($old_image_details['filepath'] == $new_image_details['filepath']) {
        do_my_log("👍🏻 Path ok, no need to move.");
        my_trigger_notice(3);
        return false;
        // Wrong location - move it, and update post and metadata
    } else {
        do_my_log("🚨 Path looks incorrect - " . $old_image_details['filepath']);

        // If image belongs to this post or is as yet unattached,
        if ($post_attachment->post_parent == $post_id || $post_attachment->post_parent == 0) {

            do_my_log("💡 File is not attached to any other post. Safe to move file and attach to this post (" . $post_id . ").");
            do_my_log("Move from " . $old_image_details['filepath'] . " to " . $new_image_details['filepath'] . "...");

            $move_main_file_success = move_main_file($post_attachment->ID, $old_image_details, $new_image_details);
            if ($move_main_file_success == true) {
                $move_sizes_files_success = move_sizes_files($post_attachment->ID, $old_image_details, $new_image_details);
                $move_original_file_success = move_original_file($post_attachment->ID, $old_image_details, $new_image_details);
            }
            return $move_main_file_success;

        } elseif ($post_attachment->post_parent !== $post_id && $post_attachment->post_parent !== 0 && $post_attachment->post_parent !== '') {
            do_my_log("🚫 Attachment already a child of " . $post_attachment->post_parent . " - " . get_the_title($post_attachment->post_parent) . " - Will not move.");
        }
    }

}

function move_main_file($attachment_id, $old_image_details, $new_image_details)
{
    /**
     * Move Media File
     *
     * Move the main image file from its old location to a new location and update related metadata in the WordPress database.
     *
     * @param int $attachment_id The ID of the attachment (i.e., the image).
     * @param array $old_image_details An associative array containing details of the image's old location (e.g., 'dirname', 'filepath', 'subdir', 'filename').
     * @return bool True if the move and database updates were successful, false otherwise.
     */

    do_my_log("🔧 move_main_file()...");

    // A. Move file
    // Get the WordPress uploads directory path
    $uploads_dir = wp_upload_dir();
    $uploads_dir_path = $uploads_dir['basedir']; // eg. /Users/robert/Sites/context.local/wp-content/uploads
    // Create the new sub-folder if it doesn't exist
    if (!file_exists($new_image_details['dirname'])) {
        do_my_log("Making directory " . $new_image_details['dirname']);
        wp_mkdir_p($new_image_details['dirname']);
    }
    // If folder now exists
    if (file_exists($new_image_details['dirname'])) {

        // If source file actually exists
        if (file_exists($old_image_details['filepath'])) {
            do_my_log("Source file exists at given location - " . $old_image_details['filepath']);

            // Move file
            do_my_log("Move to " . $new_image_details['filepath']);
            $result = rename($old_image_details['filepath'], $new_image_details['filepath']);

            if ($result) {
                do_my_log("✅ Moved: " . $result);
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
                // TODO: Ensure the correct URL is used for guid
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
                do_my_log("Database fields should now be updated.");
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

function move_sizes_files($attachment_id, $old_image_details, $new_image_details)
{
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

    do_my_log("🔧 move_sizes_files() - " . $attachment_id . "...");

    // Get the _wp_attachment_metadata serialised array
    $attachment_metadata = wp_get_attachment_metadata($attachment_id);

    // Any [sizes]?
    $success = false;
    if (isset($attachment_metadata['sizes'])) {
        do_my_log("Metadata has [sizes]. Number of sizes: " . count($attachment_metadata['sizes']));
        $num_sizes = 0;
        foreach ($attachment_metadata['sizes'] as $size => $data) {
            do_my_log("📐 Size: " . $data['file']);
            // A. Move files
            // Generate the old and new filepaths for size variants
            $old_size_filename = trailingslashit($old_image_details['dirname']) . $data['file'];
            $new_size_filename = trailingslashit($new_image_details['dirname']) . $data['file'];
            // Do the move
            $result = rename($old_size_filename, $new_size_filename);
            // do_my_log("Move result: " . $result);
            if ($result) {
                // Great
                $num_sizes++;
                do_my_log("✅ Moved " . $data['file']);
                $success = true;
            } else {
                // Error
                $success = false;
                do_my_log("❌ Failed to move" . $data['file']);
            }
            // B. Update database
            // No metadata to update - [sizes] filenames do not contain folders, only filenames.
        }
        do_my_log("🧮 Sizes handled: " . $num_sizes);
        // I want to access $success here
        return $success;
    } else {
        // No sizes here
        do_my_log("No [sizes].");
        return $success;
    }

}

function move_original_file($attachment_id, $old_image_details, $new_image_details)
{
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
                do_my_log("✅ Moved " . $old_original_filename . " to " . $new_original_filename);
            } else {
                // Move failed
                do_my_log("❌ Move failed.");
            }
            // echo $result;
        } else {
            // Old original not found.
            do_my_log("❌ File not found.");
        }

    } else {
        // "[original_image] not found";
        do_my_log("No [original_image].");
    }

}

function delete_attached_images_on_post_delete($post_id)
{
    /**
     * Remove Attachments On Post Delete
     *
     * Deletes all attached images for a given post when it is deleted.
     * This function is triggered by the before_delete_post action hook and checks if the post being deleted
     * is in the trash and if the delete request is coming from the WordPress admin panel. It then checks if any
     * of the images attached to the post are used by another post. If the image is not used by any other post, it
     * deletes the image and its associated metadata from the file system and the WordPress database. If the directory
     * containing the image is empty after the deletion, it is also deleted.
     * @param int $post_id The ID of the post being deleted.
     * @return void
     */

    // Retrieve current settings from database
    $settings = get_tidy_media_settings();
    if ($settings['use_delete'] == 1) {

        if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
            if (!isset($_REQUEST['delete_all']) && !wp_check_post_lock($post_id)) {
                $post = get_post($post_id);
                if ($post->post_status == 'trash') {

                    do_my_log("🗑 delete_attached_images_on_post_delete()...");

                    $current_screen = get_current_screen();
                    $screen_id = $current_screen ? $current_screen->id : '';

                    do_my_log("Screen ID: " . $screen_id);
                    $attachments = get_attached_media('', $post_id);
                    do_my_log("Attachments: " . count($attachments));

                    foreach ($attachments as $attachment) {
                        // Check if the image is used by another post
                        do_my_log("Check if image is used by another post.");
                        $used_by_other_post = false;
                        $attachment_id = $attachment->ID;
                        $attachment_post_id = $attachment->post_parent;
                        $post_type = get_post_type($attachment_post_id);
                        if ($post_type == 'post') {
                            $other_attachments = get_attached_media('', $attachment_post_id);
                            foreach ($other_attachments as $other_attachment) {
                                if ($other_attachment->ID != $attachment_id) {
                                    $used_by_other_post = true;
                                    break;
                                }
                            }
                            if (!$used_by_other_post) {
                                $args = array(
                                    'post_type' => 'post',
                                    'post_status' => 'publish',
                                    'posts_per_page' => -1,
                                    'fields' => 'ids',
                                );
                                $posts = get_posts($args);
                                foreach ($posts as $post) {
                                    $content = get_post_field('post_content', $post);
                                    $attachment_meta = get_post_meta($attachment_id, '_wp_attached_file', true);
                                    if (strpos($content, $attachment_meta) !== false) {
                                        $used_by_other_post = true;
                                        do_my_log("Image in use by another post. Will not delete.");
                                        break;
                                    }
                                }

                            }
                        } else {
                            $used_by_other_post = true;
                            do_my_log("Image in use by another post. Will not delete.");
                        }
                        if (!$used_by_other_post) {
                            // Delete the image if it is not used by another post
                            $attachment_path = get_attached_file($attachment_id);
                            do_my_log("Image " . $attachment_path . " only used by this post. Will be deleted.");
                            $metadata = wp_get_attachment_metadata($attachment_id);
                            foreach ($metadata['sizes'] as $size => $value) {
                                $file = $metadata['sizes'][$size]['file'];
                                $path = dirname($attachment_path) . '/' . $file;
                                do_my_log("Size for deletion: " . $path);
                                unlink($path);
                            }
                            if (isset($metadata['original_image'])) {
                                $path = dirname($attachment_path) . '/' . $metadata['original_image'];
                                do_my_log("Original image for deletion: " . $path);
                                unlink($path);
                            }
                            wp_delete_attachment($attachment_id, true);
                            do_my_log("Deletion should be complete.");
                            // Delete the directory if it is empty
                            $dir = dirname($attachment_path);
                            if (is_dir($dir) && count(glob("$dir/*")) === 0) {
                                rmdir($dir);
                                do_my_log("Directory " . $dir . " deleted because it was empty.");
                            } else {
                                do_my_log("Directory " . $dir . " not empty, will not delete.");
                            }
                        }
                    }
                }
            } else {
                // Second time firing
            }
        }
    }
}
add_action('before_delete_post', 'delete_attached_images_on_post_delete');

function remove_save_post_on_trash()
{
    /**
     * Remove save_post On Trash
     *
     * Removes the 'save_post' action from the 'post.php' page when a post is trashed, and restores it when a post is untrashed.
     * This function is hooked to the 'admin_init' and 'untrash_post' actions. When a post is trashed, it removes the 'save_post'
     * action from the 'post.php' page, which is responsible for saving post data. When a post is untrashed, it restores the
     * 'save_post' action so that the post data can be saved again.
     *
     * @return void
     */
    global $pagenow;
    if ($pagenow === 'post.php' && isset($_GET['action']) && $_GET['action'] === 'trash') {
        remove_action('save_post', 'do_saved_post');
    }
}
add_action('admin_init', 'remove_save_post_on_trash');

function restore_save_post_on_untrash($post_id)
{
    /**
     * Restore save_post On Untrash
     *
     * Restores the 'save_post' action when a post is untrashed.
     * This function is triggered by the 'untrash_post' action hook and checks if the post being untrashed was previously
     * in the trash. If it was, it adds the 'save_post' action back to the 'post.php' page, allowing post data to be saved
     * again.
     *
     * @param int $post_id The ID of the post being untrashed.
     * @return void
     */

    $post_status = get_post_status($post_id);
    if ($post_status === 'trash') {
        add_action('save_post', 'do_saved_post');
    }
}
add_action('untrash_post', 'restore_save_post_on_untrash');

function my_trigger_notice($key = '')
{
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

function my_admin_notices()
{
    /**
     * Notify Post Moved.
     *
     * Displays an admin notice with a specific message based on the notice key provided in the URL parameter.
     *
     * @since 1.0.0
     *
     * @return void
     */

    if (!isset($_GET['notice_key'])) {
        return;
    }
    $notice_key = wp_unslash(sanitize_text_field($_GET['notice_key']));
    $all_notices = [
        1 => 'Moved attached image to preferred folder',
        2 => 'Could not move attached image to preferred folder',
        3 => 'Attached image already in preferred media path - not moved',
        4 => 'Converted src for local img/s from absolute to relative URL',
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
    } elseif ($notice_key == 4) {
        $notice_class = "success";
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