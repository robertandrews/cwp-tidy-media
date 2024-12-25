<?php

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
                                                        <code><?php echo WP_PLUGIN_DIR . '/tidy-media-organizer'; ?><a href="<?php echo plugins_url('wp-tidy-media.log', dirname(__FILE__, 2)); ?>" target="_new">/wp-tidy-media.log</a></code>
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