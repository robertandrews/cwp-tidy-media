<?php

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

function is_id_attachment($number_found)
{
    /**
     * Check If ID Is Attachment
     *
     * @param int $number_found The ID to check
     * @return bool True if the ID is an attachment; false otherwise.
     */

    do_my_log("is_id_attachment()");

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

    $doc = tidy_get_content_dom($content);

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
        $attachments_unique = deduplicate_array_by_key($attachments, "ID");
        return $attachments_unique;
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
