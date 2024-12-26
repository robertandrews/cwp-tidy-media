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
                array('setting_name' => 'domains_to_replace', 'setting_value' => isset($_POST['domains_to_replace']) ? json_encode(array_filter($_POST['domains_to_replace'])) : ''),
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
    $domains = json_decode($settings['domains_to_replace'], true) ?: array(); // Decode the JSON to an array

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
                                <table class="form-table" role="presentation">
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
                                                        <code><?php echo plugin_dir_path(dirname(dirname(__FILE__))); ?><a href="<?php echo plugins_url('wp-tidy-media.log', dirname(dirname(__FILE__))); ?>" target="_new">wp-tidy-media.log</a></code>
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
                                <table class="form-table" role="presentation">
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
                                                    <p class="description">Fires on
                                                        <code><a href="https://developer.wordpress.org/reference/hooks/save_post/" target="_blank">save_post</a></code>
                                                        for these
                                                        types:
                                                        <?php
$post_types = our_post_types();
    echo implode(', ', array_map(function ($post_type) {
        return '<code>' . $post_type . '</code>';
    }, $post_types));
    ?>. Enabling this option allows the plugin to automatically organize media files attached to posts whenever a post is
                                                        saved.
                                                    </p>
                                                    <p class="description">If disabled, media organization will not
                                                        occur
                                                        automatically, and you will need to trigger it manually using
                                                        the Tidy Media button in the <a
                                                            href="<?php echo admin_url('edit.php'); ?>"
                                                            target="_blank">post list</a>.</p>
                                                </label>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Functions</label>
                                            </th>
                                            <td>
                                                <div class="function-toggles">
                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="use_localise" id="use_localise"
                                                                value="1"
                                                                <?php checked($settings['use_localise'], 1);?>>
                                                            <span class="slider"></span>
                                                        </span>
                                                        <span class="switch-label">
                                                            Pull down remote body images
                                                            <p class="description">In post content, all off-site images
                                                                will be localised to your site.</p>
                                                            <div class="example-block">
                                                                <p class="before"><strong>Before:</strong>
                                                                    <code>&lt;img src="https://external-site.com/images/photo.jpg"&gt;</code>
                                                                </p>
                                                                <p class="after"><strong>After:</strong>
                                                                    <code>&lt;img src="/wp-content/uploads/<?php
echo date('Y/m');
    ?>/photo.jpg"&gt;</code>
                                                                </p>
                                                            </div>
                                                        </span>
                                                    </label>

                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="use_relative" id="use_relative"
                                                                value="1"
                                                                <?php checked($settings['use_relative'], 1);?>>
                                                            <span class="slider"></span>
                                                        </span>
                                                        <span class="switch-label">
                                                            Convert local body image URLs from absolute to relative
                                                            <p class="description">In post content, any of your own
                                                                images called via absolute URLs will be replaced by
                                                                relative URLs.</p>
                                                            <div class="example-block">
                                                                <p class="before"><strong>Before:</strong>
                                                                    <code>&lt;img src="<?php echo home_url(); ?>/wp-content/uploads/path/to/image.jpeg"&gt;</code>
                                                                </p>
                                                                <p class="after"><strong>After:</strong>
                                                                    <code>&lt;img src="/wp-content/uploads/path/to/image.jpeg"&gt;</code>
                                                                </p>
                                                            </div>
                                                        </span>
                                                    </label>

                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="use_delete" id="use_delete"
                                                                value="1" <?php checked($settings['use_delete'], 1);?>>
                                                            <span class="slider"></span>
                                                        </span>
                                                        <span class="switch-label">
                                                            Delete attachments upon post deletion
                                                            <p class="description">When a post is deleted, any
                                                                attachments will also be deleted. Will not delete if
                                                                attachment is used elsewhere, ie in another post.</p>
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Tidy functions</label>
                                            </th>
                                            <td>
                                                <div class="function-toggles">
                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="use_tidy_body_media"
                                                                id="use_tidy_body_media" value="1"
                                                                <?php checked($settings['use_tidy_body_media'], 1);?>>
                                                            <span class="slider"></span>
                                                        </span>
                                                        <span class="switch-label">
                                                            Reorganise media found in post content
                                                            <p class="description">Post content is examined for local
                                                                image URLs. Corresponding found attachments
                                                                will be moved to your custom folder structure. The
                                                                <code>&lt;img src</code> and <code>&lt;a href</code>
                                                                elements in post body will be updated accordingly.
                                                            </p>
                                                            <div class="example-block">
                                                                <p class="before"><strong>Before:</strong>
                                                                    <code>&lt;img src="/wp-content/uploads/2024/03/image.jpg"&gt;</code>
                                                                </p>
                                                                <p class="after"><strong>After:</strong>
                                                                    <code>&lt;img src="<span class="dynamic-path"></span>/image.jpg"&gt;</code>
                                                                </p>
                                                            </div>
                                                        </span>
                                                    </label>

                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="use_tidy_attachments"
                                                                id="use_tidy_attachments" value="1"
                                                                <?php checked($settings['use_tidy_attachments'], 1);?>>
                                                            <span class="slider"></span>
                                                        </span>
                                                        <span class="switch-label">
                                                            Reorganise post attachments
                                                            <p class="description">Post-attached images will be moved to
                                                                a folder structure that mirrors your content structure.
                                                                Attachment metadata will be updated.</p>
                                                        </span>
                                                    </label>

                                                </div>
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
                                <table class="form-table" role="presentation">
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
                                                <fieldset>
                                                    <label class="taxonomy-option">
                                                        <input type="radio" name="organize_post_img_by_taxonomy"
                                                            value=""
                                                            <?php checked($settings['organize_post_img_by_taxonomy'], '');?>
                                                            class="media-folder-input">
                                                        <span>None</span>
                                                    </label>
                                                    <?php
