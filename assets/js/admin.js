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

        // Remove domain field
        wrapper.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-domain')) {
                e.target.parentElement.remove();
            }
        });
    }
});

function updatePaths() {
    var basePath = '/wp-content/uploads';
    var postTypeEnabled = document.querySelector('[name="organize_post_img_by_type"]').checked;
    var taxonomySlug = document.querySelector('[name="organize_post_img_by_taxonomy"]:checked');
    var postIdentifier = document.querySelector('[name="organize_post_img_by_post_slug"]:checked')?.value || '';
    var uploadsUseYearMonthFolders = window.uploadsUseYearMonthFolders || false;

    var path = basePath;

    if (postTypeEnabled) {
        path += '/<span style="color:#d63638">post_type</span>';
    }

    if (taxonomySlug && taxonomySlug.value !== '') {
        path += '/<span style="color:#00a32a">' + taxonomySlug.value +
            '</span>/<span style="color:#2271b1">term_slug</span>';
    }

    if (uploadsUseYearMonthFolders) {
        var today = new Date();
        var year = today.getFullYear();
        var month = today.getMonth() + 1;
        var dateFolders = year + '/' + (month < 10 ? '0' + month : month);
        path += '/' + dateFolders;
    }

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

function toggleRelativeUrlsBox() {
    var useRelativeToggle = document.querySelector('[name="use_relative"]');
    var relativeUrlsBox = document.getElementById('relative-urls-settings');

    if (useRelativeToggle && relativeUrlsBox) {
        relativeUrlsBox.style.display = useRelativeToggle.checked ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Add event listeners for all relevant inputs
    document.querySelector('[name="organize_post_img_by_type"]').addEventListener('change', updatePaths);

    var radioButtons = document.querySelectorAll(
        '[name="organize_post_img_by_taxonomy"], [name="organize_post_img_by_post_slug"]');
    for (var i = 0; i < radioButtons.length; i++) {
        radioButtons[i].addEventListener('change', updatePaths);
    }

    // Add event listener for the relative URLs toggle
    var useRelativeToggle = document.querySelector('[name="use_relative"]');
    if (useRelativeToggle) {
        useRelativeToggle.addEventListener('change', toggleRelativeUrlsBox);
        // Initial toggle state
        toggleRelativeUrlsBox();
    }

    // Initial update
    updatePaths();
}); 