<?php
/*
Plugin Name: CWP Tidy Media
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

        // Pre-specify values
        $pre_specified_values = array(
            array('setting_name' => 'organize_post_img_by_type', 'setting_value' => '1'),
            array('setting_name' => 'use_tidy_attachments', 'setting_value' => '1'),
            array('setting_name' => 'use_tidy_body_media', 'setting_value' => '1'),
            array('setting_name' => 'use_relative', 'setting_value' => '1'),
            array('setting_name' => 'use_localise', 'setting_value' => '1'),
            array('setting_name' => 'use_delete', 'setting_value' => '1'),
            array('setting_name' => 'use_log', 'setting_value' => '1'),
            array('setting_name' => 'run_on_save', 'setting_value' => '1'),
            array('setting_name' => 'organize_term_attachments', 'setting_value' => '1'),
        );
        foreach ($pre_specified_values as $value) {
            $wpdb->insert($table_name, $value);
        }

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

    add_submenu_page(
        'upload.php', // Change the parent slug to 'upload.php' for Media menu
        'Tidy Media',
        'Tidy Media',
        'manage_options',
        'tidy-media-organizer-options',
        'tidy_media_organizer_options_page'
    );
}
add_action('admin_menu', 'tidy_media_organizer_admin_page');

function get_tidy_media_settings()
{
/**
 * Get Plugin  Settings.
 *
 * @global object $wpdb WordPress database access object.
 * @return array Returns an array containing tidy media settings. If the tidy media organizer table is not found, an empty array is returned.
 *
 * The returned array contains the following keys:
 * - organize_post_img_by_type: A boolean indicating whether to organize post images by type.
 * - organize_post_img_by_taxonomy: A string indicating the taxonomy to organize post images by.
 * - organize_post_img_by_post_slug: A boolean indicating whether to organize post images by post slug.
 * - domains_to_replace: A string containing domains to replace with the local site's URL.
 * - use_tidy_attachments: A boolean indicating whether to use the Tidy Attachments feature.
 * - use_tidy_body_media: A boolean indicating whether to use the Tidy Body Media feature.
 * - use_relative: A boolean indicating whether to use relative URLs.
 * - use_localise: A boolean indicating whether to localize URLs.
 * - use_delete: A boolean indicating whether to delete orphaned attachments.
 * - use_log: A boolean indicating whether to log Tidy Media Organizer activity.
 * - run_on_save: A boolean indicating whether to run Tidy Media Organizer on post save.
 */
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
        $use_tidy_body_media = isset($settings_arr['use_tidy_body_media']) ? $settings_arr['use_tidy_body_media'] : 0;
        $use_relative = isset($settings_arr['use_relative']) ? $settings_arr['use_relative'] : 0;
        $use_localise = isset($settings_arr['use_localise']) ? $settings_arr['use_localise'] : 0;
        $use_delete = isset($settings_arr['use_delete']) ? $settings_arr['use_delete'] : 0;
        $use_log = isset($settings_arr['use_log']) ? $settings_arr['use_log'] : 0;
        $run_on_save = isset($settings_arr['run_on_save']) ? $settings_arr['run_on_save'] : 0;
        $organize_term_attachments = isset($settings_arr['organize_term_attachments']) ? $settings_arr['organize_term_attachments'] : 0;

        return array(
            'organize_post_img_by_type' => $organize_post_img_by_type,
            'organize_post_img_by_taxonomy' => $organize_post_img_by_taxonomy,
            'organize_post_img_by_post_slug' => $organize_post_img_by_post_slug,
            'domains_to_replace' => $domains_to_replace,
            'use_tidy_attachments' => $use_tidy_attachments,
            'use_tidy_body_media' => $use_tidy_body_media,
            'use_relative' => $use_relative,
            'use_localise' => $use_localise,
            'use_delete' => $use_delete,
            'use_log' => $use_log,
            'run_on_save' => $run_on_save,
            'organize_term_attachments' => $organize_term_attachments,
        );
    } else {
        // Show an error message
        echo '<div class="notice notice-error"><p>Plugin issue: <code>' . $table_name . '</code> not found in database. Cannot store settings. Try reactivating the plugin.</p></div>';
        return array();
    }
}

