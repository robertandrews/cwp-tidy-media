/**
 * Handles the media tidying functionality for WordPress posts, from the posts list (posts.php).
 * This script manages the bulk processing of selected posts to tidy their media attachments.
 * It collects selected post IDs, sends them to the WordPress backend via AJAX,
 * shows loading states, and handles the response appropriately.
 */

// Wait for the DOM to be fully loaded before executing
jQuery(document).ready(function ($) {
    // Add click event handler to the "Tidy Media" button
    $('#tidy-media-button').on('click', function () {
        // Get an array of all checked post IDs from checkboxes
        var postIds = $.map($('input[name="post[]"]:checked'), function (c) {
            return $(c).val();
        });

        // Only proceed if at least one post is selected
        if (postIds.length) {
            // Store the current page URL for redirect after processing
            var currentPageUrl = window.location.href;

            // Make an AJAX request to process the selected posts
            $.ajax({
                url: tidy_media_params.ajax_url,  // WordPress AJAX endpoint
                type: 'POST',
                data: {
                    action: 'tidy_media',         // WordPress AJAX action hook
                    nonce: tidy_media_params.nonce, // Security nonce for verification
                    post_ids: postIds,            // Array of selected post IDs
                    current_page_url: currentPageUrl // URL to redirect back to
                },
                beforeSend: function () {
                    // Show a loading spinner while processing
                    $('#tidy-media-button').after('<span class="tidy-media-spinner spinner"></span>');
                },
                success: function (response) {
                    // Remove the loading spinner
                    $('.tidy-media-spinner').remove();
                    // Show success message
                    alert('Media tidy process completed successfully.');
                    // Redirect to the response URL (typically the current page)
                    window.location.href = response;
                },
                error: function (xhr, status, error) {
                    // Remove the loading spinner
                    $('.tidy-media-spinner').remove();
                    // Show error message with details
                    alert('An error occurred while processing the selected posts: ' + error);
                }
            });
        } else {
            // Alert if no posts were selected
            alert('Please select at least one post.');
        }
    });
});