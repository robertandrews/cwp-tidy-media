<?php

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

    // Check 2: used as thumbnail elsewhere
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

    // Get the filepath
    $filepath = get_attached_file($post_attachment->ID); // TODO: Stop this happening on post deletion
    // Get the upload directory
    $upload_dir = wp_upload_dir();
    // Get the subdirectory
    $subdir = str_replace($upload_dir['basedir'], '', dirname($filepath));
    $subdir = ltrim($subdir, '/');
    // Get the guid
    $guid = $post_attachment->guid; // TODO: Stop this happening on post deletion
    $url_abs = trailingslashit(wp_upload_dir()['baseurl']) . trailingslashit($subdir) . basename($filepath);
    $url_rel = str_replace(home_url(), '', $url_abs);

    // Populate the array
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
    // TODO: Use tidy_db_get_settings() instead here...
    global $wpdb;
    $table_name = $wpdb->prefix . 'tidy_media_organizer';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        $settings = $wpdb->get_results("SELECT * FROM $table_name");
        $settings_arr = array();
        foreach ($settings as $setting) {
            $settings_arr[$setting->setting_name] = $setting->setting_value;
        }
        $path_inc_post_type = isset($settings_arr['path_inc_post_type']) ? $settings_arr['path_inc_post_type'] : 0;
        $folder_item_taxonomy = isset($settings_arr['folder_item_taxonomy']) ? $settings_arr['folder_item_taxonomy'] : '';
        $folder_item_post_identifier = isset($settings_arr['folder_item_post_identifier']) ? $settings_arr['folder_item_post_identifier'] : 0;
        // These will formulate the preferred path
    } else {
        // No database settings
        do_my_log("Could not get database settings.");
    }

    // Build new subdir
    $new_subdir = '';
    // a. Use post type?
    if ($path_inc_post_type == 1) {
        $post_type = get_post_type($post_id);
        $new_subdir .= $post_type;
    }
    // b. Use taxonomy name and term?
    if ($folder_item_taxonomy != '') {
        $new_subdir .= '/' . $folder_item_taxonomy;
        $post_terms = get_the_terms($post_id, $folder_item_taxonomy);

        if ($post_terms) {
            // Get the last term in the array to use as the current term
            $current_term = end($post_terms);

            // Get an array of the parent term IDs
            $parent_ids = get_ancestors($current_term->term_id, $folder_item_taxonomy);

            // Add the slugs of all the parent terms to the subdirectory
            foreach ($parent_ids as $parent_id) {
                $parent_term = get_term($parent_id, $folder_item_taxonomy);
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
    if ($folder_item_post_identifier == 1) {
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
