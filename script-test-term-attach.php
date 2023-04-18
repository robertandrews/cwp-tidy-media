<?php

$term_id = 8480;
$term = get_term($term_id);
print_r($term);

$term_meta = get_term_meta($term_id);
print_r($term_meta);

foreach ($term_meta as $key => $value) {

    echo "Term Meta contains " . $key . " " . $value[0] . "\n";

    if (is_numeric($value[0])) {
        $number_found = $value[0];

        // Is it an attachment?
        if (check_id_for_attachment($number_found)) {

            echo "Found attachment there.\n";

            // check_term_img_for_move();
            $attachment_path = get_attached_file($number_found);
            $post_attachment = get_post($number_found);

            // print_r($post_attachment);

            term_img_move_controller($post_attachment, $term);

        }
    }

}