$taxonomies = get_taxonomies(array('public' => true));
    foreach ($taxonomies as $taxonomy) {
        ?>
                                                    <label class="taxonomy-option">
                                                        <input type="radio" name="organize_post_img_by_taxonomy"
                                                            value="<?php echo esc_attr($taxonomy); ?>"
                                                            <?php checked($settings['organize_post_img_by_taxonomy'], $taxonomy);?>
                                                            class="media-folder-input">
                                                        <code><?php echo esc_html($taxonomy); ?></code>
                                                    </label>
                                                    <?php
}
    ?>
                                                </fieldset>
                                                <p class="description">Select a taxonomy to organize media files into
                                                    folders based on their taxonomy terms. (If a post does not have a
                                                    term set in the selected taxonomy, the media will be organized into
                                                    a <code>misc</code> folder instead of <code>term_slug</code>).</p>
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
                                                    Settings</a>)
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label>Organize by post:</label>
                                            </th>
                                            <td>
                                                <fieldset>
                                                    <label class="taxonomy-option">
                                                        <input type="radio" name="organize_post_img_by_post_slug"
                                                            value=""
                                                            <?php checked($settings['organize_post_img_by_post_slug'], '');?>
                                                            class="media-folder-input">
                                                        <span>None</span>
                                                    </label>
                                                    <label class="taxonomy-option">
                                                        <input type="radio" name="organize_post_img_by_post_slug"
                                                            value="slug"
                                                            <?php checked($settings['organize_post_img_by_post_slug'], 'slug');?>
                                                            class="media-folder-input">
                                                        <span>Post slug</span> (eg. <code>my-awesome-post</code>)
                                                    </label>
                                                    <label class="taxonomy-option">
                                                        <input type="radio" name="organize_post_img_by_post_slug"
                                                            value="id"
                                                            <?php checked($settings['organize_post_img_by_post_slug'], 'id');?>
                                                            class="media-folder-input">
                                                        <span>Post ID</span> (eg. <code>142</code>)
                                                    </label>
                                                </fieldset>
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
                        </div>

                        <!-- Body image URLs-->
                        <div class="postbox" id="relative-urls-settings">
                            <div class="postbox-header">
                                <h2>Additional domains to make relative</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_post_img_by_type">Legacy domains</label>
                                            </th>
                                            <td>
                                                <p class="description">With "Convert local body image URLs from absolute to relative" set, the plugin will automatically make URLs relative by removing <code><?php echo home_url(); ?></code>.</p>
                                                <p class="description">Use this setting to specify additional domains that should also be made relative.</p>
                                                <p class="description">This is useful if you have imported content from other sites and still have image URLs that point to old domains.</p>
                                                <div class="legacy-domains-wrapper">
                                                    <div class="legacy-domains-list">
                                                        <?php foreach ($domains as $domain): ?>
                                                        <div class="legacy-domain-item">
                                                            <input type="text" name="domains_to_replace[]" class="legacy-domain-input" placeholder="https://oldsite.com" value="<?php echo esc_attr($domain); ?>" />
                                                            <button type="button" class="button remove-domain" title="Remove domain">&minus;</button>
                                                        </div>
                                                        <?php endforeach;?>
                                                    </div>
                                                    <button type="button" class="button add-domain">Add Another Domain</button>
                                                </div>
                                                <div class="example-block">
                                                    <p><strong>Example:</strong> If you migrated from oldsite.com to this site, add:</p>
                                                    <code>http://www.oldsite.com, https://oldsite.com</code>
                                                    <p class="before"><strong>Before:</strong>
                                                        <code>&lt;img src="http://www.oldsite.com/wp-content/uploads/path/to/image.jpeg"&gt;</code>
                                                    </p>
                                                    <p class="after"><strong>After:</strong>
                                                        <code>&lt;img src="/wp-content/uploads/path/to/image.jpeg"&gt;</code>
                                                    </p>
                                                </div>
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
                                <table class="form-table" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="organize_term_attachments">Operation</label>
                                            </th>
                                            <td>
                                                <div class="function-toggles">
                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="organize_term_attachments"
                                                                id="organize_term_attachments" value="1"
                                                                <?php checked($settings['organize_term_attachments'], 1);?>>
                                                            <span class="slider"></span>
                                                        </span>
                                                        <span class="switch-label">
                                                            Tidy media attached to taxonomy terms
                                                            <p class="description">In WordPress, media can be attached
                                                                to
                                                                taxonomy terms as well as posts. This setting allows the
                                                                plugin
                                                                to organize
                                                                media files that are associated with taxonomy terms
                                                                (like
                                                                categories or tags). If a taxonomy term has media
                                                                attachment IDs
                                                                stored in its metadata, those media files will be
                                                                organized into
                                                                corresponding folders.</p>
                                                            <p class="description">For example, if you have a category
                                                                called
                                                                <code>Travel</code> and it has an associated image,
                                                                enabling
                                                                this option will ensure that the image is organized into
                                                                a
                                                                folder named <code>taxonomy/category/travel/</code>.
                                                            </p>

                                                            <div class="example-block">
                                                                <p class="before"><strong>Before:</strong>
                                                                    <code><?php echo esc_html(wp_upload_dir()['basedir']); ?>/path/to/image.jpeg</code>
                                                                </p>
                                                                <p class="after"><strong>After:</strong>
                                                                    <code><?php echo esc_html(wp_upload_dir()['basedir']); ?>/taxonomy/category/travel/image.jpeg</code>
                                                                </p>
                                                            </div>

                                                            <p class="description">Fires on <code><a
                                                                        href="https://developer.wordpress.org/reference/hooks/edit_term/"
                                                                        target="_blank">edit_term</a></code> for
                                                                these
                                                                taxonomies:
                                                                <?php