function our_post_types()
{
/**
 * Our Post Types.
 *
 * Generates an array of post types which various other functions can use, eg. in post queries
 * and the main do_saved_post(). Strips out some built-in types like "attachment". Only uses
 * "post", "page" and any custom post types.
 *
 * @return array An array of available post types, with the default post types added back in
 */

    // Available post-like post types, strip out defaults
    $args = array(
        'public' => true,
        '_builtin' => false,
    );
    $post_types = get_post_types($args);
    // Add back 2x default post types - 'post' and 'page'
    array_push($post_types, 'post', 'page');

    return $post_types;

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
                array('setting_name' => 'use_tidy_body_media', 'setting_value' => isset($_POST['use_tidy_body_media']) ? sanitize_text_field($_POST['use_tidy_body_media']) : ''),
                array('setting_name' => 'use_relative', 'setting_value' => isset($_POST['use_relative']) ? sanitize_text_field($_POST['use_relative']) : ''),
                array('setting_name' => 'use_localise', 'setting_value' => isset($_POST['use_localise']) ? sanitize_text_field($_POST['use_localise']) : ''),
                array('setting_name' => 'use_delete', 'setting_value' => isset($_POST['use_delete']) ? sanitize_text_field($_POST['use_delete']) : ''),
                array('setting_name' => 'use_log', 'setting_value' => isset($_POST['use_log']) ? sanitize_text_field($_POST['use_log']) : ''),
                array('setting_name' => 'run_on_save', 'setting_value' => isset($_POST['run_on_save']) ? sanitize_text_field($_POST['run_on_save']) : ''),
                array('setting_name' => 'organize_term_attachments', 'setting_value' => isset($_POST['organize_term_attachments']) ? sanitize_text_field($_POST['organize_term_attachments']) : ''),
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
                                <h2>System settings</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tbody>
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


                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Post attachment settings</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Operation</label>
                                            </th>
                                            <td>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="checkbox" name="run_on_save" id="run_on_save" value="1"
                                                        <?php checked($settings['run_on_save'], 1);?>>
                                                    Run on every post save
                                                    <p class="description">Fires on <code>save_post</code> for these
                                                        types:
                                                        <?php
$post_types = our_post_types();

    echo implode(', ', array_map(function ($post_type) {
        return '<code>' . $post_type . '</code>';
    }, $post_types));
    ?>.
                                                    </p>
                                                </label>
                                            </td>
                                        </tr>
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
                                                    <input type="checkbox" name="use_tidy_body_media"
                                                        id="use_tidy_body_media" value="1"
                                                        <?php checked($settings['use_tidy_body_media'], 1);?>>
                                                    Tidy body media URLs
                                                    <p class="description">Attachments of all local image URLs will be
                                                        moved to your custom folder structure. <code>img src</code> and
                                                        <code>a href</code> in post
                                                        body will be
                                                        updated accordingly.
                                                    </p>
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
                                    </tbody>
                                </table>
                            </div>
                        </div>


                        <!-- Post media folders -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Post attachment custom filepath</h2>
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
    $post_types = our_post_types();
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


                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Term attachment settings</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_term_attachments">Operation</label>
                                            </th>
                                            <td>
                                                <label style="margin: 0.35em 0 0.5em!important; display: inline-block;">
                                                    <input type="checkbox" name="organize_term_attachments"
                                                        id="organize_term_attachments" value="1"
                                                        <?php checked($settings['organize_term_attachments'], 1);?>>
                                                    Tidy media attached to taxonomy terms
                                                    <p class="description">Attachments of any IDs found referenced in a
                                                        term's
                                                        meta fields will be organised in corresponding folders.</p>
                                                    <p class="description">Fires on <code>edit_term</code> for these
                                                        taxonomies:
                                                        <?php
// list taxonomies
    $taxonomies = get_taxonomies(array('public' => true));

    echo implode(', ', array_map(function ($taxonomy) {
        return '<code>' . $taxonomy . '</code>';
    }, $taxonomies));
    ?>.
                                                    </p>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label>Preview:</label>
                                            </th>
                                            <td>
                                                <div><?php echo esc_js(wp_upload_dir()['basedir']); ?>/<span
                                                        style="color:#d63638">taxonomy</span>/<span
                                                        style="color:#00a32a">taxonomy_slug</span>/image.jpeg</div>
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

    // Only run if:
    // - Post is not permanently deleted (ie. has a status)
    // - Post status is not in the trash/bin (so, it won't fire when it's subsequently Permanently Deleted)
    // - Post status is not auto-draft (ie. don't fire when auto-drafting)
    // - Post status is not auto-saved
    // - Post status is not a saved revision

    if (get_post_status($post_id) && !wp_is_post_autosave($post_id) && !wp_is_post_revision($post_id) && get_post_status($post_id) !== 'trash' && get_post_status($post_id) !== 'auto-draft') {

        // Only run on preferred post types
        $post_types = our_post_types();
        $my_post_type = get_post_type($post_id);
        if (in_array($my_post_type, $post_types)) {

            do_my_log("💾 do_saved_post() - " . $post_id . " " . get_post_field('post_type', $post_id) . ": " . get_the_title($post_id));

            // Retrieve current settings from database
            $settings = get_tidy_media_settings();

            // Core functions
            if ($settings['use_localise'] == 1) {
                localise_remote_images($post_id);
            }
            if ($settings['use_relative'] == 1) {
                relative_body_imgs($post_id);
            }
            if ($settings['use_tidy_body_media'] == 1) {
                tidy_body_media($post_id);
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

    // TODO: Why does this omit some featured images?
    $post_attachments = do_get_all_attachments($post_id);

    // $attachment_ids = implode(',', wp_list_pluck($post_attachments, 'ID'));
    // do_my_log("attach ids: ". $attachment_ids);

    // do_my_log(' thumbnail - ' . get_post_thumbnail_id($post_id));
    // $thumb_id = get_post_thumbnail_id($post_id);
    // $thumb_attachment = get_post($thumb_id);

    if ($post_attachments) {
        foreach ($post_attachments as $post_attachment) {

            do_my_log("🖼 Attachment " . $post_attachment->ID . " - " . $post_attachment->post_title);

            // Check file location, move if needed
            $move_attachment_outcome = custom_path_controller($post_id, $post_attachment);
            // return $move_attachment_outcome;

        }
    } else {
        do_my_log("No attachments found.");
        return false;
    }

}

function do_get_content_as_dom($content)
{

    if ($content) {
        // Set the encoding of the input HTML string
        $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
        // Create a new DOMDocument object
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        $dom->encoding = 'UTF-8';
        // Load the post content into the DOMDocument object
        $dom->loadHTML($content, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        return $dom;
    }
}

function tidy_body_media($post_id)
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

    do_my_log("🧩 tidy_body_media()...");

    // Get the post content
    $content = get_post_field('post_content', $post_id);

    if (!$content) {
        return;
    }

    $doc = do_get_content_as_dom($content);

    $targets = array(
        "img" => "src",
        "a" => "href",
    );

    foreach ($targets as $element => $attribute) {

        // Find all img tags in the post content
        $matched_elements = $doc->getElementsByTagName($element);

        $num_tidied_in_body = 0;

        // Loop through each img tag
        foreach ($matched_elements as $el_match) {

            $modified = null;

            // Get the src attribute of the img tag
            $found_attribute = $el_match->getAttribute($attribute);

            // If the src attribute is either 1) a relative URL or 2) absolute URL on this site
            if (preg_match('/^(\/|' . preg_quote(home_url(), '/') . ')/', $found_attribute)) {

                // If the src attribute is an absolute local URL, strip the domain part
                if (strpos($found_attribute, home_url()) === 0) {
                    $found_attribute = preg_replace('/^' . preg_quote(home_url(), '/') . '/', '', $found_attribute);
                }

                // Get the file path of the image
                $filepath = get_home_path() . ltrim($found_attribute, '/');

                // Get found file's details
                do_my_log("🌄 Found src attribute " . $found_attribute);

                $found_media_filepath = get_home_path() . ltrim($found_attribute, '/'); // /Users/robert/Sites/context.local/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg
                // do_my_log("Filepath would be " . $found_media_filepath);
                $post_attachment = null;

                // ✅ A) File is where src says - move it and update the body
                if (file_exists($found_media_filepath)) {

                    // do_my_log("File does exist at src. Getting its attachment object...");

                    $post_attachment = get_attachment_obj_from_filepath($found_attribute);

                    if ($post_attachment) {
                        do_my_log("🖼 Found attachment object " . $post_attachment->ID . " - " . $post_attachment->post_title);

                        // 1. Check file location, move if needed
                        $move_attachment_outcome = custom_path_controller($post_id, $post_attachment);
                        if ($move_attachment_outcome === true) {

                            // 2. Update the body
                            // do_my_log("Update the body...");
                            // Upload folder parts, used to generate attachment
                            $new_image_details = new_image_details($post_id, $post_attachment);

                            $uploads_base = trailingslashit(wp_upload_dir()['baseurl']); // http://context.local:8888/wp-content/uploads/
                            $uploads_folder = str_replace(trailingslashit(home_url()), '', $uploads_base); // /wp-content/uploads/

                            $settings = get_tidy_media_settings();
                            // Relative URL
                            if ($settings['use_relative'] == 1) {
                                $new_src = "/" . $uploads_folder . trailingslashit($new_image_details['subdir']) . $new_image_details['filename'];
                                // Absolute URL
                            } else {
                                $new_src = $uploads_base . trailingslashit($new_image_details['subdir']) . $new_image_details['filename'];
                            }

                            $el_match->setAttribute($attribute, $new_src);
                            $new_content = $doc->saveHTML();
                            // do_my_log("✅ Replacements made: " . $num_replacements);
                            // If the content has changed, set the modified flag to true
                            if ($new_content !== $content) {
                                $modified = true;
                                $content = $new_content;
                                $num_tidied_in_body++;
                            }
                            // TODO: Should the save happen here, repeatedly, or outside?
                            if ($modified == true) { // was if ($new_content) {
                                // do_my_log("Updating post...");
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

                    } else {
                        // No attachment found
                        do_my_log("Could not find attachment object.");
                    }

                    // ❌ B) File is not at given src - find it and use that
                } else {

                    do_my_log("❌ File does not exist at " . $found_media_filepath);
                    // Search for the file
                    $search_results = search_file(basename($found_media_filepath));
                    if ($search_results) {
                        do_my_log("🔍 " . basename($found_media_filepath) . " found at " . $search_results);
                        $poss_path = "/" . str_replace(get_home_path(), '', $search_results);
                        $found_attachment = get_attachment_obj_from_filepath($poss_path);
                        $new_attachment_url = wp_get_attachment_image_url($found_attachment->ID, 'full');
                        $settings = get_tidy_media_settings();

                        // $new_image_details = new_image_details($post_id, $post_attachment);

                        $uploads_base = trailingslashit(wp_upload_dir()['baseurl']); // http://context.local:8888/wp-content/uploads/
                        $uploads_folder = str_replace(trailingslashit(home_url()), '', $uploads_base); // /wp-content/uploads/

                        $settings = get_tidy_media_settings();
                        // Relative URL
                        if ($settings['use_relative'] == 1) {
                            $new_attachment_url = str_replace(trailingslashit(home_url()), '/', $new_attachment_url);

                            // Absolute URL
                        } else {
                            do_my_log("Absolute URL");
                            $new_attachment_url = $new_attachment_url;
                        }

                        $el_match->setAttribute($attribute, $new_attachment_url);
                        $new_content = $doc->saveHTML();
                        // do_my_log("✅ Replacements made: " . $num_replacements);
                        // If the content has changed, set the modified flag to true
                        if ($new_content !== $content) {
                            $modified = true;
                            $content = $new_content;
                            $num_tidied_in_body++;
                        }
                        // TODO: Should the save happen here, repeatedly, or outside?
                        if ($modified == true) { // was if ($new_content) {
                            // do_my_log("Updating post...");
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

                        do_my_log("Replaced " . $found_attribute . " with " . $new_attachment_url);

                    }
                }

            }
        }

        do_my_log("🧮 Tidied from body: " . $num_tidied_in_body);
        // do_my_log("Finished tidy_body_media().");
        // do_my_log("🔚");

    }

}

function search_file($filename)
{
    /**
     * Search For File
     *
     * Searches for a file in the WordPress uploads directory and its subdirectories.
     *
     * @param string $filename The name of the file to search for.
     * @return string|false The absolute path to the first occurrence of the file found, or false if the file was not found.
     */
    $wp_upload_dir = wp_upload_dir();
    $dirs = array($wp_upload_dir['basedir']);

    while (!empty($dirs)) {
        $dir = array_shift($dirs);
        $files = glob($dir . DIRECTORY_SEPARATOR . $filename);

        if (count($files) > 0) {
            return $files[0];
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $subdir) {
            $dirs[] = $subdir;
        }
    }

    return false;
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

    if ($content) {

        $new_content = $content;
        // Set starting vars
        $modified = false;
        $num_rel_changes = 0;

        // Set up list of domains to strip from links - site URL is added by default
        $settings = get_tidy_media_settings();
        $domains_to_replace = $settings['domains_to_replace'];
        $local_domains = array_map('trim', explode(",", $domains_to_replace));
        if (!in_array(get_site_url(), $local_domains)) {
            array_push($local_domains, get_site_url());
        }

        foreach ($local_domains as $domain) {

            // Set the encoding of the input HTML string
            $content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
            $new_content = mb_convert_encoding($new_content, 'HTML-ENTITIES', 'UTF-8');

            $doc = do_get_content_as_dom($content);

            // Get every img tag
            $images = $doc->getElementsByTagName('img');
            foreach ($images as $image) {
                // Get its src attribute
                $src = $image->getAttribute('src');
                // src starts with absolute url
                if (strpos($src, $domain) === 0) {
                    // Strip the domain part
                    $new_src = str_replace($domain, '', $src);
                    // Change the src in the content
                    $image->setAttribute('src', $new_src);
                    // Notice
                    do_my_log("Replaced: " . $src);
                    do_my_log("With: " . $new_src);
                    // Resave the content (to memory)
                    $new_content = $doc->saveHTML();
                    $num_rel_changes++;
                }
            }

            // If the content has changed,
            if ($new_content !== $content) {

                // do_my_log("Need to save post...");

                // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                remove_action('save_post', 'do_saved_post', 10, 1);
                // Re-save the post
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $new_content,
                ));
                do_my_log("✅ Post updated.");
                // Hook it back up
                add_action('save_post', 'do_saved_post', 10, 1);

                my_trigger_notice(4);

            }

        }
        do_my_log("🧮 Relative URL conversions: " . $num_rel_changes++);

    } else {
        // echo "Post ".$post_id." has no content.\n";
    }

    // do_my_log("Finished relative_body_imgs().");
    // do_my_log("🔚");

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

    if (!$post_content) {
        return;
    }

    $dom = do_get_content_as_dom($post_content);

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

                // do_my_log("Save to " . $image_file);

                if (file_put_contents($image_file, $image_data) !== false) {

                    do_my_log("Saved file to " . $image_file);

                    // Get the post date of the parent post
                    $post_date = get_post_field('post_date', $post_id);
                    // Create attachment post object
                    // ("Creating attachment for this...");
                    $attachment = array(
                        // TODO: Ensure the correct URL is used for guid
                        // 'guid' => $upload_dir['url'] . '/' . $image_name,
                        'post_title' => $image_name,
                        'post_mime_type' => wp_check_filetype($image_name)['type'],
                        'post_content' => '',
                        'post_status' => 'inherit',
                        'post_parent' => $post_id,
                        'post_date' => $post_date,
                        'post_date_gmt' => get_gmt_from_date($post_date),
                    );
                    // Insert the attachment into the media library
                    $attach_id = wp_insert_attachment($attachment, $image_file, $post_id);

                    // Set the attachment metadata
                    // do_my_log("Set attachment metadata...");
                    $attach_data = wp_generate_attachment_metadata($attach_id, $image_file);
                    wp_update_attachment_metadata($attach_id, $attach_data);

                    do_my_log("📎 Attachment created.");
                    $num_localised++;

                    // Replace the image src with the new attachment URL
                    // do_my_log("📝 Replacing remote src with local URL " . wp_get_attachment_url($attach_id));
                    $image_tag->setAttribute('src', wp_get_attachment_url($attach_id));

                    // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                    remove_action('save_post', 'do_saved_post', 10, 1);
                    // Update the post content
                    $post_content = $dom->saveHTML();
                    wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));
                    do_my_log("✅ Updated post body.");
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
    // do_my_log("Finished localise_remote_images().");
    // do_my_log("🔚");

}

function get_attachment_obj_from_filepath($found_img_src)
{
    /**
     * Get Attachment From Filepath
     *
     * Gets the attachment object for a file given its absolute URL path.
     *
     * @param string $found_img_src The absolute URL path of the file, e.g. /wp-content/uploads/post/client/ghost-foundation/2020/09/rafat-ali-skift.jpg.
     * @return WP_Post|void The WP_Post object representing the attachment, or void if the attachment ID was not found.
     */
    // echo "found_img_src is " . $found_img_src . "\n";

    // Upload folder parts, used to generate attachment
    $uploads_base = trailingslashit(wp_upload_dir()['baseurl']); // http://context.local:8888/wp-content/uploads/
    // echo "uploads_base is " . $uploads_base . "\n";
    $uploads_folder = str_replace(trailingslashit(home_url()), '', $uploads_base); // /wp-content/uploads/
    // echo "uploads_folder is " . $uploads_folder . "\n";

    // Get file's attachment object
    $found_img_url = trailingslashit(get_site_url()) . $found_img_src; // http://context.local:8888/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg
    // echo "found_img_url is " . $found_img_url . "\n";

    // Correct for double-slash that happens when an abolute URL was input
    $found_img_url = str_replace('//wp-content', '/wp-content', $found_img_url);
    // Remove the start to just work with a local child of /uploads/
    $img_path_no_base = str_replace($uploads_base, '', $found_img_url);
    // echo "img_path_no_base is " . $img_path_no_base . "\n";

    // Use DB metadata to find attachment object
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        // 'fields' => 'ids',
        'meta_query' => array(
            array(
                'value' => $img_path_no_base,
                'compare' => 'LIKE',
                'key' => '_wp_attached_file', // Was _wp_attachment_metadata - see https: //github.com/robertandrews/wp-tidy-media/issues/33
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

        // print_r($post_attachment);
        return $post_attachment;

    } else {
        // No attachment ID found
        do_my_log("❌ No attachment ID found.");
        // echo "No attachment ID obtainable from filepath - could not get attachment.";
    }

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

    $filepath = get_attached_file($post_attachment->ID); // TODO: Stop this happening on post deletion
    $upload_dir = wp_upload_dir();
    $subdir = str_replace($upload_dir['basedir'], '', dirname($filepath));
    $subdir = ltrim($subdir, '/');
    $guid = $post_attachment->guid; // TODO: Stop this happening on post deletion
    $url_abs = trailingslashit(wp_upload_dir()['baseurl']) . trailingslashit($subdir) . basename($filepath);
    $url_rel = str_replace(home_url(), '', $url_abs);

    $old_image = array();
    $old_image['filepath'] = $filepath; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $old_image['dirname'] = dirname($filepath); // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/
    $old_image['subdir'] = $subdir; // post/client/contentnext/2011/12
    $old_image['filename'] = basename($filepath); // netflix-on-tv-in-living-room-o.jpg
    // TODO: Ensure the correct URL is used for guid
    $old_image['guid'] = $guid; // http://context.local:8888/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $old_image['url_abs'] = $url_abs;
    $old_image['url_rel'] = $url_rel;
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

        if ($post_terms) {
            // Get the last term in the array to use as the current term
            $current_term = end($post_terms);

            // Get an array of the parent term IDs
            $parent_ids = get_ancestors($current_term->term_id, $organize_post_img_by_taxonomy);

            // Add the slugs of all the parent terms to the subdirectory
            foreach ($parent_ids as $parent_id) {
                $parent_term = get_term($parent_id, $organize_post_img_by_taxonomy);
                $new_subdir .= '/' . $parent_term->slug;
            }

            // Add the slug of the current term to the subdirectory
            $new_subdir .= '/' . $current_term->slug;

            // Set the stem to the subdirectory without the current term slug
            $new_subdir_stem = implode('/', array_slice(explode('/', $new_subdir), 0, -1));
        } else {
            $new_subdir .= '/' . 'misc';
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

        if (!empty($new_subdir)) {
            $new_subdir .= '/' . $post_slug;
        } else {
            $new_subdir .= $post_slug;
        }
    }

    $filepath = get_attached_file($post_attachment->ID);

    $upload_dir = wp_upload_dir();
    $subdir = $new_subdir;

    $url_abs = trailingslashit(wp_upload_dir()['baseurl']) . trailingslashit($subdir) . basename($filepath);
    $url_rel = str_replace(home_url(), '', $url_abs);

    // Populate bits of $new_image
    $new_image = array();
    $new_image['subdir'] = $subdir; // post/client/contentnext/2011/12
    $new_image['subdir_stem'] = $new_subdir_stem; // post/client/contentnext
    $new_image['filepath'] = trailingslashit(trailingslashit($upload_dir['basedir']) . $subdir) . basename($filepath); // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $new_image['dirname'] = trailingslashit($upload_dir['basedir']) . $subdir; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/
    $new_image['filename'] = basename($filepath); // netflix-on-tv-in-living-room-o.jpg
    // TODO: Ensure the correct URL is used for guid
    $new_image['guid'] = trailingslashit(trailingslashit($upload_dir['baseurl']) . $subdir) . basename($filepath); // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $new_image['url_abs'] = $url_abs;
    $new_image['url_rel'] = $url_rel;

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
    // do_my_log("🔬 Comparing " . $old_image_details['filepath'] . " vs " . $new_image_details['filepath']);

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

            // do_my_log("💡 File is not attached to any other post. Safe to move file and attach to this post (" . $post_id . ").");
            // do_my_log("Move from " . $old_image_details['filepath'] . " to " . $new_image_details['filepath'] . "...");

            $move_main_file_success = move_main_file($post_attachment->ID, $old_image_details, $new_image_details, $post_id);
            /*
            if ($move_main_file_success == true) {
            do_my_log("File was moved.");
            // TODO: Check and update any other posts
            update_body_img_urls($post_id, $post_attachment->ID, $old_image_details, $new_image_details);
            } else {
            do_my_log("File was NOT moved.");
            }
             */

            /*$move_sizes_files_success = */move_sizes_files($post_attachment->ID, $old_image_details, $new_image_details, $post_id);
            /*$move_original_file_success = */move_original_file($post_attachment->ID, $old_image_details, $new_image_details, $post_id);
            // }

            return $move_main_file_success;

        } elseif ($post_attachment->post_parent !== $post_id && $post_attachment->post_parent !== 0 && $post_attachment->post_parent !== '') {
            do_my_log("🚫 Attachment already a child of " . $post_attachment->post_parent . " - " . get_the_title($post_attachment->post_parent) . " - Will not move.");
        }
    }

}

function update_body_img_urls($post_id, $post_att_id, $old_image_details, $new_image_details)
{
/**
 * Update Body Image URLs
 *
 * When a move_* operation updates (moves) a post's image, any _other_ posts which also include the image via <img src...> will
 * find it becomes 404.
 * This function will check for any posts which embed the just-updated image at its previous URL, and will update that
 * URL to the new location.
 * This does not run on the post which instigated the move, ie the sole post which is the post_parent of the attachment,
 * since this should have already been updated by tidy_body_media().
 *
 * @param int $post_id The ID of the starting post.
 * @param int $post_att_id The ID of the attachment post for the starting post.
 * @param array $old_image_details An array containing the details of the old image to be replaced.
 * @param array $new_image_details An array containing the details of the new image to replace the old image.
 * @return void This function does not return a value.
 */

    do_my_log("🧩 update_body_img_urls()...");

    // 1. Get the old URL we just updated - relative and absolute forms
    // $old_image_details['url_rel']

    // 2. Do a post query for that string
    // do_my_log("looking for " . $old_image_details['url_rel']);
    $args = array(
        'post_type' => our_post_types(),
        'posts_per_page' => -1,
        'post__not_in' => array($post_id), // omit the starting post, which was already updated
        's' => $old_image_details['url_rel'],
    );
    $query = new WP_Query($args);

    // 3. Replace old string

    // The Loop
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // Get the post content
            $content = get_post_field('post_content', $post_id);

            $doc = do_get_content_as_dom($content);

            // Find all img tags in the post content
            $images = $doc->getElementsByTagName('img');

            foreach ($images as $img) {

                // Get the src attribute of the img tag
                $src = $img->getAttribute('src');

                // If old URL form is in the src
                if (strpos($src, $old_image_details['url_rel']) !== false) { // was: if ($src === $old_image_details['url_rel']) {

                    // do_my_log("Found old relative URL " . $src ." in '". get_post_field('post_title', get_the_ID()) ."'. Need to replace img src with ".$new_image_details['url_rel']);
                    $new_src = str_replace($old_image_details['url_rel'], $new_image_details['url_rel'], $src);
                    // do_my_log("New is ".$new_src);
                    do_my_log("Updating img src " . $src . " in '" . get_post_field('post_title', get_the_ID()) . "' to " . $new_image_details['url_rel']);

                    $img->setAttribute('src', $new_src);
                    $new_content = $doc->saveHTML();

                }
            }

            // Save the updated post, if updates occurred
            if ($new_content !== $content) {
                $modified = true;
                $content = $new_content;
            }
            if ($modified == true) { // was if ($new_content) {
                // do_my_log("Updating '". get_post_field('post_title', get_the_ID()) ."'");
                // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                remove_action('save_post', 'do_saved_post', 10, 1);
                // Re-save the post
                wp_update_post(array(
                    'ID' => get_the_ID(),
                    'post_content' => $content,
                ));
                // Hook it back up
                add_action('save_post', 'do_saved_post', 10, 1);
                // do_my_log("Done.");
            }

        }
    } else {
        // no posts found
        // do_my_log("👍🏻 No posts containing old URL.");
    }

}

