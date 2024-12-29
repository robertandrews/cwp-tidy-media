<?php

function tidy_do_delete_attachments_on_post_delete($post_id)
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
    $settings = tidy_db_get_settings();
    if ($settings['tmo_do_delete_attachments_on_post_delete'] == 1) {

        // If the action is delete, or if the delete_all action is empty bin or empty trash
        if ((isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') || (isset($_REQUEST['delete_all']) && ($_REQUEST['delete_all'] == 'Empty Bin' || $_REQUEST['delete_all'] == 'Empty Trash'))) {

            // Get the post object about to be deleted
            $post = get_post($post_id);

            // If it's already in the trash, and we are deleting it for good
            if ($post->post_status == 'trash') {

                do_my_log("🗑 tidy_do_delete_attachments_on_post_delete()...");

                // Get the current screen, just for logging
                /*
                $current_screen = get_current_screen();
                $screen_id = $current_screen ? $current_screen->id : '';
                do_my_log("Screen ID: " . $screen_id);
                 */

                // Get post's attachments including featured image
                $attachments = do_get_all_attachments($post_id);

                // If there are attachments
                if (is_array($attachments) || is_object($attachments)) {

                    do_my_log("Attachments: " . count($attachments));

                    // Loop through each attachment
                    foreach ($attachments as $attachment) {
                        do_my_log("Checking " . $attachment->ID);
                        // Is the attachment used elsewhere?
                        $used_elsewhere = is_attachment_used_elsewhere($attachment->ID, $post->ID);
                        if ($used_elsewhere !== true) {
                            // Go ahead and delete it
                            do_my_log("Will delete attachment with post");
                            tidy_delete_the_attachment($attachment->ID);
                        } else {
                            // Do not delete it
                            do_my_log("Attachment used elsewhere. Will not delete.");
                        }
                    }
                }
            }
        }
    }
}
add_action('before_delete_post', 'tidy_do_delete_attachments_on_post_delete');

function tidy_delete_the_attachment($attachment_id)
{
/**
 * Delete Attachment
 *
 * Cleverly uses the kitchen sink to delete all traces of an attachment. WordPress has no single way
 * to do this. So the function:
 * - 1. Finds and deletes [sizes] of attachment, via auxillary function.
 * - 2. Deletes attachment files.
 * - 3. Deletes attachment metadata.
 * - Deletes attachment's directory if it becomes empty.
 *
 * @param int $attachment_id The ID of the attachment to be deleted.
 * @return void
 */
    do_my_log("tidy_delete_the_attachment()");

    // Check if the attachment does not exist OR is not an image
    if (!wp_attachment_is_image($attachment_id) || !get_post($attachment_id)) {
        do_my_log("❌ Attachment does not exist.");
        // Return early
        return;
    }

    // 0. Get directory before the file is gone
    $attachment_path = get_attached_file($attachment_id);
    $dir = dirname($attachment_path);

    // 1. Delete [sizes] via custom function
    tidy_delete_img_sizes($attachment_id);

    // 2. Delete the physical files associated with the attachment
    wp_delete_attachment_files($attachment_id, null, null, null);

    // 3. Delete the attachment and its metadata from the database
    // Some question mark over whether wp_delete_attachment_files() is necessary
    // since, supposedly, wp_delete_attachment() should do it all.
    // Was likely coded this way to account for original_image.
    wp_delete_attachment($attachment_id, true);

    // Delete the directory if it's empty
    tidy_delete_empty_dir($dir);

}

function tidy_delete_img_sizes($attachment_id)
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