$taxonomies = get_taxonomies(array('public' => true));
    echo implode(', ', array_map(function ($taxonomy) {
        return '<code>' . $taxonomy . '</code>';
    }, $taxonomies));
    ?>.
                                                        </span>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label>Preview:</label>
                                            </th>
                                            <td>
                                                <div><?php echo esc_html(wp_upload_dir()['basedir']); ?>/<span
                                                        style="color:#d63638">taxonomy_name_slug</span>/<span
                                                        style="color:#00a32a">term_name_slug</span>/image.jpeg</div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.querySelector('.legacy-domains-wrapper');
        if (wrapper) {
            // Add new domain field
            wrapper.querySelector('.add-domain').addEventListener('click', function() {
                const list = wrapper.querySelector('.legacy-domains-list');
                const newItem = document.createElement('div');
                newItem.className = 'legacy-domain-item';
                newItem.innerHTML = `
                    <input type="text"
                        name="domains_to_replace[]"
                        class="legacy-domain-input"
                        placeholder="https://oldsite.com" />
                    <button type="button" class="button remove-domain" title="Remove domain">&minus;</button>
                `;
                list.appendChild(newItem);
            });

            // Remove domain field
            wrapper.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-domain')) {
                    e.target.parentElement.remove();
                }
            });
        }
    });

    function updatePaths() {
        var basePath = '/wp-content/uploads';
        var postTypeEnabled = document.querySelector('[name="organize_post_img_by_type"]').checked;
        var taxonomySlug = document.querySelector('[name="organize_post_img_by_taxonomy"]:checked');
        var postIdentifier = document.querySelector('[name="organize_post_img_by_post_slug"]:checked')?.value || '';
        var uploadsUseYearMonthFolders =
            <?php echo get_option('uploads_use_yearmonth_folders') === '1' ? 'true' : 'false'; ?>;

        var path = basePath;

        if (postTypeEnabled) {
            path += '/<span style="color:#d63638">post_type</span>';
        }

        if (taxonomySlug && taxonomySlug.value !== '') {
            path += '/<span style="color:#00a32a">' + taxonomySlug.value +
                '</span>/<span style="color:#2271b1">term_slug</span>';
        }

        if (uploadsUseYearMonthFolders) {
            var today = new Date();
            var year = today.getFullYear();
            var month = today.getMonth() + 1;
            var dateFolders = year + '/' + (month < 10 ? '0' + month : month);
            path += '/' + dateFolders;
        }

        if (postIdentifier === 'slug') {
            path += '/<span style="color:#dba617">my-awesome-post</span>';
        } else if (postIdentifier === 'id') {
            path += '/<span style="color:#dba617">142</span>';
        }

        // Update preview path with colors
        var fullPreviewPath = '<?php echo esc_js(wp_upload_dir()['basedir']); ?>' + path + '/image.jpeg';
        document.querySelector('#planned-path').innerHTML = fullPreviewPath;

        // Update dynamic path examples (without colors)
        var plainPath = path.replace(/<[^>]+>/g, ''); // Strip HTML tags for the examples
        document.querySelectorAll('.dynamic-path').forEach(function(element) {
            element.textContent = plainPath;
        });
    }

    function toggleRelativeUrlsBox() {
        var useRelativeToggle = document.querySelector('[name="use_relative"]');
        var relativeUrlsBox = document.getElementById('relative-urls-settings');

        if (useRelativeToggle && relativeUrlsBox) {
            relativeUrlsBox.style.display = useRelativeToggle.checked ? 'block' : 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Add event listeners for all relevant inputs
        document.querySelector('[name="organize_post_img_by_type"]').addEventListener('change', updatePaths);

        var radioButtons = document.querySelectorAll(
            '[name="organize_post_img_by_taxonomy"], [name="organize_post_img_by_post_slug"]');
        for (var i = 0; i < radioButtons.length; i++) {
            radioButtons[i].addEventListener('change', updatePaths);
        }

        // Add event listener for the relative URLs toggle
        var useRelativeToggle = document.querySelector('[name="use_relative"]');
        if (useRelativeToggle) {
            useRelativeToggle.addEventListener('change', toggleRelativeUrlsBox);
            // Initial toggle state
            toggleRelativeUrlsBox();
        }

        // Initial update
        updatePaths();
    });
    </script>

