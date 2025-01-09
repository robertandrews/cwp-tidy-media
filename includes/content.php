<?php

/**
 * Function: Create a DOMDocument object from HTML content, with proper UTF-8 encoding.
 *
 * This function takes HTML content, decodes HTML entities, and creates a properly
 * configured DOMDocument object. It handles UTF-8 encoding and disables warnings
 * and errors during HTML parsing.
 *
 * @param string|null $content The HTML content to convert to a DOM object
 * @return DOMDocument|null Returns a configured DOMDocument object if content exists, null otherwise
 */
function tidy_get_content_dom($content)
{
    if ($content) {
        // Set the encoding of the input HTML string
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Create a new DOMDocument object
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        $dom->encoding = 'UTF-8';
        // Load the post content into the DOMDocument object
        $dom->loadHTML($content, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        return $dom;
    }
}

/**
 * Function: Update Body Image URLs
 *
 * When a move_* operation updates (moves) a post's image, any _other_ posts which also include the image via <img src...> will
 * find it becomes 404.
 * This function will check for any posts which embed the just-updated image at its previous URL, and will update that
 * URL to the new location.
 * This does not run on the post which instigated the move, ie the sole post which is the post_parent of the attachment,
 * since this should have already been updated by tidy_do_reorg_body_media().
 *
 * @param int $post_id The ID of the starting post.
 * @param int $post_att_id The ID of the attachment post for the starting post.
 * @param array $old_image_details An array containing the details of the old image to be replaced.
 * @param array $new_image_details An array containing the details of the new image to replace the old image.
 * @return void This function does not return a value.
 */

function tidy_update_body_media_urls($post_id, $post_att_id, $old_image_details, $new_image_details)
{
    do_my_log("👉🏻 " . __FUNCTION__ . ":");

    // 1. Get the old URL we just updated - relative and absolute forms
    $search_url = isset($old_image_details['url_abs']) ? $old_image_details['url_abs'] : $old_image_details['url_rel'];

    // 2. Do a post query for that string
    $args = array(
        'post_type' => tidy_get_our_post_types(),
        'posts_per_page' => -1,
        // Only exclude current post if we're not dealing with a remote URL (i.e., if url_abs isn't set)
        'post__not_in' => !isset($old_image_details['url_abs']) ? array($post_id) : array(),
        's' => $search_url,
    );
    $query = new WP_Query($args);
    // do_my_log("-- " . $query->found_posts . " posts contain " . $search_url);

    // 3. Replace old string
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $current_post_id = get_the_ID();
            // do_my_log("📄 Processing post ID: " . $current_post_id . " - " . get_post_field('post_title', $current_post_id));
            do_my_log("Post ID: " . $current_post_id . " ('" . get_post_field('post_title', $current_post_id) . "') contains " . $search_url);

            // Get the post content and media elements
            $content = get_post_field('post_content', $current_post_id);
            $media_elements = get_post_body_media_elements($current_post_id);

            if (!empty($media_elements)) {
                do_my_log("🖼 Found " . count($media_elements) . " media elements in post content");
                $doc = tidy_get_content_dom($content);
                $modified = false;

                foreach ($media_elements as $element) {
                    $src = $element['src'];
                    do_my_log("🔍 Checking media src: " . $src);

                    // Check absolute URL first if it exists, then fall back to relative
                    if (isset($old_image_details['url_abs']) && strpos($src, $old_image_details['url_abs']) !== false) {
                        do_my_log("✅ Found match with url_abs: " . $old_image_details['url_abs']);
                        $new_src = str_replace($old_image_details['url_abs'], $new_image_details['url_rel'], $src);
                        do_my_log("🔄 Replacing with: " . $new_src);

                        // Find and update the element in the DOM
                        $elements = $doc->getElementsByTagName($element['tag']);
                        foreach ($elements as $el) {
                            if ($el->getAttribute('src') === $src) {
                                $el->setAttribute('src', $new_src);
                                $modified = true;
                            }
                        }
                    } else if (isset($old_image_details['url_rel']) && !empty($old_image_details['url_rel']) && strpos($src, $old_image_details['url_rel']) !== false) {
                        do_my_log("✅ Found match with url_rel: " . $old_image_details['url_rel']);
                        $new_src = str_replace($old_image_details['url_rel'], $new_image_details['url_rel'], $src);
                        do_my_log("🔄 Replacing with: " . $new_src);

                        // Find and update the element in the DOM
                        $elements = $doc->getElementsByTagName($element['tag']);
                        foreach ($elements as $el) {
                            if ($el->getAttribute('src') === $src) {
                                $el->setAttribute('src', $new_src);
                                $modified = true;
                            }
                        }
                    } else {
                        do_my_log("❌ No match found for this media element");
                    }
                }

                // Save the updated post if modifications were made
                if ($modified) {
                    do_my_log("💾 Content modified, saving updates...");
                    // Unhook catch_saved_post(), or wp_update_post() would cause an infinite loop
                    remove_action('save_post', 'catch_saved_post', 10, 1);
                    // Re-save the post
                    wp_update_post(array(
                        'ID' => $current_post_id,
                        'post_content' => $doc->saveHTML(),
                    ));
                    // Hook it back up
                    add_action('save_post', 'catch_saved_post', 10, 1);
                    do_my_log("✅ Post updated successfully");
                } else {
                    do_my_log("ℹ️ No content changes needed");
                }
            } else {
                do_my_log("ℹ️ No media elements found in post content");
            }
        }
    } else {
        do_my_log("ℹ️ No posts found containing the URL");
    }

    wp_reset_postdata();
}
