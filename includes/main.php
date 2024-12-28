<?php
function do_saved_post($post_id)
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
        $post_types = our_post_types();
        $my_post_type = get_post_type($post_id);
        if (in_array($my_post_type, $post_types)) {

            do_my_log("💾 do_saved_post() - " . $post_id . " " . get_post_field('post_type', $post_id) . ": " . get_the_title($post_id));

            // Retrieve current settings from database
            $settings = tidy_db_get_settings();

            // Core functions
            if ($settings['use_localise'] == 1) {
                localise_remote_images($post_id);
            }
            if ($settings['use_relative'] == 1) {
                relative_body_imgs($post_id);
            }
            if ($settings['use_tidy_body_media'] == 1) {
                tidy_body_media($post_id);
            }
            if ($settings['use_tidy_attachments'] == 1) {
                tidy_post_attachments($post_id);
            }
            // delete_attached_images_on_post_delete($post_id);
            do_my_log("🏁 Complete.");
            do_my_log("🔚");

        } else {
            // error: disallowed post type
        }

    }
}
add_action('save_post', 'do_saved_post', 10, 1);

function tidy_post_attachments($post_id)
{
    /**
     * Tidy Post Attachments.
     *
     * This main function is responsible for tidying up post attachments after a post is saved in WordPress. It retrieves all
     * the attachments related to the post, and checks if they are located in the preferred path. If an attachment is found
     * in a non-preferred path, it will be moved to the preferred path.
     *
     * @param int $post_id The ID of the post to tidy up its attachments.
     * @return boolean Returns false if no attachments are found, or if any errors occur during the attachment move process.
     */

    do_my_log('🧩 tidy_post_attachments()...');

    // TODO: Why does this omit some featured images?
    $post_attachments = do_get_all_attachments($post_id);

    // $attachment_ids = implode(',', wp_list_pluck($post_attachments, 'ID'));
    // do_my_log("attach ids: ". $attachment_ids);

    // do_my_log(' thumbnail - ' . get_post_thumbnail_id($post_id));
    // $thumb_id = get_post_thumbnail_id($post_id);
    // $thumb_attachment = get_post($thumb_id);

    if ($post_attachments) {
        foreach ($post_attachments as $post_attachment) {

            do_my_log("🖼 Attachment " . $post_attachment->ID . " - " . $post_attachment->post_title);

            // Check file location, move if needed
            $move_attachment_outcome = custom_path_controller($post_id, $post_attachment);
            // return $move_attachment_outcome;

        }
    } else {
        do_my_log("No attachments found.");
        return false;
    }

}

