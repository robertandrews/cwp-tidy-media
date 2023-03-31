<?php

get_att_obj_from_filepath('/wp-content/uploads/2017/05/The-Future-of-mCommerce-eCommerce-Playbook-v1.pdf');

function get_att_obj_from_filepath($found_img_src)
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
