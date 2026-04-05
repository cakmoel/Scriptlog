<?php

/**
 * invoke_responsive_image()
 *
 * Generates optimized HTML for responsive images with:
 * - WebP source with fallback
 * - Explicit width/height for CLS prevention
 * - Optional fetchpriority for LCP optimization
 *
 * @param string $media_filename - original filename from database
 * @param string $size - 'thumbnail', 'medium', 'large'
 * @param bool $image_thumb
 * @param string $alt
 * @param string $class
 * @param bool $fetchpriority
 * @param string $decoding
 * @return string
 */
function invoke_responsive_image(
    $media_filename,
    $size = 'thumbnail',
    $image_thumb = true,
    $alt = '',
    $class = 'img-fluid',
    $fetchpriority = false,
    $decoding = 'auto'
) {

    if (empty($media_filename) || $media_filename === 'nophoto') {
        return '<img src="https://via.placeholder.com/640x450" alt="' . htmlout($alt) . '" width="640" height="450" class="' . htmlout($class) . '" decoding="' . htmlout($decoding) . '">';
    }

    $file_basename = substr($media_filename, 0, strripos($media_filename, '.'));

    // Define sizes
    $sizes = [
        'thumbnail' => ['width' => 640, 'height' => 450, 'prefix' => 'small_', 'folder' => 'small'],
        'medium'    => ['width' => 730, 'height' => 486, 'prefix' => 'medium_', 'folder' => 'medium'],
        'large'     => ['width' => 1200, 'height' => 630, 'prefix' => 'large_', 'folder' => 'large'],
    ];

    $size_info = $sizes[$size] ?? $sizes['thumbnail'];
    $width = $size_info['width'];
    $height = $size_info['height'];
    $prefix = $size_info['prefix'];
    $folder = $size_info['folder'];

    // Paths to check - using APP_IMAGE constants
    $base_path = __DIR__ . '/../../' . APP_IMAGE;
    $webp_path = $base_path . $file_basename . '.webp';
    $sized_jpg = $base_path . $folder . DS . $prefix . basename($media_filename);
    $original_jpg = $base_path . basename($media_filename);

    // Check for WebP
    $has_webp = is_readable($webp_path);

    // Build image URL - prefer sized version
    if (is_readable($sized_jpg)) {
        $image_src = app_url() . DS . APP_IMAGE . $folder . DS . $prefix . rawurlencode(basename($media_filename));
    } elseif (is_readable($original_jpg)) {
        $image_src = app_url() . DS . APP_IMAGE . rawurlencode(basename($media_filename));
    } else {
        // No image found, return placeholder
        return '<img src="https://via.placeholder.com/' . $width . 'x' . $height . '" alt="' . htmlout($alt) . '" width="' . $width . '" height="' . $height . '" class="' . htmlout($class) . '">';
    }

    // Build WebP URL
    $webp_src = $has_webp ? app_url() . DS . APP_IMAGE . rawurlencode($file_basename . '.webp') : $image_src;

    $fetchpriority_attr = $fetchpriority ? ' fetchpriority="high"' : '';

    if ($has_webp) {
        return '<picture>
            <source srcset="' . $webp_src . '" type="image/webp">
            <img src="' . $image_src . '" alt="' . htmlout($alt) . '" width="' . $width . '" height="' . $height . '" class="' . htmlout($class) . '" decoding="' . htmlout($decoding) . '"' . $fetchpriority_attr . '>
        </picture>';
    } else {
        return '<img src="' . $image_src . '" alt="' . htmlout($alt) . '" width="' . $width . '" height="' . $height . '" class="' . htmlout($class) . '" decoding="' . htmlout($decoding) . '"' . $fetchpriority_attr . '>';
    }
}

/**
 * invoke_hero_image()
 *
 * Specialized function for hero/LCP images with fetchpriority="high"
 *
 * @param string $media_filename
 * @param string $fallback_url
 * @param string $alt
 * @return string
 */