function tidy_body_media($post_id)
{
    /**
     * Tidy Body Img Paths
     *
     * Fixes all local image URLs in the post's body to point to the expected location, based on the post's ID.
     * eg. Maybe /wp-content/uploads/image.jpeg should be /wp-content/uploads/post/taxonomy/term/image.jpeg
     *
     * When a malformed img src is found, function will:
     *  - Check if it exists in specified location form - move it and change the URL.
     *  - Check if it exists in intended location form  - just update URL to reflect.
     *
     * @param int $post_id The ID of the post whose body should be fixed.
     *
     * @return void
     */

    do_my_log("🧩 tidy_body_media()...");

    // Get the post content
    $content = get_post_field('post_content', $post_id);

    if (!$content) {
        return;
    }

    $doc = do_get_content_as_dom($content);

    $targets = array(
        "img" => "src",
        "a" => "href",
    );

    foreach ($targets as $element => $attribute) {

        // Find all img tags in the post content
        $matched_elements = $doc->getElementsByTagName($element);

        $num_tidied_in_body = 0;

        // Loop through each img tag
        foreach ($matched_elements as $el_match) {

            $modified = null;

            // Get the src attribute of the img tag
            $found_attribute = $el_match->getAttribute($attribute);

            // If the src attribute is either 1) a relative URL or 2) absolute URL on this site
            if (preg_match('/^(\/|' . preg_quote(home_url(), '/') . ')/', $found_attribute)) {

                // If the src attribute is an absolute local URL, strip the domain part
                if (strpos($found_attribute, home_url()) === 0) {
                    $found_attribute = preg_replace('/^' . preg_quote(home_url(), '/') . '/', '', $found_attribute);
                }

                // Get the file path of the image
                $filepath = get_home_path() . ltrim($found_attribute, '/');

                // Get found file's details
                do_my_log("🌄 Found src attribute " . $found_attribute);

                $found_media_filepath = get_home_path() . ltrim($found_attribute, '/'); // /Users/robert/Sites/context.local/wp-content/uploads/media/folio/clients/wired/tom_heather.jpg
                // do_my_log("Filepath would be " . $found_media_filepath);
                $post_attachment = null;

                // ✅ A) File is where src says - move it and update the body
                if (file_exists($found_media_filepath)) {

                    // do_my_log("File does exist at src. Getting its attachment object...");

                    $post_attachment = get_attachment_obj_from_filepath($found_attribute);

                    if ($post_attachment) {
                        do_my_log("🖼 Found attachment object " . $post_attachment->ID . " - " . $post_attachment->post_title);

                        // 1. Check file location, move if needed
                        $move_attachment_outcome = custom_path_controller($post_id, $post_attachment);
                        if ($move_attachment_outcome === true) {

                            // 2. Update the body
                            // do_my_log("Update the body...");
                            // Upload folder parts, used to generate attachment
                            $new_image_details = new_image_details($post_id, $post_attachment);

                            $uploads_base = trailingslashit(wp_upload_dir()['baseurl']); // http://context.local:8888/wp-content/uploads/
                            $uploads_folder = str_replace(trailingslashit(home_url()), '', $uploads_base); // /wp-content/uploads/

                            $settings = tidy_db_get_settings();
                            // Relative URL
                            if ($settings['use_relative'] == 1) {
                                $new_src = "/" . $uploads_folder . trailingslashit($new_image_details['subdir']) . $new_image_details['filename'];
                                // Absolute URL
                            } else {
                                $new_src = $uploads_base . trailingslashit($new_image_details['subdir']) . $new_image_details['filename'];
                            }

                            $el_match->setAttribute($attribute, $new_src);
                            $new_content = $doc->saveHTML();
                            // do_my_log("✅ Replacements made: " . $num_replacements);
                            // If the content has changed, set the modified flag to true
                            if ($new_content !== $content) {
                                $modified = true;
                                $content = $new_content;
                                $num_tidied_in_body++;
                            }
                            // TODO: Should the save happen here, repeatedly, or outside?
                            if ($modified == true) { // was if ($new_content) {
                                // do_my_log("Updating post...");
                                // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                                remove_action('save_post', 'do_saved_post', 10, 1);
                                // Re-save the post
                                wp_update_post(array(
                                    'ID' => $post_id,
                                    'post_content' => $content,
                                ));
                                // Hook it back up
                                add_action('save_post', 'do_saved_post', 10, 1);
                            }

                            // 3. Attach image to this post if it was unattached
                            if ($post_attachment->post_parent === 0 || $post_attachment->post_parent === '') {
                                do_my_log("Image " . $attachment_id . " not attached to any post - attach it to this (" . $post_id . ").");
                                // Set the post_parent of the image to the post ID
                                $update_args = array(
                                    'ID' => $attachment_id,
                                    'post_parent' => $post_id,
                                );
                                remove_action('save_post', 'do_saved_post', 10, 1);
                                wp_update_post($update_args);
                                add_action('save_post', 'do_saved_post', 10, 1);
                            }

                        }

                    } else {
                        // No attachment found
                        do_my_log("Could not find attachment object.");
                    }

                    // ❌ B) File is not at given src - find it and use that
                } else {

                    do_my_log("❌ File does not exist at " . $found_media_filepath);
                    // Search for the file
                    $search_results = search_file(basename($found_media_filepath));
                    if ($search_results) {
                        do_my_log("🔍 " . basename($found_media_filepath) . " found at " . $search_results);
                        $poss_path = "/" . str_replace(get_home_path(), '', $search_results);
                        $found_attachment = get_attachment_obj_from_filepath($poss_path);
                        $new_attachment_url = wp_get_attachment_image_url($found_attachment->ID, 'full');
                        $settings = tidy_db_get_settings();

                        // $new_image_details = new_image_details($post_id, $post_attachment);

                        $uploads_base = trailingslashit(wp_upload_dir()['baseurl']); // http://context.local:8888/wp-content/uploads/
                        $uploads_folder = str_replace(trailingslashit(home_url()), '', $uploads_base); // /wp-content/uploads/

                        $settings = tidy_db_get_settings();
                        // Relative URL
                        if ($settings['use_relative'] == 1) {
                            $new_attachment_url = str_replace(trailingslashit(home_url()), '/', $new_attachment_url);

                            // Absolute URL
                        } else {
                            do_my_log("Absolute URL");
                            $new_attachment_url = $new_attachment_url;
                        }

                        $el_match->setAttribute($attribute, $new_attachment_url);
                        $new_content = $doc->saveHTML();
                        // do_my_log("✅ Replacements made: " . $num_replacements);
                        // If the content has changed, set the modified flag to true
                        if ($new_content !== $content) {
                            $modified = true;
                            $content = $new_content;
                            $num_tidied_in_body++;
                        }
                        // TODO: Should the save happen here, repeatedly, or outside?
                        if ($modified == true) { // was if ($new_content) {
                            // do_my_log("Updating post...");
                            // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                            remove_action('save_post', 'do_saved_post', 10, 1);
                            // Re-save the post
                            wp_update_post(array(
                                'ID' => $post_id,
                                'post_content' => $content,
                            ));
                            // Hook it back up
                            add_action('save_post', 'do_saved_post', 10, 1);
                        }

                        do_my_log("Replaced " . $found_attribute . " with " . $new_attachment_url);

                    }
                }

            }
        }

        do_my_log("🧮 Tidied from body: " . $num_tidied_in_body);
        // do_my_log("Finished tidy_body_media().");
        // do_my_log("🔚");

    }

}

