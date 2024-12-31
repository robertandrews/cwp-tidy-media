<?php

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
function is_attachment_used_elsewhere($attachment_id, $main_post_id)
{

    do_my_log("is_attachment_used_elsewhere()");

    // Validate input IDs
    if (!$attachment_id || !$main_post_id) {
        do_my_log("Error: Invalid attachment_id or main_post_id provided");
        return false;
    }

    $main_post = get_post($main_post_id);
    $attachment = get_post($attachment_id);

    // Check if posts exist
    if (!$main_post || !$attachment) {
        do_my_log("Error: Could not find post or attachment with provided IDs");
        return false;
    }

    $old_image_details = get_old_image_details($attachment->ID);

    // Check 1: Is URL in body content?
    $args_attach = array(
        'post_type' => tidy_get_our_post_types(),
        'posts_per_page' => -1,
        'post__not_in' => array($main_post_id), // omit the starting post, which was already updated
        's' => $old_image_details['url_rel'],
    );
    $query_attach = new WP_Query($args_attach);
    do_my_log("Attachment: Found " . $query_attach->found_posts . " other posts with this as attachment");
    if ($query_attach->found_posts > 0) {
        return true;
    }

    // Check 2: Is media object used as a post featured image (not for this post)?
    $args_thumb = array(
        'post_type' => tidy_get_our_post_types(), // Replace with the post type you want to search in
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

    // Media not used elsewhere
    return false;
}

/**
 * Generate Existing Image Details
 *
 * Retrieves various details of an old image attachment for a post.
 * This is designed to make the partial folder and filepath parts available to other functions
 * in a singular array. This avoids needing to generate those parts in those functions.
 *
 * @param int $attachment_id The ID of the attachment (i.e., the image).
 * @return array An associative array containing details of the image's old location (e.g., 'dirname', 'filepath', 'subdir', 'filename', 'guid').
 */
function get_old_image_details($attachment_id)
{
    // Get the filepath
    $filepath = get_attached_file($attachment_id);
    // Get the upload directory
    $upload_dir = wp_upload_dir();
    // Get the subdirectory
    $subdir = str_replace($upload_dir['basedir'], '', dirname($filepath));
    $subdir = ltrim($subdir, '/');
    // Get the guid
    $guid = get_post($attachment_id)->guid;
    $url_abs = trailingslashit(wp_upload_dir()['baseurl']) . trailingslashit($subdir) . basename($filepath);
    $url_rel = str_replace(home_url(), '', $url_abs);

    // Populate the array
    $old_image = array();
    $old_image['filepath'] = $filepath;
    $old_image['dirname'] = dirname($filepath);
    $old_image['subdir'] = $subdir;
    $old_image['filename'] = basename($filepath);
    $old_image['guid'] = $guid;
    $old_image['url_abs'] = $url_abs;
    $old_image['url_rel'] = $url_rel;
    return $old_image;
}

/**
 * Generate Details to Relocate and Update Image
 *
 * Formulates various details of the new image attachment for a post.
 *
 * This is designed to make the partial folder and filepath parts available to other functions
 * in a singular array. This avoids needing to generate those parts in those functions.
 *
 * @param int $post_id The ID of the post where the image is attached.
 * @param int $attachment_id The ID of the attachment.
 * @return array An associative array containing the details of the new image.
 */
function generate_new_image_details($post_id, $attachment_id)
{
    // Cache upload directory info - used multiple times
    $upload_dir = wp_upload_dir();
    $filepath = get_attached_file($attachment_id);
    $filename = basename($filepath);

    // Get user's path preferences from database
    $settings = tidy_db_get_settings();

    // Initialize path parts array for efficient concatenation
    $path_parts = array();
    $new_subdir_stem = '';

    // a. Use post type?
    if ($settings['path_inc_post_type'] == 1) {
        $path_parts[] = get_post_type($post_id);
    }

    // b. Use taxonomy name and term?
    if (!empty($settings['folder_item_taxonomy'])) {
        $path_parts[] = $settings['folder_item_taxonomy'];
        $post_terms = get_the_terms($post_id, $settings['folder_item_taxonomy']);

        if ($post_terms) {
            $current_term = end($post_terms);
            $parent_ids = get_ancestors($current_term->term_id, $settings['folder_item_taxonomy']);

            // Build parent terms path
            foreach ($parent_ids as $parent_id) {
                $parent_term = get_term($parent_id, $settings['folder_item_taxonomy']);
                $path_parts[] = $parent_term->slug;
            }

            // Add current term
            $path_parts[] = $current_term->slug;

            // Store stem without the last term
            $new_subdir_stem = implode('/', array_slice($path_parts, 0, -1));
        } else {
            $path_parts[] = 'misc';
            $new_subdir_stem = implode('/', $path_parts);
        }
    }

    // c. Add date folders if enabled
    if (get_option('uploads_use_yearmonth_folders') == 1) {
        $post_date = get_post_field('post_date', $post_id);
        $path_parts[] = date('Y/m', strtotime($post_date));
    }

    // d. Add post slug if enabled
    if ($settings['folder_item_post_identifier'] == 1) {
        $post = get_post($post_id);
        if ($post) {
            $path_parts[] = $post->post_name;
        }
    }

    // Generate the subdirectory path
    $subdir = implode('/', array_filter($path_parts));

    // Generate URLs efficiently
    $base_url = trailingslashit($upload_dir['baseurl']);
    $subdir_path = $subdir ? trailingslashit($subdir) : '';
    $url_abs = $base_url . $subdir_path . $filename;
    $url_rel = str_replace(home_url(), '', $url_abs);

    // Return the image details array
    return array(
        'subdir' => $subdir,
        'subdir_stem' => $new_subdir_stem,
        'filepath' => trailingslashit(trailingslashit($upload_dir['basedir']) . $subdir) . $filename,
        'dirname' => trailingslashit($upload_dir['basedir']) . $subdir,
        'filename' => $filename,
        'guid' => $url_abs,
        'url_abs' => $url_abs,
        'url_rel' => $url_rel,
    );
}

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

function custom_path_controller($post_id, $post_attachment)
{

    do_my_log("custom_path_controller()...");

    // Generate source and destination path pieces
    $old_image_details = get_old_image_details($post_attachment->ID);
    // TODO: Failed to generate any custom-path details
    $new_image_details = generate_new_image_details($post_id, $post_attachment->ID);
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
            tidy_update_body_media_urls($post_id, $post_attachment->ID, $old_image_details, $new_image_details);
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