</div>

<style>
.taxonomy-option {
    display: block;
    margin: 8px 0;
    padding: 4px 0;
}

.taxonomy-option input[type="radio"] {
    margin-right: 8px;
}

.taxonomy-option code {
    font-size: 13px;
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
}

fieldset {
    border: none;
    padding: 0;
    margin: 0;
}

.function-toggles {
    display: flex;
    flex-direction: column;
    gap: 1.5em;
}

.switch-wrapper {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin: 0;
    cursor: pointer;
}

.switch {
    position: relative;
    display: inline-block;
    width: 40px;
    height: 20px;
    flex-shrink: 0;
    margin-top: 3px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 20px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked+.slider {
    background-color: #2271b1;
}

input:checked+.slider:before {
    transform: translateX(20px);
}

.switch-label {
    flex: 1;
    font-weight: 500;
}

.switch-label .description {
    font-weight: normal;
    margin-top: 4px;
}

.example-block {
    background: #f0f0f1;
    border-radius: 4px;
    padding: 8px 12px;
    margin-top: 8px;
    font-size: 12px;
}

.example-block p {
    margin: 4px 0;
}

.example-block code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
}

.example-block .before {
    color: #757575;
}

.example-block .after {
    color: #2271b1;
}

/* Adjust spacing for examples */
.switch-wrapper {
    margin-bottom: 8px;
}

.switch-label .description {
    margin-bottom: 0;
}

.legacy-domains-wrapper {
    margin: 10px 0;
}

.legacy-domain-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
}

.legacy-domain-input {
    flex: 1;
    max-width: 400px;
}

.remove-domain {
    padding: 0 8px !important;
    line-height: 1.7 !important;
}

.add-domain {
    margin-top: 4px !important;
}
</style>

<?php

}