function relative_body_imgs($post_id)
{
    /**
     * Make In-Line Image URLs relative
     *
     * This function takes a WordPress post ID and modifies the post's content by
     * making all local image URLs relative to the site's root directory. It does this by
     * removing any specified domains from the image URLs.
     *
     * The function only removes the site's own scheme domain (eg. "http://www.myblog.com").
     *
     * @param int $post_id The ID of the WordPress post to modify.
     * @return void
     */

    do_my_log("🧩 relative_body_imgs()...");

    // Get the post content
    $content = get_post_field('post_content', $post_id);

    if ($content) {

        $new_content = $content;
        // Set starting vars
        $modified = false;
        $num_rel_changes = 0;

        // Set up list of domains to strip from links - site URL is added by default
        $settings = tidy_db_get_settings();
        $domains_to_replace = json_decode($settings['domains_to_replace'], true); // Decode the JSON array
        $local_domains = array_map('trim', $domains_to_replace);
        if (!in_array(get_site_url(), $local_domains)) {
            array_push($local_domains, get_site_url());
        }

        foreach ($local_domains as $domain) {

            // Set the encoding of the input HTML string
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $new_content = html_entity_decode($new_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            $doc = do_get_content_as_dom($content);

            // Get every img tag
            $images = $doc->getElementsByTagName('img');
            foreach ($images as $image) {
                // Get its src attribute
                $src = $image->getAttribute('src');
                // src starts with absolute url
                if (strpos($src, $domain) === 0) {
                    // Strip the domain part
                    $new_src = str_replace($domain, '', $src);
                    // Change the src in the content
                    $image->setAttribute('src', $new_src);
                    // Notice
                    do_my_log("Replaced: " . $src);
                    do_my_log("With: " . $new_src);
                    // Resave the content (to memory)
                    $new_content = $doc->saveHTML();
                    $num_rel_changes++;
                }
            }

            // If the content has changed,
            if ($new_content !== $content) {

                // do_my_log("Need to save post...");

                // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                remove_action('save_post', 'do_saved_post', 10, 1);
                // Re-save the post
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $new_content,
                ));
                do_my_log("✅ Post updated.");
                // Hook it back up
                add_action('save_post', 'do_saved_post', 10, 1);

                my_trigger_notice(4);

            }

        }
        do_my_log("🧮 Relative URL conversions: " . $num_rel_changes++);

    } else {
        // echo "Post ".$post_id." has no content.\n";
    }

    // do_my_log("Finished relative_body_imgs().");
    // do_my_log("🔚");

}

