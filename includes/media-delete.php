<?php

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
