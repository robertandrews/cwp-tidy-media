<?php

function do_get_content_as_dom($content)
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

function update_body_img_urls($post_id, $post_att_id, $old_image_details, $new_image_details)
{
/**
 * Update Body Image URLs
 *
 * When a move_* operation updates (moves) a post's image, any _other_ posts which also include the image via <img src...> will
 * find it becomes 404.
 * This function will check for any posts which embed the just-updated image at its previous URL, and will update that
 * URL to the new location.
 * This does not run on the post which instigated the move, ie the sole post which is the post_parent of the attachment,
 * since this should have already been updated by tidy_body_media().
 *
 * @param int $post_id The ID of the starting post.
 * @param int $post_att_id The ID of the attachment post for the starting post.
 * @param array $old_image_details An array containing the details of the old image to be replaced.
 * @param array $new_image_details An array containing the details of the new image to replace the old image.
 * @return void This function does not return a value.
 */

    do_my_log("🧩 update_body_img_urls()...");

    // 1. Get the old URL we just updated - relative and absolute forms
    // $old_image_details['url_rel']

    // 2. Do a post query for that string
    // do_my_log("looking for " . $old_image_details['url_rel']);
    $args = array(
        'post_type' => our_post_types(),
        'posts_per_page' => -1,
        'post__not_in' => array($post_id), // omit the starting post, which was already updated
        's' => $old_image_details['url_rel'],
    );
    $query = new WP_Query($args);

    // 3. Replace old string

    // The Loop
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // Get the post content
            $content = get_post_field('post_content', $post_id);

            $doc = do_get_content_as_dom($content);

            // Find all img tags in the post content
            $images = $doc->getElementsByTagName('img');

            foreach ($images as $img) {

                // Get the src attribute of the img tag
                $src = $img->getAttribute('src');

                // If old URL form is in the src
                if (strpos($src, $old_image_details['url_rel']) !== false) { // was: if ($src === $old_image_details['url_rel']) {

                    // do_my_log("Found old relative URL " . $src ." in '". get_post_field('post_title', get_the_ID()) ."'. Need to replace img src with ".$new_image_details['url_rel']);
                    $new_src = str_replace($old_image_details['url_rel'], $new_image_details['url_rel'], $src);
                    // do_my_log("New is ".$new_src);
                    do_my_log("Updating img src " . $src . " in '" . get_post_field('post_title', get_the_ID()) . "' to " . $new_image_details['url_rel']);

                    $img->setAttribute('src', $new_src);
                    $new_content = $doc->saveHTML();

                }
            }

            // Save the updated post, if updates occurred
            if ($new_content !== $content) {
                $modified = true;
                $content = $new_content;
            }
            if ($modified == true) { // was if ($new_content) {
                // do_my_log("Updating '". get_post_field('post_title', get_the_ID()) ."'");
                // Unhook do_saved_post(), or wp_update_post() would cause an infinite loop
                remove_action('save_post', 'do_saved_post', 10, 1);
                // Re-save the post
                wp_update_post(array(
                    'ID' => get_the_ID(),
                    'post_content' => $content,
                ));
                // Hook it back up
                add_action('save_post', 'do_saved_post', 10, 1);
                // do_my_log("Done.");
            }

        }
    } else {
        // no posts found
        // do_my_log("👍🏻 No posts containing old URL.");
    }

}