function move_main_file($attachment_id, $old_image_details, $new_image_details, $post_id)
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

    // Ensure destination folder exists - if not, create it
    if (!file_exists($new_image_details['dirname'])) {
        wp_mkdir_p($new_image_details['dirname']);
    }
    if (file_exists($new_image_details['dirname'])) {

        // If source file actually exists
        if (file_exists($old_image_details['filepath'])) {

            // do_my_log("Move from: " . $old_image_details['filepath']);
            do_my_log("Move to: " . $new_image_details['filepath']);

            $source_dir = $old_image_details['dirname'];
            $target_dir = $new_image_details['dirname'];
            // define the filename to move
            $filename = $old_image_details['filename'];

            if (file_exists($target_dir . '/' . $filename)) {
                // if the file already exists, generate a unique filename for the moving file
                $unique_filename = wp_unique_filename($target_dir, $filename);
                // move the file to the target directory using the unique filename
                $result = rename($source_dir . '/' . $filename, $target_dir . '/' . $unique_filename);
                // update the new_image_details filename
                $new_image_details['filename'] = $unique_filename;
            } else {
                // if the file doesn't already exist, move the file to the target directory with the original filename
                $result = rename($source_dir . '/' . $filename, $target_dir . '/' . $filename);
            }

            // Move the file
            // $result = rename($old_image_details['dirname'] . '/' . $old_image_details['filename'], $target_dir = $new_image_details['dirname'] . '/' . $unique_filename);

            if ($result) {
                do_my_log("✅ Moved: " . $result);

                // B. Update database

                // Update database #1 - Set attachment date to post's date (if post_id was passed)
                do_my_log("Updating DB #1: wp_update_post to update the post's own post_date," . get_post_field('post_date', $post_id));
                if ($post_id) {
                    $post_date = get_post_field('post_date', $post_id);
                    wp_update_post(array(
                        'ID' => $attachment_id,
                        'post_date' => $post_date,
                        'post_date_gmt' => get_gmt_from_date($post_date),
                    ));
                }

                // Update database #2 - image wp_postmeta, _wp_attached_file (eg. post/client/clarity/2018/06/146343_photo-1486312338219-ce68d2c6f44d-4959-art.jpe)
                do_my_log("Updating DB #2: update_post_meta to update wp_postmeta _wp_attached_file to " . trailingslashit($new_image_details['subdir']) . $new_image_details['filename']);
                update_post_meta($attachment_id, '_wp_attached_file', trailingslashit($new_image_details['subdir']) . $new_image_details['filename']);

                // Update database #3 - image wp_postmeta, _wp_attachment_metadata (eg. [file] => post/client/clarity/2018/06/146343_photo-1486312338219-ce68d2c6f44d-4959-art.jpe)
                do_my_log("Updating DB #3: wp_update_attachment_metadata to update [file] location in wp_postmeta _wp_attachment_metadata to " . trailingslashit($new_image_details['subdir']) . $new_image_details['filename']);
                $attachment_metadata = wp_get_attachment_metadata($attachment_id);
                $attachment_metadata['file'] = trailingslashit($new_image_details['subdir']) . $new_image_details['filename'];
                wp_update_attachment_metadata($attachment_id, $attachment_metadata);

                // Update database #4 - image wp_posts, guid - does not alter hostname part
                /*do_my_log("Updating DB #4: wp_update_post to update wp_posts guid");
                $old_guid_full = get_post_field('guid', $attachment_id);
                // TODO: Ensure the correct URL is used for guid
                $new_guid_full = str_replace($old_image_details['subdir'], $new_image_details['subdir'], $old_image_details['guid']);
                global $wpdb;*/
                /*
                $wpdb->update(
                $wpdb->posts,
                // array('guid' => $new_guid_full),
                array('ID' => $attachment_id),
                array('%s'),
                array('%d')
                );
                 */
                /*
                $wpdb->update(
                $wpdb->posts,
                array('ID' => $attachment_id),
                array('ID' => $attachment_id),
                array('%d'),
                array('%d')
                );
                 */

                do_my_log("wp_update_post some some reason");
                wp_update_post(array(
                    'ID' => $attachment_id,
                ));

                my_trigger_notice(1);
                do_my_log("Database fields should now be updated.");
                // If this was a post, update any body image URLs
                if ($post_id) {
                    update_body_img_urls($post_id, $attachment_id, $old_image_details, $new_image_details);
                }

                return true;
            } else {
                my_trigger_notice(2);
                do_my_log("❌ Moved failed");
                return false;
            }
        } else {
            do_my_log("❌ File does not exist.");
        }
    } else {
        my_trigger_notice(2);
        do_my_log("❌ Folder does not exist.");

        return false;
    }

}

