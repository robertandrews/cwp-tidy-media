# WP Tidy Media

WordPress' default `uploads` folder structure is not always the best fit for your site.

Chronological folders (eg. `wp-content/uploads/2023/03/image.jpeg`) do not suit every site.

This plugin offers custom media storage rules for the WordPress Media Library.

## Features

WP Tidy Media offers the following reorganisation features. Each can be enabled individually:

### 1. Pull down remote body images

All off-site images found in post body content will be pulled to your site. Applies to all `<img src=` URLs except your own site and specified "additional home domains". Organisation will be as per the other settings.

- Before: `<img src="https://www.example.com/wp-content/uploads/2023/03/image.jpeg">`
- After: `<img src="/wp-content/uploads/2023/03/image.jpeg">`

### 2. Convert local body image URLs from absolute to relative

By default, WordPress inserts images into post content using [absolute URLs](https://stackoverflow.com/a/21828923) (eg. `<img src="http://www.yourblog.com/wp-content/uploads/2023/03/image.jpeg">`). This can make site migration - and even local development of a deployed website - complicated, because images will appear broken. By contrast, [relative URLs](https://stackoverflow.com/a/21828923) will always point to the image, wherever you host the site.

Any of your own images called via absolute URLs will be replaced by a corresponding relative URL (eg. `<img src="/wp-content/uploads/2023/03/image.jpeg">`). This does not move images.

- Before: `<img src="http://www.yourblog.com/wp-content/uploads/2023/03/image.jpeg">`
- After: `<img src="/wp-content/uploads/2023/03/image.jpeg">`

### 3. Delete attachments upon post deletion

When a post is deleted, any attached media will also be deleted. Only deletes if attachment is unused elsewhere.

### 4. Reorganise media found in post content

All on-site image URLs found in post body content (whether attached or not) will be moved to your custom folder structure. `<img src` in post body will be updated accordingly.

- Before: `<img src="http://www.yourblog.com/wp-content/uploads/2023/03/image.jpeg">`
- After: `<img src="/wp-content/uploads/your_post_type/your_taxonomy/your_term_slug/2023/03/image.jpeg">`

### 5. Reorganise post attachments

Post-attached images will be moved to your specified custom folder structure, which can mirror your content structure. This includes Featured Image and other attachments.

- Before: `<img src="http://www.yourblog.com/wp-content/uploads/2023/03/image.jpeg">`
- After: `<img src="/wp-content/uploads/your_post_type/your_taxonomy/your_term_slug/2023/03/image.jpeg">`

(This setting is separate from "Reorganise media found in post content" because an image in post content does not necessarily have to be attached to the post).

## Operation

WP Tidy Media can be used in two ways:

1. Run on every post save (optional).
2. Run in bulk on multiple posts from the Posts list, via a button.

Running on every post save ensures the library is kept organised in the future.

Running in bulk can reorganise your Media Library retrospectively. If you change your mind about bulk tidy operations, you can change your custom file path after a batch run, and then run again - attachments will be moved again.

### Post attachment custom filepath

| Setting                | Option                                                          |
| ---------------------- | --------------------------------------------------------------- |
| Organise by post type? | Enable/disable (creates folder for each post type)              |
| Organise by taxonomy   | None, or choose from available public taxonomies                |
| Use date folders       | (Set in Media Settings) Creates YYYY/MM folders                 |
| Organise by post       | None, Post slug (eg. `my-awesome-post`), or Post ID (eg. `142`) |

### Additional domains to make relative

| Setting                 | Option                                                                                                   |
| ----------------------- | -------------------------------------------------------------------------------------------------------- |
| Additional home domains | Add any other hostnames/domains to strip out (for users who may have `<img src` URLs from legacy sites). |

## Functionality

### Types of attachments

The plugin will act on all three kinds of image files an attachment can represent:

1. `full` size image file (the main web-ready version)
2. `sizes` (various resized versions like `thumbnail`, `medium`, `large`)
3. `original_image` (only present for large uploads since WP 5.3)

[Since WordPress5.3](https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/), when an uploaded image is over 2560px in height or width, WordPress downsizes the primary file it will serve and also retains the original as `original_image`.

### Moving attachments

Moving the files alone to your custom folder path is insufficient. This plugin also makes changes to attachments' corresponding database records:

**wp_postmeta**:

- **_wp_attachment_metadata**: Updates the serialised array to reflect new file location.
  ```php
  // Before:
  array(
      'file' => '2023/03/image.jpeg',
      'sizes' => array(
          'thumbnail' => array(
              'file' => '2023/03/image-150x150.jpeg'
          )
      )
  )

  // After:
  array(
      'file' => 'products/clothing/t-shirts/image.jpeg',
      'sizes' => array(
          'thumbnail' => array(
              'file' => 'products/clothing/t-shirts/image-150x150.jpeg'
          )
      )
  )
  ```
- **_wp_attached_file**: Updates the relative image path.
    ```php
  // Before:
  '2023/03/image.jpeg'
  
  // After:
  'products/clothing/t-shirts/image.jpeg'
  ```

**wp_posts**:

- **post_parent**: Where an image is not already attached to another post and is designated to be attached to the current post, this attachment occurs by setting the post's ID as the attachment's post_parent.
  ```php
  // Before:
  array(
      'ID' => 123,          // Attachment ID
      'post_parent' => 0    // Not attached to any post
  )

  // After:
  array(
      'ID' => 123,          // Attachment ID
      'post_parent' => 456  // Now attached to post ID 456
  )
  ```
- **post_date**: Where a change is being made to an attachment (ie. a reorganisation, or a new image is added to the Media Library), it will take on the date of the post itself. This is to avoid flooding the Media Library with new items.
  ```php
  // Before:
  array(
      'ID' => 123,          // Attachment ID
      'post_date' => '2024-03-20 15:30:00'    // Date attachment was uploaded
  )

  // After:
  array(
      'ID' => 123,          // Attachment ID
      'post_date' => '2023-06-15 09:45:00'    // Now matches parent post's date
  )
  ```
- **guid**: Where an attachment is being moved, its guid (unique indicator) field will also be updated. Only the sub-directory portion is changed, the domain is not changed. Note: WordPress developers [advise against](https://wordpress.org/documentation/article/changing-the-site-url/#important-guid-note) changing guid for posts. This plugin runs on "attachment" post objects, for which guid is somewhat inconsequential, and is designed primarily for site overhauls which aim to run correct guids.
    ```php
  // Before:
  array(
      'ID' => 123,          // Attachment ID
      'guid' => 'http://www.yourblog.com/wp-content/uploads/2023/03/image.jpeg'
  )

  // After:
  array(
      'ID' => 123,          // Attachment ID
      'guid' => 'http://www.yourblog.com/wp-content/uploads/products/clothing/t-shirts/image.jpeg'
  )  ```

### Order of operation

Features are run logically in this order:

1. Pull down remote body images.
2. Convert local body image URLs from absolute to relative.
3. Reorganise media found in post content.
4. Reorganise post attachments.

Attachment deletion with post deletion occurs at time of post deletion, if enabled.

## Installation

1. Upload the `wp-tidy-media` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access "Tidy Media" settings from beneath the core "Media" menu.

## Storage

WP Tidy Media does not store settings in `wp_options` or any core WordPress database table.

For neatness, it uses its own distinct table, `wp_tidy_media_organizer`.

This table is created on plugin activation, and a small number of settings is set.

The following settings are stored in the `wp_tidy_media_organizer` table:

| Setting Name                   | Example Value                    | Description                                                             |
| ------------------------------ | -------------------------------- | ----------------------------------------------------------------------- |
| organize_post_img_by_type      | 1                                | Creates folders for each post type (e.g., 'posts/', 'products/')        |
| organize_post_img_by_taxonomy  | 'category'                       | Organizes by taxonomy terms (e.g., 'news/', 'reviews/')                 |
| organize_post_img_by_post_slug | 0                                | When enabled, creates folders using post slugs (e.g., 'my-first-post/') |
| domains_to_replace             | 'old-site.com, staging.site.com' | Additional domains to convert to relative URLs                          |
| use_tidy_attachments           | 1                                | Enables reorganizing post attachments                                   |
| use_tidy_body_media            | 1                                | Enables reorganizing media found in post content                        |
| use_relative                   | 1                                | Converts absolute URLs to relative URLs                                 |
| use_localise                   | 1                                | Downloads and stores remote images locally                              |
| use_delete                     | 1                                | Deletes attachments when their parent post is deleted                   |
| use_log                        | 1                                | Logs operations to wp-tidy-media.log                                    |
| run_on_save                    | 1                                | Runs operations automatically when posts are saved                      |
| organize_term_attachments      | 1                                | Enables organizing media by taxonomy terms                              |

All boolean settings use '1' for enabled and '0' for disabled. The plugin activates with most features enabled by default.

## Removal

The plugin tidies up after itself. Upon deletion, the table `wp_tidy_media_organizer` is deleted. This removes all traces of the plugin.

Removal will not affect changes already made by this plugin - any attachments already moved will remain in their new locations.

## Warning

Deletion of the plugin will not revert attachments to their prior state.

Running these operations may pose risks to your Media Library.

Make a back-up of your site before you run this plugin.

The author cannot be held liable for any data loss.

## License

This plugin is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
