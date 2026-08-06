<?php

/**
 * photo_instance()
 *
 * @category function
 * @author  Oliver Vogel
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @see http://image.intervention.io/
 * @see https://anchetawern.github.io/blog/2016/02/18/using-the-intervention-image-library-in-php/
 * @see https://www.tutmecode.com/php/create-thumbnail-from-big-size-image-in-php-or-laravel/
 *
 */

use Intervention\Image\ImageManager;

function photo_instance()
{
    // Currently you can choose between gd and imagick
    return new ImageManager(['driver' => 'gd']);
}

/**
 * upload_photo()
 *
 * uploading picture
 * if fileinfo enabled then Intervention Image works properly else will implement non-secure approach
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $file_location
 * @param string $file_size
 * @param string $file_type
 * @param string $file_name
 * @return false|void
 *
 */
function upload_photo($file_location, $file_size, $file_type, $file_name)
{

    $small_size = 320;
    $medium_size = 640;
    $large_size = 770;
    $temp_src = $file_location;
    $img_ext = get_file_extension($file_name);
    $img_name = pathinfo($file_name, PATHINFO_BASENAME);
    $img_source = null;

    $format = get_image_format(strtolower($file_type));
    if ($format === null) {
        scriptlog_error("Unsupported File!");
        return;
    }

    if ($format === 'png') {
        $img_source = imagecreatefrompng($file_location);
        imagepalettetotruecolor($img_source);
        imagealphablending($img_source, true);
        imagesavealpha($img_source, true);
    } elseif ($format === 'gif') {
        $img_source = imagecreatefromgif($file_location);
    } elseif ($format === 'jpeg') {
        $img_source = imagecreatefromjpeg($file_location);
    } elseif ($format === 'webp') {
        $img_source = imagecreatefromwebp($file_location);
    } elseif ($format === 'bmp') {
        $img_source = imagecreatefrombmp($file_location);
    }

    list($current_width, $current_height) = getimagesize($temp_src);

    // construct new name
    $large_thumb_name = 'large_' . $img_name;
    $medium_thumb_name = 'medium_' . $img_name;
    $small_thumb_name = 'small_' . $img_name;

    // picture directory
    $origin_path = __DIR__ . '/../../' . APP_IMAGE;
    $origin_path_uploaded = $origin_path . $file_name;

    $small_path = __DIR__ . '/../../' . APP_IMAGE_SMALL;
    create_directory($small_path);
    $small_path_uploaded = $small_path . $small_thumb_name;

    $medium_path = __DIR__ . '/../../' . APP_IMAGE_MEDIUM;
    create_directory($medium_path);
    $medium_path_uploaded = $medium_path . $medium_thumb_name;

    $large_path = __DIR__ . '/../../' . APP_IMAGE_LARGE;
    create_directory($large_path);
    $large_path_uploaded = $large_path . $large_thumb_name;

    if (!(extension_loaded('fileinfo') || function_exists('finfo_open') || class_exists('finfo'))) {
        if (!$img_source) {
            return false;
        }

        if (resize_image($current_width, $current_height, $medium_size, $medium_path_uploaded, $img_source, 80, $file_type)) {
            if (!crop_image($current_width, $current_height, $small_size, $small_path_uploaded, $img_source, 80, $file_type)) {
                scriptlog_error("Error Creating small size of thumbnail!");
            }

            if (!move_uploaded_file($temp_src, $origin_path_uploaded)) {
                scriptlog_error("Error uploading picture");
            }

            // creating large size thumbnail
            $large_size_thumb = new \Scriptlog\Core\Resize($origin_path_uploaded);
            $large_size_thumb->resizeImage($large_size, 400, "crop");
            $large_size_thumb->saveImage($large_path_uploaded, 80);
        }
    } else {
        if ($img_ext == "jpeg" || $img_ext == "jpg" || $img_ext == "png" || $img_ext == "gif" || $img_ext == "bmp") {
            if (false === set_webp_origin($current_width, $current_height, $file_location, $file_size, $origin_path_uploaded, $origin_path, $file_name)) {
                scriptlog_error("Error creating origin size of webp image format", E_USER_WARNING);
            }

            if (false === set_webp_regular($current_width, $current_height, $origin_path_uploaded, $large_path, $file_name)) {
                scriptlog_error("Error creating regular size of webp image format", E_USER_WARNING);
            }

            if (false === set_webp_medium($current_width, $current_height, $origin_path_uploaded, $medium_path, $file_name)) {
                scriptlog_error("Error creating medium size of webp image format", E_USER_WARNING);
            }

            if (false === set_webp_small($current_width, $current_height, $origin_path_uploaded, $small_path, $file_name)) {
                scriptlog_error("Error creating small of webp image format", E_USER_WARNING);
            }
        }
    }

    // save origin picture (skip when fileinfo path already moved file via set_webp_origin)
    if (!(extension_loaded('fileinfo') || function_exists('finfo_open') || class_exists('finfo'))) {
        if (false === set_origin_photo($current_width, $current_height, $file_location, $file_size, $origin_path_uploaded)) {
            scriptlog_error("Error uploading picture", E_USER_WARNING);
        }
    }

    // crop to regular size
    if (false === set_regular_photo($current_width, $current_height, $origin_path_uploaded, $large_path, $file_name, $file_type)) {
        scriptlog_error("Error creating regular size of picture", E_USER_WARNING);
    }

    // crop to medium size
    if (false === set_medium_photo($current_width, $current_height, $origin_path_uploaded, $medium_path, $file_name, $file_type)) {
        scriptlog_error("Error creating medium size of picture", E_USER_WARNING);
    }

    // crop to smaller size
    if (false === set_small_photo($current_width, $current_height, $origin_path_uploaded, $small_path, $file_name, $file_type)) {
        scriptlog_error("Error creating smaller size of picture", E_USER_WARNING);
    }
}