function move_sizes_files($attachment_id, $old_image_details, $new_image_details, $post_id)
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

    $source_dir = $old_image_details['dirname'];
    $target_dir = $new_image_details['dirname'];
    // define the filename to move
    $filename = $old_image_details['filename'];

    // Get attachment metadata
    $attachment_metadata = wp_get_attachment_metadata($attachment_id);

    if (isset($attachment_metadata['sizes'])) {

        // Iterate through each size and generate a unique filename for it
        foreach ($attachment_metadata['sizes'] as $size => $size_info) {
            // Get the original size-specific filename
            $size_filename = $size_info['file'];

            // Check if a file with the same size-specific filename already exists in the target directory
            if (file_exists($target_dir . '/' . $size_filename)) {
                // Generate a unique filename using wp_unique_filename()
                $unique_size_filename = wp_unique_filename($target_dir, $size_filename);
            } else {
                $unique_size_filename = $size_filename;
            }

            // if source file exists
            if (file_exists($source_dir . '/' . $size_filename)) {

                // Move the file to the target directory with the unique filename
                $result = rename($source_dir . '/' . $size_filename, $target_dir . '/' . $unique_size_filename);
                if ($result) {
                    do_my_log("✅ Moved " . $source_dir . '/' . $size_filename . " to " . $target_dir . '/' . $unique_size_filename);
                    // Update attachment metadata with new file name
                    $attachment_metadata['sizes'][$size]['file'] = $unique_size_filename;
                    wp_update_attachment_metadata($attachment_id, $attachment_metadata);
                } else {
                    do_my_log("❌ Failed to move " . $size_filename . " to " . $unique_size_filename);
                }

            }
        }
    }

    do_my_log("🧮 Sizes done. ");

    // return !empty($moved_files);
}

