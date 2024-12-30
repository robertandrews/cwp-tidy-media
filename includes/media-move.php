<?php

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
                    tidy_update_body_media_urls($post_id, $attachment_id, $old_image_details, $new_image_details);
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
