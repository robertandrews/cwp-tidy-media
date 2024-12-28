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

    // Only run if:
    // - Post is not permanently deleted (ie. has a status)
    // - Post status is not in the trash/bin (so, it won't fire when it's subsequently Permanently Deleted)
    // - Post status is not auto-draft (ie. don't fire when auto-drafting)
    // - Post status is not auto-saved
    // - Post status is not a saved revision

    if (get_post_status($post_id) && !wp_is_post_autosave($post_id) && !wp_is_post_revision($post_id) && get_post_status($post_id) !== 'trash' && get_post_status($post_id) !== 'auto-draft') {

        // Only run on preferred post types
        $post_types = tidy_get_our_post_types();
        $my_post_type = get_post_type($post_id);
        if (in_array($my_post_type, $post_types)) {

            do_my_log("💾 catch_saved_post() - " . $post_id . " " . get_post_field('post_type', $post_id) . ": " . get_the_title($post_id));

            // Retrieve current settings from database
            $settings = tidy_db_get_settings();

            // Core functions
            if ($settings['use_localise'] == 1) {
                tidy_do_localise_images($post_id);
            }
            if ($settings['use_relative'] == 1) {
                tidy_do_relativise_urls($post_id);
            }
            if ($settings['use_tidy_body_media'] == 1) {
                tidy_do_reorg_body_media($post_id);
            }
            if ($settings['use_tidy_attachments'] == 1) {
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