function move_original_file($attachment_id, $old_image_details, $new_image_details, $post_id)
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
    do_my_log("🔧 move_original_image_file() - " . $attachment_id . "...");

    // Get attachment metadata
    $attachment_metadata = wp_get_attachment_metadata($attachment_id);

    if (isset($attachment_metadata['original_image'])) {

        $source_dir = $old_image_details['dirname'];
        $target_dir = $new_image_details['dirname'];

        // Get the original image filename from the metadata
        $filename = $attachment_metadata['original_image'];

        if (file_exists($source_dir . '/' . $filename)) {

            // Check if a file with the same filename already exists in the target directory
            if (file_exists($target_dir . '/' . $filename)) {
                // Generate a unique filename using wp_unique_filename()
                $unique_filename = wp_unique_filename($target_dir, $filename);
            } else {
                $unique_filename = $filename;
            }

            // Move the original image file to the target directory with the unique filename
            $result = rename($source_dir . '/' . $filename, $target_dir . '/' . $unique_filename);
            if ($result) {
                do_my_log("✅ Moved " . $source_dir . '/' . $filename . " to " . $target_dir . '/' . $unique_filename);
                // Update attachment metadata with new file name
                $attachment_metadata['original_image'] = $unique_filename;
                wp_update_attachment_metadata($attachment_id, $attachment_metadata);
            } else {
                do_my_log("❌ Failed to move " . $filename . " to " . $unique_filename);
            }

            do_my_log("🧮 Original image done.");

        } else {
            do_my_log("❌ Original image file not found.");
        }

    } else {
        do_my_log("❌ No original image to move.");
    }

}