/**
 * set_origin_photo
 *
 * @category Function
 * @param int|num $current_width
 * @param int|num $current_height
 * @param string $file_location
 * @param int|num $file_size
 * @param string $file_name
 * @param string $file_path_uploaded
 * @param string $file_type
 *
 */
function set_origin_photo($current_width, $current_height, $file_location, $file_size, $file_path_uploaded)
{

    if ($current_width <= 0 || $current_height <= 0) {
        return false;
    }

    if (move_uploaded_file($file_location, $file_path_uploaded) && filesize($file_path_uploaded) !== $file_size) {
        unlink($file_path_uploaded);
    }
}

/**
 * set_webp_origin
 *
 * @category Function
 * @param int|numeric $current_width
 * @param int|numeric $current_height
 * @param string $file_location
 * @param string $file_size
 * @param string $origin_path_uploaded
 * @param string $origin_path
 * @param string $file_name
 * @return false|true
 *
 */
function set_webp_origin($current_width, $current_height, $file_location, $file_size, $origin_path_uploaded, $origin_path, $file_name)
{

    if ($current_width <= 0 || $current_height <= 0) {
        return false;
    }

    if (!move_uploaded_file($file_location, $origin_path_uploaded)) {
        return false;
    }

    if (filesize($origin_path_uploaded) !== $file_size) {
        unlink($origin_path_uploaded);
        return false;
    }

    // get filename
    $file_basename = substr($file_name, 0, strripos($file_name, '.'));

    $origin_webp = photo_instance()->make($origin_path_uploaded);
    if ($origin_webp->save($origin_path . $file_basename . '.webp', 80, 'webp')) {
        $origin_webp->destroy();
        return true;
    }
}

/**
 * set_regular_photo
 *
 * @param int|num $current_width
 * @param int|num $current_height
 * @param string $file_path_uploaded
 * @param string $file_path_thumb
 * @param string $file_name
 * @param string $file_type
 *
 */
function set_regular_photo($current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name, $file_type)
{

    $regular_size = 770;

    if ($current_width <= 0 || $current_height <= 0) {
        return false;
    }

    $regular_scaled = min($regular_size / $current_width, $regular_size / $current_height);
    $new_width = ceil($regular_scaled * $current_width);
    $new_height = ceil($regular_scaled * $current_height);

    $regular_photo = photo_instance()->make($file_path_uploaded);
    $regular_photo->fit($new_width, $new_height);

    $format = get_image_format($file_type);
    if ($format === null) {
        return false;
    }

    $saveFormat = ($format === 'jpeg') ? 'jpg' : $format;
    if ($regular_photo->save($file_path_thumb . 'large_' . $file_name, 80, $saveFormat)) {
        $regular_photo->destroy();
        return true;
    }
}

/**
 * set_webp_regular
 *
 * @category Function
 * @param int|num $current_width
 * @param int|num $current_height
 * @param string $file_path_uploaded
 * @param string $file_path_thumb
 * @param string $file_name
 *
 */
