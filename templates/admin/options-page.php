<?php
/**
 * Template for the Tidy Media Organizer options page.
 *
 * @var array $settings The current plugin settings
 * @var array $domains The legacy domains array
 * @var array $post_types Available post types
 * @var array $taxonomies Available taxonomies
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1>Tidy Media Organizer</h1>

    <form method="post">
        <div id="poststuff">
            <div id="post-body" class="metabox-holder">
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">

                        <!-- System settings -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>System settings</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="path_inc_post_type">Logging</label>
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

                        <!-- Post attachment settings -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Post attachment settings</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="path_inc_post_type">Operation</label>
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
                                                <label for="path_inc_post_type">Functions</label>
                                            </th>
                                            <td>
                                                <div class="function-toggles">
                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="tmo_do_localise_images" id="tmo_do_localise_images"
                                                                value="1"
                                                                <?php checked($settings['tmo_do_localise_images'], 1);?>>
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
                                                            <input type="checkbox" name="tmo_do_relativise_urls" id="tmo_do_relativise_urls"
                                                                value="1"
                                                                <?php checked($settings['tmo_do_relativise_urls'], 1);?>>
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
                                                            <input type="checkbox" name="tmo_do_delete_attachments_on_post_delete" id="tmo_do_delete_attachments_on_post_delete"
                                                                value="1" <?php checked($settings['tmo_do_delete_attachments_on_post_delete'], 1);?>>
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
                                                <label for="path_inc_post_type">Tidy functions</label>
                                            </th>
                                            <td>
                                                <div class="function-toggles">
                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="tmo_do_reorg_body_media"
                                                                id="tmo_do_reorg_body_media" value="1"
                                                                <?php checked($settings['tmo_do_reorg_body_media'], 1);?>>
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
                                                            <input type="checkbox" name="tmo_do_reorg_post_attachments"
                                                                id="tmo_do_reorg_post_attachments" value="1"
                                                                <?php checked($settings['tmo_do_reorg_post_attachments'], 1);?>>
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
                                                <label for="path_inc_post_type">Organize by post type?</label>
                                            </th>
                                            <td>
                                                <input type="checkbox" name="path_inc_post_type"
                                                    id="path_inc_post_type" value="1"
                                                    <?php checked($settings['path_inc_post_type'], 1);?>
                                                    class="media-folder-input">
                                                <?php
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
                                                        <input type="radio" name="folder_item_taxonomy"
                                                            value=""
                                                            <?php checked($settings['folder_item_taxonomy'], '');?>
                                                            class="media-folder-input">
                                                        <span>None</span>
                                                    </label>
                                                    <?php foreach ($taxonomies as $taxonomy): ?>
                                                    <label class="taxonomy-option">
                                                        <input type="radio" name="folder_item_taxonomy"
                                                            value="<?php echo esc_attr($taxonomy); ?>"
                                                            <?php checked($settings['folder_item_taxonomy'], $taxonomy);?>
                                                            class="media-folder-input">
                                                        <code><?php echo esc_html($taxonomy); ?></code>
                                                    </label>
                                                    <?php endforeach;?>
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
                                                        <input type="radio" name="folder_item_post_identifier"
                                                            value=""
                                                            <?php checked($settings['folder_item_post_identifier'], '');?>
                                                            class="media-folder-input">
                                                        <span>None</span>
                                                    </label>
                                                    <label class="taxonomy-option">
                                                        <input type="radio" name="folder_item_post_identifier"
                                                            value="slug"
                                                            <?php checked($settings['folder_item_post_identifier'], 'slug');?>
                                                            class="media-folder-input">
                                                        <span>Post slug</span> (eg. <code>my-awesome-post</code>)
                                                    </label>
                                                    <label class="taxonomy-option">
                                                        <input type="radio" name="folder_item_post_identifier"
                                                            value="id"
                                                            <?php checked($settings['folder_item_post_identifier'], 'id');?>
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
                                                <label for="path_inc_post_type">Legacy domains</label>
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
                                                    <code>http://www.oldsite.com</code> and <code>https://oldsite.com</code>
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

                        <!-- Term attachment settings -->
                        <div class="postbox">
                            <div class="postbox-header">
                                <h2>Term attachment settings</h2>
                            </div>
                            <div class="inside">
                                <table class="form-table" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                                <label for="path_inc_tax_term">Operation</label>
                                            </th>
                                            <td>
                                                <div class="function-toggles">
                                                    <label class="switch-wrapper">
                                                        <span class="switch">
                                                            <input type="checkbox" name="path_inc_tax_term"
                                                                id="path_inc_tax_term" value="1"
                                                                <?php checked($settings['path_inc_tax_term'], 1);?>>
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
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
window.uploadsBasePath = '<?php echo esc_js(wp_upload_dir()['basedir']); ?>';
window.uploadsUseYearMonthFolders = <?php echo get_option('uploads_use_yearmonth_folders') === '1' ? 'true' : 'false'; ?>;
</script>