function do_delete_attachment($attachment_id)
{
/**
 * Delete Attachment
 *
 * Cleverly uses the kitchen sink to delete all traces of an attachment. WordPress has no single way
 * to do this. So the function:
 * - Finds and deletes [sizes] of attachment, via auxillary function.
 * - Deletes attachment files.
 * - Deletes attachment metadata.
 * - Deletes attachment's directory if it becomes empty.
 *
 * @param int $attachment_id The ID of the attachment to be deleted.
 * @return void
 */
    do_my_log("do_delete_attachment()");

    // Check if the attachment exists
    if (!wp_attachment_is_image($attachment_id) && !get_post($attachment_id)) {
        return;
    }

    // Get directory before the file is gone
    $attachment_path = get_attached_file($attachment_id);
    $dir = dirname($attachment_path);

    // Delete [sizes] via custom function
    do_delete_img_sizes($attachment_id);

    // Delete the physical files associated with the attachment
    wp_delete_attachment_files($attachment_id, null, null, null);

    // Delete the attachment and its metadata from the database
    wp_delete_attachment($attachment_id, true);

    // Delete directory if it's empty
    if (is_dir($dir) && count(glob("$dir/*")) === 0) {
        rmdir($dir);
        do_my_log("Directory " . $dir . " deleted because it was empty.");
    } else {
        do_my_log("Directory " . $dir . " not empty, will not delete.");
    }

}