function set_webp_regular($current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name)
{
    $regular_size = 770;

    if ($current_width <= 0 || $current_height <= 0) {
        return false;
    }

    // get filename
    $file_basename = substr($file_name, 0, strripos($file_name, '.'));

    $regular_scaled = min($regular_size / $current_width, $regular_size / $current_height);
    $new_width = ceil($regular_scaled * $current_width);
    $new_height = ceil($regular_scaled * $current_height);

    $regular_webp = photo_instance()->make($file_path_uploaded);
    $regular_webp->fit($new_width, $new_height);
    if ($regular_webp->save($file_path_thumb . 'large_' . $file_basename . '.webp', 80, 'webp')) {
        $regular_webp->destroy();
        return true;
    }
}

/**
 * set_medium_photo
 *
 * Create a medium-sized version of an uploaded image using Intervention.
 *
 * @category Function
 * @param int|numeric $current_width
 * @param int|numeric $current_height
 * @param string $file_path_uploaded
 * @param string $file_path_thumb
 * @param string $file_name
 * @param string $file_type
 * @return bool
 */
function set_medium_photo($current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name, $file_type)
{

    $medium_size = 640;

    if ($current_width <= 0 || $current_height <= 0) {
        return false;
    }

    $medium_scaled = min($medium_size / $current_width, $medium_size / $current_height);
    $new_width = ceil($medium_scaled * $current_width);
    $new_height = ceil($medium_scaled * $current_height);

    $medium_photo = photo_instance()->make($file_path_uploaded);
    $medium_photo->fit($new_width, $new_height);

    $format = get_image_format($file_type);
    if ($format === null) {
        return false;
    }

    $saveFormat = ($format === 'jpeg') ? 'jpg' : $format;
    if ($medium_photo->save($file_path_thumb . 'medium_' . $file_name, 80, $saveFormat)) {
        $medium_photo->destroy();
        return true;
    }

    return false;
}

// setting medium size of webp image format
function set_webp_medium($current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name)
{

    $medium_size = 640;

    if ($current_width <= 0 || $current_height <= 0) {
        return false;
    }

    // get filename
    $file_basename = substr($file_name, 0, strripos($file_name, '.'));

    $medium_scaled = min($medium_size / $current_width, $medium_size / $current_height);
    $new_width = ceil($medium_scaled * $current_width);
    $new_height = ceil($medium_scaled * $current_height);

    $medium_webp = photo_instance()->make($file_path_uploaded);
    $medium_webp->fit($new_width, $new_height);
    if ($medium_webp->save($file_path_thumb . 'medium_' . $file_basename . '.webp', 80, 'webp')) {
        $medium_webp->destroy();
        return true;
    }
}

/**
 * set_small_photo
 *
 * Create a small-sized version of an uploaded image using Intervention.
 *
 * @category Function
 * @param int|numeric $current_width
 * @param int|numeric $current_height
 * @param string $file_path_uploaded
 * @param string $file_path_thumb
 * @param string $file_name
 * @param string $file_type
 * @return bool
 */
function set_small_photo($current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name, $file_type)
{

    $small_size = 320;

    if ($current_width <= 0 || $current_height <= 0) {
        return false;
    }

    $small_scaled = min($small_size / $current_width, $small_size / $current_height);
    $new_width = ceil($small_scaled * $current_width);
    $new_height = ceil($small_scaled * $current_height);

    $small_photo = photo_instance()->make($file_path_uploaded);
    $small_photo->fit($new_width, $new_height);

    $format = get_image_format($file_type);
    if ($format === null) {
        return false;
    }

    $saveFormat = ($format === 'jpeg') ? 'jpg' : $format;
    if ($small_photo->save($file_path_thumb . 'small_' . $file_name, 80, $saveFormat)) {
        $small_photo->destroy();
        return true;
    }

    return false;
}

// setting smaller size of webp image format
function set_webp_small($current_width, $current_height, $file_path_uploaded, $file_path_thumb, $file_name)
{

    $small_size = 320;

    if ($current_width <= 0 || $current_height <= 0) {
        return false;
    }

    // get filename
    $file_basename = substr($file_name, 0, strripos($file_name, '.'));

    $small_scaled = min($small_size / $current_width, $small_size / $current_height);
    $new_width = ceil($small_scaled * $current_width);
    $new_height = ceil($small_scaled * $current_height);

    $small_webp = photo_instance()->make($file_path_uploaded);
    $small_webp->fit($new_width, $new_height);
    if ($small_webp->save($file_path_thumb . 'small_' . $file_basename . '.webp', 80, 'webp')) {
        $small_webp->destroy();
        return true;
    }
}
