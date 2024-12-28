/**
 * Initializes the legacy domains management functionality when the DOM is loaded.
 * This section handles adding and removing domain fields in the legacy domains list.
 */
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.querySelector('.legacy-domains-wrapper');
    if (wrapper) {
        // Add new domain field
        wrapper.querySelector('.add-domain').addEventListener('click', function () {
            const list = wrapper.querySelector('.legacy-domains-list');
            const newItem = document.createElement('div');
            newItem.className = 'legacy-domain-item';
            newItem.innerHTML = `
                <input type="text"
                    name="domains_to_replace[]"
                    class="legacy-domain-input"
                    placeholder="https://oldsite.com" />
                <button type="button" class="button remove-domain" title="Remove domain">&minus;</button>
            `;
            list.appendChild(newItem);
        });

        // Remove domain field when minus button is clicked
        wrapper.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-domain')) {
                e.target.parentElement.remove();
            }
        });
    }
});

/**
 * Updates the preview paths based on selected organization options.
 * This function dynamically generates and displays the file path structure
 * based on various settings like:
 * - Post type organization
 * - Taxonomy organization
 * - Year/Month folders
 * - Post identifier (slug or ID)
 */
function updatePreviewPaths() {
    // Base upload directory path
    var basePath = '/wp-content/uploads';
    var postTypeEnabled = document.querySelector('[name="organize_post_img_by_type"]').checked;
    var taxonomySlug = document.querySelector('[name="organize_post_img_by_taxonomy"]:checked');
    var postIdentifier = document.querySelector('[name="organize_post_img_by_post_slug"]:checked')?.value || '';
    var uploadsUseYearMonthFolders = window.uploadsUseYearMonthFolders || false;

    var path = basePath;

    // Add post type to path if enabled
    if (postTypeEnabled) {
        path += '/<span style="color:#d63638">post_type</span>';
    }

    // Add taxonomy organization if selected
    if (taxonomySlug && taxonomySlug.value !== '') {
        path += '/<span style="color:#00a32a">' + taxonomySlug.value +
            '</span>/<span style="color:#2271b1">term_slug</span>';
    }

    // Add year/month folders if enabled
    if (uploadsUseYearMonthFolders) {
        var today = new Date();
        var year = today.getFullYear();
        var month = today.getMonth() + 1;
        var dateFolders = year + '/' + (month < 10 ? '0' + month : month);
        path += '/' + dateFolders;
    }

    // Add post identifier (slug or ID) if selected
    if (postIdentifier === 'slug') {
        path += '/<span style="color:#dba617">my-awesome-post</span>';
    } else if (postIdentifier === 'id') {
        path += '/<span style="color:#dba617">142</span>';
    }

    // Update preview path with colors
    var fullPreviewPath = window.uploadsBasePath + path + '/image.jpeg';
    document.querySelector('#planned-path').innerHTML = fullPreviewPath;

    // Update dynamic path examples (without colors)
    var plainPath = path.replace(/<[^>]+>/g, ''); // Strip HTML tags for the examples
    document.querySelectorAll('.dynamic-path').forEach(function (element) {
        element.textContent = plainPath;
    });
}

/**
 * Toggles the visibility of the relative URLs settings box
 * based on whether the use relative URLs option is checked.
 */
function toggleRelativeUrlsBox() {
    var useRelativeToggle = document.querySelector('[name="use_relative"]');
    var relativeUrlsBox = document.getElementById('relative-urls-settings');

    if (useRelativeToggle && relativeUrlsBox) {
        relativeUrlsBox.style.display = useRelativeToggle.checked ? 'block' : 'none';
    }
}

/**
 * Initializes all event listeners for the settings page when the DOM is loaded.
 * This includes:
 * - Path organization settings
 * - Relative URL toggle
 * - Initial path preview update
 */
document.addEventListener('DOMContentLoaded', function () {
    // Add event listeners for all relevant inputs
    document.querySelector('[name="organize_post_img_by_type"]').addEventListener('change', updatePreviewPaths);

    var radioButtons = document.querySelectorAll(
        '[name="organize_post_img_by_taxonomy"], [name="organize_post_img_by_post_slug"]');
    for (var i = 0; i < radioButtons.length; i++) {
        radioButtons[i].addEventListener('change', updatePreviewPaths);
    }

    // Add event listener for the relative URLs toggle
    var useRelativeToggle = document.querySelector('[name="use_relative"]');
    if (useRelativeToggle) {
        useRelativeToggle.addEventListener('change', toggleRelativeUrlsBox);
        // Initial toggle state
        toggleRelativeUrlsBox();
    }

    // Initial update of paths
    updatePreviewPaths();
}); 