function localise_remote_images($post_id)
{
    /**
     * Localise Remote Images
     *
     * Slurps any remote images in a given post by downloading them to the media library
     * and updating the image src attribute to use a relative URL.
     *
     * @param int $post_id The ID of the post to be checked for remote images.
     *
     * @return void
     *
     * @throws Exception If there is an error downloading an image or updating the post.
     */

    do_my_log("🧩 localise_remote_images()...");

    $post_content = get_post_field('post_content', $post_id);

    if (!$post_content) {
        return;
    }

    $dom = do_get_content_as_dom($post_content);

    // Process both img tags and anchor tags that link to images
    $elements_to_check = array(
        array('tag' => 'img', 'attr' => 'src'),
        array('tag' => 'a', 'attr' => 'href'),
    );

    $num_localised = 0;

    foreach ($elements_to_check as $element_info) {
        $tags = $dom->getElementsByTagName($element_info['tag']);

        foreach ($tags as $tag) {
            $url = $tag->getAttribute($element_info['attr']);

            // Check if URL points to an image by looking at the file extension
            $is_image_url = preg_match('/\.(jpe?g|png|gif|webp)$/i', $url);

            // "Remote" URL i) starts with "http" but ii) not including http://www.yoursite.com
            if ($is_image_url && strpos($url, 'http') === 0 && strpos($url, home_url()) === false) {
                do_my_log("🎆 Found " . $element_info['tag'] . " " . $element_info['attr'] . " " . $url);

                // Check if we already have this image in the media library
                $existing_attachment = attachment_url_to_postid($url);
                if ($existing_attachment) {
                    do_my_log("Image already exists in media library with ID: " . $existing_attachment);
                    $tag->setAttribute($element_info['attr'], wp_get_attachment_url($existing_attachment));
                    continue;
                }

                // Download the image file contents
                $image_data = file_get_contents($url);

                if ($image_data) {
                    do_my_log("🛬 Downloaded file.");

                    // Check if the downloaded file is an image
                    $image_info = getimagesizefromstring($image_data);
                    if (!$image_info) {
                        do_my_log("❌ Not a valid image.");
                        continue;
                    }

                    // Generate path info
                    $image_info = pathinfo($url);
                    $image_name = $image_info['basename'];
                    // Generate uploads directory info
                    $upload_dir = wp_upload_dir();
                    $image_file = $upload_dir['path'] . '/' . $image_name;

                    if (file_put_contents($image_file, $image_data) !== false) {
                        do_my_log("Saved file to " . $image_file);

                        // Get the post date of the parent post
                        $post_date = get_post_field('post_date', $post_id);
                        // Create attachment post object
                        $attachment = array(
                            'post_title' => $image_name,
                            'post_mime_type' => wp_check_filetype($image_name)['type'],
                            'post_content' => '',
                            'post_status' => 'inherit',
                            'post_parent' => $post_id,
                            'post_date' => $post_date,
                            'post_date_gmt' => get_gmt_from_date($post_date),
                        );

                        // Insert the attachment into the media library
                        $attach_id = wp_insert_attachment($attachment, $image_file, $post_id);

                        // Set the attachment metadata
                        $attach_data = wp_generate_attachment_metadata($attach_id, $image_file);
                        wp_update_attachment_metadata($attach_id, $attach_data);

                        do_my_log("📎 Attachment created.");
                        $num_localised++;

                        // Replace the URL with the new attachment URL
                        $tag->setAttribute($element_info['attr'], wp_get_attachment_url($attach_id));

                        // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                        remove_action('save_post', 'do_saved_post', 10, 1);
                        // Update the post content
                        $post_content = $dom->saveHTML();
                        wp_update_post(array('ID' => $post_id, 'post_content' => $post_content));
                        do_my_log("✅ Updated post body.");
                        // Hook it back up
                        add_action('save_post', 'do_saved_post', 10, 1);
                    } else {
                        do_my_log("❌ File save failed.");
                    }
                } else {
                    do_my_log("❌ File download failed.");
                }
            } else {
                if ($is_image_url) {
                    do_my_log("🚫 Image not remote - " . $url);
                }
            }
        }
    }

    do_my_log("🧮 Localised images: " . $num_localised);
}

function delete_attached_images_on_post_delete($post_id)
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
    if ($settings['use_delete'] == 1) {

        // Log the entire $_REQUEST array
        /*
        do_my_log("Request: " . print_r($_REQUEST, true));
        do_my_log("Request URI: " . $_SERVER['REQUEST_URI']);
         */

        // if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
        if ((isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') || (isset($_REQUEST['delete_all']) && ($_REQUEST['delete_all'] == 'Empty Bin' || $_REQUEST['delete_all'] == 'Empty Trash'))) {

            // if (!isset($_REQUEST['delete_all']) && !wp_check_post_lock($post_id)) {

            $post = get_post($post_id);

            // When permanently deleting an attachment...
            if ($post->post_status == 'trash') {

                do_my_log("🗑 delete_attached_images_on_post_delete()...");

                $current_screen = get_current_screen();
                $screen_id = $current_screen ? $current_screen->id : '';
                do_my_log("Screen ID: " . $screen_id);

                // Get post's attachments and featured image
                $attachments = do_get_all_attachments($post_id);

                if (is_array($attachments) || is_object($attachments)) {

                    do_my_log("Attachments: " . count($attachments));

                    foreach ($attachments as $attachment) {
                        do_my_log("Checking " . $attachment->ID);
                        $used_elsewhere = is_attachment_used_elsewhere($attachment->ID, $post->ID);
                        // TODO: Only if URL not found in other posts
                        if ($used_elsewhere !== true) {
                            do_my_log("Will delete attachment with post");
                            do_delete_attachment($attachment->ID);
                        } else {
                            do_my_log("Attachment used elsewhere. Will not delete.");
                        }
                    }
                }
            }
            /*
        } else {
        // Second time firing
        }
         */
        }
    }
}
add_action('before_delete_post', 'delete_attached_images_on_post_delete');