function do_delete_img_sizes($attachment_id)
{
    /**
     * Delete Image Sizes
     *
     * Deletes all image size variants associated with a given attachment ID.
     *
     * @@param int $attachment_id The ID of the attachment whose image sizes should be deleted.
     * @return void
     */
    // Get all image size variants associated with the attachment
    $image_sizes = get_intermediate_image_sizes();
    $image_sizes[] = 'full'; // include the original image size as well
    $attachment_meta = wp_get_attachment_metadata($attachment_id);

    if (!empty($attachment_meta['sizes'])) {
        foreach ($attachment_meta['sizes'] as $size => $size_info) {
            if (in_array($size, $image_sizes)) {
                $image_sizes[] = $size;
            }
        }
    }

    // Delete each image size variant
    foreach ($image_sizes as $size) {
        $image_data = wp_get_attachment_image_src($attachment_id, $size);
        if ($image_data) {
            $image_path = $image_data[0];
            if (file_exists($image_path)) {
                wp_delete_attachment_file($attachment_id, null, true);
            }
        }
    }
}

function is_attachment_used_elsewhere($attachment_id, $main_post_id)
{
    /**
     * Is Attachment Used Elsewhere?
     *
     * Determines if an attachment of a particular post is used in any other posts.
     * This function checks whether the attachment is being used in another post's content or as a featured image.
     * If the attachment is used elsewhere, the function returns true, otherwise it returns false.
     *
     * @param int $attachment_id The ID of the attachment to check.
     * @param int $main_post_id The ID of the post where the attachment is currently being used.
     * @return bool True if the attachment is used elsewhere, false otherwise.
     */

    do_my_log("is_attachment_used_elsewhere()");

    $main_post = get_post($main_post_id);
    $attachment = get_post($attachment_id);
    $old_image_details = old_image_details($attachment);

    // Check 1: URL in body content
    $args_attach = array(
        'post_type' => our_post_types(),
        'posts_per_page' => -1,
        'post__not_in' => array($main_post_id), // omit the starting post, which was already updated
        's' => $old_image_details['url_rel'],
    );
    $query_attach = new WP_Query($args_attach);
    do_my_log("Attachment: Found " . $query_attach->found_posts . " other posts with this as attachment");
    if ($query_attach->found_posts > 0) {
        return true;
    }

    // Check 2: used as thumbnail elsewhere
    $args_thumb = array(
        'post_type' => our_post_types(), // Replace with the post type you want to search in
        'meta_key' => '_thumbnail_id',
        'meta_value' => $attachment_id,
        'post__not_in' => array($main_post_id), // omit the starting post, which was already updated
        'posts_per_page' => -1, // Retrieve all matching posts
    );
    $posts_with_featured_image = new WP_Query($args_thumb);
    do_my_log("Thumbnail: Found " . $posts_with_featured_image->found_posts . " other posts with this as thumbnail");
    if ($posts_with_featured_image->have_posts()) {
        return true;
    }

}

function do_get_all_attachments($post_id)
{
    /**
     * Get All Attachments
     *
     * Clever function to get a combined array of *all* attachments associated with a post.
     * WordPress is limited in this regard. While a featured image is stored against a post in WP_Posts
     * with _thumbnail_id, in-line use of media may not be recorded in those items because an
     * attachment can only attach to a single post.
     * This function gets a) any featured image and b) any attachments inserted into body content.
     * The result is combined.
     *
     * @param int $post_id The ID of the post to search for attachments.
     * @return array|null An array of attachment objects if attachments are found, or null if none are found.
     */

    $attachments = array();

    // Get items in post content
    $content = get_post_field('post_content', $post_id);

    if (!$content) {
        return;
    }

    $doc = do_get_content_as_dom($content);

    $images = $doc->getElementsByTagName('img');
    foreach ($images as $img) {
        $src = $img->getAttribute('src');
        $inline_attachment = get_attachment_obj_from_filepath($src);
        if ($inline_attachment) {
            $attachments[] = $inline_attachment;
        }
    }

    // Get the featured image ID
    $featured_image_id = get_post_thumbnail_id($post_id);
    $featured_img_obj = get_post($featured_image_id);
    if ($featured_img_obj) {
        $attachments[] = $featured_img_obj;
    }

    // Combine, deduplicate and return
    if ($attachments) {
        $attachments_unique = deduplicate_by_key($attachments, "ID");
        return $attachments_unique;
    }

}

function deduplicate_by_key($array, $key)
{
/**
 * Deduplicate Array Items By Key
 *
 * This function deduplicates an array of objects based on a specified object property.
 * Designed so that an array of post items does not carry a post item twice, using the ID field.
 *
 * It iterates over the array and checks if each object's specified property is already in the temporary array.
 * If the property value is not found, it is added to the temporary array and the object is added to the result array.
 * If the property value is found, the object is not added to the result array.
 * Finally, the result array is returned, containing only unique objects.
 *
 * @param array $array The input array of objects to deduplicate.
 * @param string $key The name of the property to check for duplication.
 * @return array The array of objects with duplicates removed.
 */
    $temp_array = array();
    $result_array = array();

    foreach ($array as $item) {
        if (!isset($temp_array[$item->$key])) {
            $temp_array[$item->$key] = true;
            $result_array[] = $item;
        }
    }

    return $result_array;
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

        // Log the entire $_REQUEST array
        /*
        do_my_log("Request: " . print_r($_REQUEST, true));
        do_my_log("Request URI: " . $_SERVER['REQUEST_URI']);
         */

        // if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
        if ((isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') || (isset($_REQUEST['delete_all']) && ($_REQUEST['delete_all'] == 'Empty Bin' || $_REQUEST['delete_all'] == 'Empty Trash'))) {

            // if (!isset($_REQUEST['delete_all']) && !wp_check_post_lock($post_id)) {

            $post = get_post($post_id);

            // When permanently deleting an attachment...
            if ($post->post_status == 'trash') {

                do_my_log("🗑 delete_attached_images_on_post_delete()...");

                $current_screen = get_current_screen();
                $screen_id = $current_screen ? $current_screen->id : '';
                do_my_log("Screen ID: " . $screen_id);

                // Get post's attachments and featured image
                $attachments = do_get_all_attachments($post_id);

                if (is_array($attachments) || is_object($attachments)) {

                    do_my_log("Attachments: " . count($attachments));

                    foreach ($attachments as $attachment) {
                        do_my_log("Checking " . $attachment->ID);
                        $used_elsewhere = is_attachment_used_elsewhere($attachment->ID, $post->ID);
                        // TODO: Only if URL not found in other posts
                        if ($used_elsewhere !== true) {
                            do_my_log("Will delete attachment with post");
                            do_delete_attachment($attachment->ID);
                        } else {
                            do_my_log("Attachment used elsewhere. Will not delete.");
                        }
                    }
                }
            }
            /*
        } else {
        // Second time firing
        }
         */
        }
    }
}
add_action('before_delete_post', 'delete_attached_images_on_post_delete');

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

