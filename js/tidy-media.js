jQuery(document).ready(function ($) {
    $('#tidy-media-button').on('click', function () {
        var postIds = $.map($('input[name="post[]"]:checked'), function (c) {
            return $(c).val();
        });
        if (postIds.length) {
            $.ajax({
                url: tidy_media_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'tidy_media',
                    nonce: tidy_media_params.nonce,
                    post_ids: postIds
                },
                beforeSend: function () {
                    $('#tidy-media-button').after('<span class="tidy-media-spinner spinner"></span>');
                },
                success: function (response) {
                    $('.tidy-media-spinner').remove();
                    alert('Media tidy process completed successfully.');
                },
                error: function (xhr, status, error) {
                    $('.tidy-media-spinner').remove();
                    alert('An error occurred while processing the selected posts: ' + error);
                }
            });
        } else {
            alert('Please select at least one post.');
        }
    });
});
