<?php

/**
 * Blog Theme Media Helpers
 *
 * Slideshow, gallery, download and thumbnail helpers for the Bootstrap
 * Blog theme. Extracted from the monolithic functions.php (Phase 5
 * remediation). All functions use function_exists() guards to avoid
 * redeclaration errors.
 *
 * @category Theme Function
 * @package Scriptlog
 */

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * get_slideshow() - Get posts with media for slideshow
 */
if (!function_exists('get_slideshow')) {
    function get_slideshow($limit = 5)
    {
        if (function_exists('medoo_init')) {
            $database = medoo_init();
            return $database->select('tbl_posts', [
                '[>]tbl_media' => ['media_id' => 'ID'],
                '[>]tbl_users' => ['post_author' => 'ID']
            ], [
                'tbl_posts.ID(post_id)',
                'tbl_posts.post_title',
                'tbl_posts.post_content',
                'tbl_posts.post_slug',
                'tbl_posts.post_summary',
                'tbl_posts.post_date(created_at)',
                'tbl_posts.post_modified(modified_at)',
                'tbl_media.media_filename',
                'tbl_media.media_caption',
                'tbl_users.user_fullname',
                'tbl_users.user_login'
            ], [
                'tbl_posts.post_status' => 'publish',
                'tbl_posts.post_type' => 'blog',
                'tbl_media.media_target' => 'blog',
                'tbl_media.media_access' => 'public',
                'tbl_media.media_status' => 1,
                'tbl_users.user_banned' => 0,
                'ORDER' => ['tbl_posts.post_date' => 'DESC'],
                'LIMIT' => $limit
            ]);
        }
        return [];
    }
}

/**
 * display_galleries() - Get gallery images
 */
if (!function_exists('display_galleries')) {
    function display_galleries($start, $limit)
    {
        $showcase = class_exists('FrontContentModel') ? FrontContentModel::frontGalleries(initialize_gallery(), $start, $limit) : "";
        return is_iterable($showcase) ? $showcase : array();
    }
}

/**
 * get_download_page_data() - Get download page data
 *
 * @param string $identifier
 * @return array
 */
if (!function_exists('get_download_page_data')) {
    function get_download_page_data($identifier)
    {
        if (empty($identifier)) {
            return ['error' => 'Invalid download identifier'];
        }

        try {
            $downloadController = class_exists('Registry') ? Registry::get('downloadController') : null;
            if (!$downloadController instanceof DownloadController) {
                if (!class_exists('DownloadService') || !class_exists('DownloadModel') || !class_exists('MediaDao')) {
                    return ['error' => 'Download system not available'];
                }
                $downloadController = new DownloadController(new DownloadService(new DownloadModel(), new MediaDao()));
            }
            return $downloadController->getDownloadPage($identifier);
        } catch (Exception $e) {
            error_log('Download page error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return ['error' => 'Unable to retrieve download information'];
        }
    }
}

/**
 * Get post thumbnail with fallback
 */
if (!function_exists('get_post_thumbnail')) {
    function get_post_thumbnail($post_img, $post_title, $img_alt = '')
    {
        if (empty($post_img) || $post_img === 'NULL') {
            // No image - use your default
            return '<img src="' . app_url() . '/public/files/pictures/nophoto.jpg" alt="' . theme_escape_html($post_title) . '" width="730" height="486" class="img-fluid" loading="lazy" decoding="async">';
        }

        // Check if image exists
        $image_check = invoke_image_uploaded($post_img, true);

        if ($image_check !== false) {
            // Image exists - use responsive image (hero/LCP: eager + high priority)
            return invoke_responsive_image($post_img, 'medium', true, !empty($img_alt) ? $img_alt : $post_title, 'img-fluid', true, 'lazy', 'eager');
        } else {
            // Image doesn't exist - use your default
            return '<img src="' . app_url() . '/public/files/pictures/nophoto.jpg" alt="' . theme_escape_html($post_title) . '" width="730" height="486" class="img-fluid" loading="lazy" decoding="async">';
        }
    }
}