function invoke_hero_image($media_filename, $fallback_url = '', $alt = '')
{
    if (empty($media_filename) || $media_filename === 'nophoto') {
        $url = !empty($fallback_url) ? $fallback_url : theme_dir() . 'assets/img/hero.jpg';
        return '<img src="' . htmlout($url) . '" alt="' . htmlout($alt) . '" fetchpriority="high" decoding="sync">';
    }

    $file_basename = substr($media_filename, 0, strripos($media_filename, '.'));

    // Paths using APP_IMAGE constants
    $base_path = __DIR__ . '/../../' . APP_IMAGE;
    $webp_path = $base_path . $file_basename . '.webp';
    $large_jpg = $base_path . 'large' . DS . 'large_' . basename($media_filename);
    $original_jpg = $base_path . basename($media_filename);

    $has_webp = is_readable($webp_path);

    if (is_readable($large_jpg)) {
        $image_src = app_url() . DS . APP_IMAGE_LARGE . 'large_' . rawurlencode(basename($media_filename));
    } elseif (is_readable($original_jpg)) {
        $image_src = app_url() . DS . APP_IMAGE . rawurlencode(basename($media_filename));
    } else {
        return '<img src="' . htmlout($fallback_url) . '" alt="' . htmlout($alt) . '" fetchpriority="high" decoding="sync">';
    }

    $webp_src = $has_webp ? app_url() . DS . APP_IMAGE . rawurlencode($file_basename . '.webp') : $image_src;

    if ($has_webp) {
        return '<picture>
            <source srcset="' . $webp_src . '" type="image/webp">
            <img src="' . $image_src . '" alt="' . htmlout($alt) . '" fetchpriority="high" decoding="sync">
        </picture>';
    } else {
        return '<img src="' . $image_src . '" alt="' . htmlout($alt) . '" fetchpriority="high" decoding="sync">';
    }
}

/**
 * invoke_gallery_image()
 *
 * Specialized function for gallery images with lazy loading
 *
 * @param string $media_filename
 * @param string $alt
 * @return string
 */
function invoke_gallery_image($media_filename, $alt = '')
{
    if (empty($media_filename) || $media_filename === 'nophoto') {
        return '<img src="https://via.placeholder.com/640x450" alt="' . htmlout($alt) . '" width="640" height="450" class="img-fluid" loading="lazy" decoding="async">';
    }

    $file_basename = substr($media_filename, 0, strripos($media_filename, '.'));

    // Paths using APP_IMAGE constants
    $base_path = __DIR__ . '/../../' . APP_IMAGE;
    $webp_path = $base_path . $file_basename . '.webp';
    $medium_jpg = $base_path . 'medium' . DS . 'medium_' . basename($media_filename);
    $original_jpg = $base_path . basename($media_filename);

    $has_webp = is_readable($webp_path);

    if (is_readable($medium_jpg)) {
        $image_src = app_url() . DS . APP_IMAGE_MEDIUM . 'medium_' . rawurlencode(basename($media_filename));
    } elseif (is_readable($original_jpg)) {
        $image_src = app_url() . DS . APP_IMAGE . rawurlencode(basename($media_filename));
    } else {
        return '<img src="https://via.placeholder.com/640x450" alt="' . htmlout($alt) . '" width="640" height="450" class="img-fluid" loading="lazy" decoding="async">';
    }

    $webp_src = $has_webp ? app_url() . DS . APP_IMAGE . rawurlencode($file_basename . '.webp') : $image_src;

    if ($has_webp) {
        return '<picture>
            <source srcset="' . $webp_src . '" type="image/webp">
            <img src="' . $image_src . '" alt="' . htmlout($alt) . '" width="640" height="450" class="img-fluid" loading="lazy" decoding="async">
        </picture>';
    } else {
        return '<img src="' . $image_src . '" alt="' . htmlout($alt) . '" width="640" height="450" class="img-fluid" loading="lazy" decoding="async">';
    }
}