// Support taxonomy term file checking on edit_term hook, if user has enabled it
$settings = get_tidy_media_settings();
/*
if ($settings['organize_term_attachments'] == 1) {
add_action('edit_term', 'do_edit_term', 10, 3);
add_action('create_term', 'do_edit_term', 10, 3);

}
 */
function do_edit_term($term_id, $tt_id, $taxonomy)
{
    /**
     * Check Edited Term
     *
     * @param int $term_id The ID of the term to edit.
     * @param int $tt_id The taxonomy term ID.
     * @param string $taxonomy The taxonomy name.
     * @return void
     */

    do_my_log("do_edit_term()");

    // Get served term and its meta
    $term = get_term($term_id, $taxonomy);
    $term_meta = get_term_meta($term_id);

    // Go through term's wp_termmeta records, looking for attachment IDs
    foreach ($term_meta as $key => $value) {
        // do_my_log("Term Meta: " . $key . " = " . $value[0]);
        // if value 0 is an integer
        // TODO: Functionalise this repeating block
        if (is_numeric($value[0])) {
            $number_found = $value[0];
            do_my_log($key . " value is numeric: " . $number_found);
            // If the number corresponds to an actual attachment, assess the file for moving
            if (check_id_for_attachment($number_found)) {
                do_my_log("Found attachment against " . $key . " - " . $number_found);
                $attachment_path = get_attached_file($number_found);
                $post_attachment = get_post($number_found);
                term_img_move_controller($post_attachment, $term, $key);
            }
        } else {
            // do_my_log($key . " value is not numeric");
            if (is_serialized($value[0])) {
                do_my_log($key . " value is serialized");
                // unserialise it
                $unserialized_meta_value = unserialize($value[0]);
                foreach ($unserialized_meta_value as $key2 => $value2) {
                    // do_my_log("Unserialized Meta: " . $key2 . " = " . $value2);
                    // When single photos is used, CMB2 stores its ID as a value
                    // TODO: Functionalise this repeating block
                    if (is_numeric($value2)) {
                        $number_found = $value2;
                        do_my_log($key2 . " value is numeric: " . $number_found);
                        // If the number corresponds to an actual attachment, assess the file for moving
                        if (check_id_for_attachment($number_found)) {
                            do_my_log("Found attachment against " . $key2 . " - " . $number_found);
                            $attachment_path = get_attached_file($number_found);
                            $post_attachment = get_post($number_found);
                            term_img_move_controller($post_attachment, $term, $key);
                        }
                    }
                    // When multiple photos are used, CMB2 stores their IDs as keys, with URLs as values
                    // TODO: Functionalise this repeating block
                    if (is_numeric($key2)) {
                        $number_found = $key2;
                        do_my_log($key2 . " value is numeric: " . $number_found);
                        // If the number corresponds to an actual attachment, assess the file for moving
                        if (check_id_for_attachment($number_found)) {
                            do_my_log("Found attachment against " . $key2 . " - " . $number_found);
                            $attachment_path = get_attached_file($number_found);
                            $post_attachment = get_post($number_found);
                            term_img_move_controller($post_attachment, $term, $key);
                        }
                    }

                }

            }
        }

    }

}

function check_id_for_attachment($number_found)
{
    /**
     * Check If ID Is Attachment
     *
     * @param int $number_found The ID to check
     * @return bool True if the ID is an attachment; false otherwise.
     */

    do_my_log("check_id_for_attachment()");

    // check if an attachment (post of type 'attachment') exists with $number_found as its ID
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'post__in' => array($number_found),
    );
    $query = new WP_Query($args);
    // If there are any results, the number is of an attachment
    if ($query->have_posts()) {
        return true;
        // If not, this is not an attachment
    } else {
        return false;
    }
}

function new_term_image_details($post_attachment, $term, $key)
{
    /**
     * General Term Image Details
     *
     * Generate an array of details for a new term image.
     *
     * @param WP_Post $post_attachment The attachment post to generate the image details from.
     * @param WP_Term $term The term the image is associated with.
     * @return array An array containing details for the new term image.
     */

    $filepath = get_attached_file($post_attachment->ID);
    $upload_dir = wp_upload_dir();

    $subdir = 'taxonomy/' . $term->taxonomy;
    // add to subdir
    if ($key) {
        $subdir .= '/' . $key;
    }

    // Ensure term slug becomes filename, but retain original extension
    $filename = pathinfo(basename($filepath), PATHINFO_FILENAME); // Get the filename without extension
    $extension = pathinfo(basename($filepath), PATHINFO_EXTENSION); // Get the file extension
    $new_filename = $term->slug . '.' . $extension; // Concatenate the new string with the extension
    $new_filepath = str_replace($filename, $new_filename, $filepath); // Replace the old filename with the new one

    $url_abs = trailingslashit(wp_upload_dir()['baseurl']) . trailingslashit($subdir) . $new_filename;
    $url_rel = str_replace(home_url(), '', $url_abs);

    // Populate bits of $new_image
    $new_image = array();
    $new_image['subdir'] = $subdir; // post/client/contentnext/2011/12
    // $new_image['subdir_stem'] = $new_subdir_stem; // post/client/contentnext
    $new_image['filepath'] = trailingslashit(trailingslashit($upload_dir['basedir']) . $subdir) . $new_filename; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $new_image['dirname'] = trailingslashit($upload_dir['basedir']) . $subdir; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/
    $new_image['filename_noext'] = $filename;
    $new_image['extension'] = $extension;
    $new_image['filename'] = $new_filename; // netflix-on-tv-in-living-room-o.jpg
    $new_image['guid'] = trailingslashit(trailingslashit($upload_dir['baseurl']) . $subdir) . $new_filename; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $new_image['url_abs'] = $url_abs;
    $new_image['url_rel'] = $url_rel;
    $new_image['title'] = $term->name;

    return $new_image;

}

function term_img_move_controller($term_attachment, $term, $key)
{

    $old_term_image_details = old_image_details($term_attachment);
    // print_r($old_term_image_details);
    $new_term_image_details = new_term_image_details($term_attachment, $term, $key);
    // print_r($new_term_image_details);

    // Check if need to move
    if ($old_term_image_details['filepath'] == $new_term_image_details['filepath']) {
        do_my_log("👍🏻 Path ok, no need to move.");
        return false;
    } else {
        do_my_log("🚨 Path looks incorrect - " . $old_term_image_details['filepath']);
        // Wrong location - move it, and update post and metadata
        move_main_file($term_attachment->ID, $old_term_image_details, $new_term_image_details, null);

        $new_term_image_details = new_term_image_details($term_attachment, $term, $key);
        move_sizes_files($term_attachment->ID, $old_term_image_details, $new_term_image_details, null);
        move_original_file($term_attachment->ID, $old_term_image_details, $new_term_image_details);

    }

}

// TODO: Delete ref'd images when term is deleted - delete_term