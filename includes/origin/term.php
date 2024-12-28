<?php

// Support taxonomy term file checking on edit_term hook, if user has enabled it
$settings = tidy_db_get_settings();
/*
if ($settings['organize_term_attachments'] == 1) {
add_action('edit_term', 'do_edit_term', 10, 3);
add_action('create_term', 'do_edit_term', 10, 3);
}
 */

function do_edit_term($term_id, $tt_id, $taxonomy)
{
    /**
     * Check Edited Term
     *
     * @param int $term_id The ID of the term to edit.
     * @param int $tt_id The taxonomy term ID.
     * @param string $taxonomy The taxonomy name.
     * @return void
     */

    do_my_log("do_edit_term()");

    // Get served term and its meta
    $term = get_term($term_id, $taxonomy);
    $term_meta = get_term_meta($term_id);

    // Go through term's wp_termmeta records, looking for attachment IDs
    foreach ($term_meta as $key => $value) {
        // do_my_log("Term Meta: " . $key . " = " . $value[0]);
        // if value 0 is an integer
        // TODO: Functionalise this repeating block
        if (is_numeric($value[0])) {
            $number_found = $value[0];
            do_my_log($key . " value is numeric: " . $number_found);
            // If the number corresponds to an actual attachment, assess the file for moving
            if (is_id_attachment($number_found)) {
                do_my_log("Found attachment against " . $key . " - " . $number_found);
                $attachment_path = get_attached_file($number_found);
                $post_attachment = get_post($number_found);
                term_img_move_controller($post_attachment, $term, $key);
            }
        } else {
            // do_my_log($key . " value is not numeric");
            if (is_serialized($value[0])) {
                do_my_log($key . " value is serialized");
                // unserialise it
                $unserialized_meta_value = unserialize($value[0]);
                foreach ($unserialized_meta_value as $key2 => $value2) {
                    // do_my_log("Unserialized Meta: " . $key2 . " = " . $value2);
                    // When single photos is used, CMB2 stores its ID as a value
                    // TODO: Functionalise this repeating block
                    if (is_numeric($value2)) {
                        $number_found = $value2;
                        do_my_log($key2 . " value is numeric: " . $number_found);
                        // If the number corresponds to an actual attachment, assess the file for moving
                        if (is_id_attachment($number_found)) {
                            do_my_log("Found attachment against " . $key2 . " - " . $number_found);
                            $attachment_path = get_attached_file($number_found);
                            $post_attachment = get_post($number_found);
                            term_img_move_controller($post_attachment, $term, $key);
                        }
                    }
                    // When multiple photos are used, CMB2 stores their IDs as keys, with URLs as values
                    // TODO: Functionalise this repeating block
                    if (is_numeric($key2)) {
                        $number_found = $key2;
                        do_my_log($key2 . " value is numeric: " . $number_found);
                        // If the number corresponds to an actual attachment, assess the file for moving
                        if (is_id_attachment($number_found)) {
                            do_my_log("Found attachment against " . $key2 . " - " . $number_found);
                            $attachment_path = get_attached_file($number_found);
                            $post_attachment = get_post($number_found);
                            term_img_move_controller($post_attachment, $term, $key);
                        }
                    }

                }

            }
        }

    }

}

function new_term_image_details($post_attachment, $term, $key)
{
    /**
     * General Term Image Details
     *
     * Generate an array of details for a new term image.
     *
     * @param WP_Post $post_attachment The attachment post to generate the image details from.
     * @param WP_Term $term The term the image is associated with.
     * @return array An array containing details for the new term image.
     */

    $filepath = get_attached_file($post_attachment->ID);
    $upload_dir = wp_upload_dir();

    $subdir = 'taxonomy/' . $term->taxonomy;
    // add to subdir
    if ($key) {
        $subdir .= '/' . $key;
    }

    // Ensure term slug becomes filename, but retain original extension
    $filename = pathinfo(basename($filepath), PATHINFO_FILENAME); // Get the filename without extension
    $extension = pathinfo(basename($filepath), PATHINFO_EXTENSION); // Get the file extension
    $new_filename = $term->slug . '.' . $extension; // Concatenate the new string with the extension
    $new_filepath = str_replace($filename, $new_filename, $filepath); // Replace the old filename with the new one

    $url_abs = trailingslashit(wp_upload_dir()['baseurl']) . trailingslashit($subdir) . $new_filename;
    $url_rel = str_replace(home_url(), '', $url_abs);

    // Populate bits of $new_image
    $new_image = array();
    $new_image['subdir'] = $subdir; // post/client/contentnext/2011/12
    // $new_image['subdir_stem'] = $new_subdir_stem; // post/client/contentnext
    $new_image['filepath'] = trailingslashit(trailingslashit($upload_dir['basedir']) . $subdir) . $new_filename; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $new_image['dirname'] = trailingslashit($upload_dir['basedir']) . $subdir; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/
    $new_image['filename_noext'] = $filename;
    $new_image['extension'] = $extension;
    $new_image['filename'] = $new_filename; // netflix-on-tv-in-living-room-o.jpg
    $new_image['guid'] = trailingslashit(trailingslashit($upload_dir['baseurl']) . $subdir) . $new_filename; // /Users/robert/Sites/context.local/wp-content/uploads/post/client/contentnext/2011/12/netflix-on-tv-in-living-room-o.jpg
    $new_image['url_abs'] = $url_abs;
    $new_image['url_rel'] = $url_rel;
    $new_image['title'] = $term->name;

    return $new_image;

}

function term_img_move_controller($term_attachment, $term, $key)
{

    $old_term_image_details = old_image_details($term_attachment);
    // print_r($old_term_image_details);
    $new_term_image_details = new_term_image_details($term_attachment, $term, $key);
    // print_r($new_term_image_details);

    // Check if need to move
    if ($old_term_image_details['filepath'] == $new_term_image_details['filepath']) {
        do_my_log("👍🏻 Path ok, no need to move.");
        return false;
    } else {
        do_my_log("🚨 Path looks incorrect - " . $old_term_image_details['filepath']);
        // Wrong location - move it, and update post and metadata
        move_main_file($term_attachment->ID, $old_term_image_details, $new_term_image_details, null);

        $new_term_image_details = new_term_image_details($term_attachment, $term, $key);
        move_sizes_files($term_attachment->ID, $old_term_image_details, $new_term_image_details, null);
        move_original_file($term_attachment->ID, $old_term_image_details, $new_term_image_details);

    }

}
// TODO: Delete ref'd images when term is deleted - delete_term