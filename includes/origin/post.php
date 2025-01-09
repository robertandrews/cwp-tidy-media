<?php
function catch_saved_post($post_id)
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

    // Prevent running during autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Prevent running during AJAX actions unless explicitly allowed
    if (defined('DOING_AJAX') && DOING_AJAX && !isset($_POST['tidy_media_nonce'])) {
        return;
    }

    // Prevent running during bulk edit
    if (isset($_GET['bulk_edit'])) {
        return;
    }

    // Get the post
    $post = get_post($post_id);
    if (!$post) {
        return;
    }

    // Check user capabilities
    $post_type = get_post_type($post);
    $post_type_object = get_post_type_object($post_type);
    if (!current_user_can($post_type_object->cap->edit_post, $post_id)) {
        return;
    }

    // Only run if:
    // - Post has a status
    // - Post is not an autosave
    // - Post is not a revision
    // - Post is not in trash
    // - Post is not an auto-draft
    if (get_post_status($post_id) &&
        !wp_is_post_autosave($post_id) &&
        !wp_is_post_revision($post_id) &&
        get_post_status($post_id) !== 'trash' &&
        get_post_status($post_id) !== 'auto-draft') {

        // Only run on preferred post types
        $post_types = tidy_get_our_post_types();
        $my_post_type = get_post_type($post_id);
        if (in_array($my_post_type, $post_types)) {

            do_my_log("💾 catch_saved_post() - " . get_post_field('post_type', $post_id) . " " . $post_id . ": " . get_the_title($post_id));

            // Retrieve current settings from database
            $settings = tidy_db_get_settings();

            // Core functions
            if ($settings['tmo_do_localise_images'] == 1) {
                tidy_do_localise_images($post_id);
            }
            if ($settings['tmo_do_relativise_urls'] == 1) {
                tidy_do_relativise_urls($post_id);
            }
            if ($settings['tmo_do_reorg_body_media'] == 1) {
                tidy_do_reorg_body_media($post_id);
            }
            if ($settings['tmo_do_reorg_post_attachments'] == 1) {
                tidy_do_reorg_post_attachments($post_id);
            }
            // tidy_do_delete_attachments_on_post_delete($post_id);
            do_my_log("🏁 Complete.");
            do_my_log("🔚");

        } else {
            // error: disallowed post type
        }
    }
}
add_action('save_post', 'catch_saved_post', 10, 1);

/**
 * Attach Media to Post
 *
 * Attaches a media item to a post if it's currently unattached.
 *
 * @param int $attachment_id The ID of the attachment to potentially attach
 * @param int $post_id The ID of the post to attach the media to
 * @return bool True if attachment was made, false if no attachment was needed
 */

function tidy_do_attach_media_to_post($attachment_id, $post_id)
{

    $post_attachment = get_post($attachment_id);

    if (!$post_attachment) {
        do_my_log("❌ Could not find attachment with ID " . $attachment_id);
        return false;
    }

    // Only attach if currently unattached
    if ($post_attachment->post_parent === 0 || $post_attachment->post_parent === '') {
        do_my_log("Image " . $attachment_id . " not attached to any post - attach it to this (" . $post_id . ").");

        // Set the post_parent of the image to the post ID
        $update_args = array(
            'ID' => $attachment_id,
            'post_parent' => $post_id,
        );

        remove_action('save_post', 'catch_saved_post', 10, 1);
        wp_update_post($update_args);
        add_action('save_post', 'catch_saved_post', 10, 1);

        return true;
    }

    return false;
}
