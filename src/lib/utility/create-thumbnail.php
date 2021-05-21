<?php
/**
 * resize_image
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param int|num $current_width
 * @param int|num $current_height
 * @param int|num $max_size
 * @param string $destination
 * @param GdImage|Obj $img_src
 * @param int $quality
 * @param string $img_type
 * @return bool
 * 
 */
function resize_image($current_width, $current_height, $max_size, $destination, $img_src, $quality, $img_type)
{

if ($current_width <= 0 || $current_height <= 0) {

    return false;

}

$img_scaled  = min($max_size/$current_width, $max_size/$current_height);
$new_width   = ceil($img_scaled*$current_width);
$new_height  = ceil($img_scaled*$current_height);
$new_canves = imagecreatetruecolor($new_width, $new_height);

// resize image
if (imagecopyresampled($new_canves, $img_src, 0, 0, 0, 0, $new_width, $new_height, $current_width, $current_height)) {

    switch (strtolower($img_type)) {

        case 'image/png':
            
            imagepng($new_canves, $destination);

            break;

        case 'image/gif':

            imagegif($new_canves, $destination);

            break;

        case 'image/jpeg':
        case 'image/pjpeg':
        case 'image/jpg':

            imagejpeg($new_canves, $destination, $quality);

            break;

        case 'image/webp':

            imagewebp($new_canves, $destination);

            break;
        
        default:
            
            return false;

            break;
        
    }

    if (is_resource($new_canves)) {

        imagedestroy($new_canves);

    }

    return true;

}

}

/**
 * crop_image
 *
 * @category functioin
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param int|num $current_width
 * @param int|num $current_height
 * @param int|num $new_size
 * @param string $destination
 * @param GdImage|Obj $img_src
 * @param int|num $quality
 * @param string $img_type
 * @return bool
 */
function crop_image($current_width, $current_height, $new_size, $destination, $img_src, $quality, $img_type)
{

if ($current_width <= 0 || $current_height <= 0) {

    return false;

} 

if ($current_width>$current_height) {

    $y_offset = 0;
    $x_offset = ($current_width - $current_height) / 2;
    $square_size = $current_width - ($x_offset * 2);

} else {

    $x_offset = 0;
    $y_offset = ($current_height - $current_width) / 2;
    $square_size = $current_height - ($y_offset * 2);

}

$new_canves = imagecreatetruecolor($new_size, $new_size);

if (imagecopyresampled($new_canves, $img_src, 0, 0, $x_offset, $y_offset, $new_size, $new_size, $square_size, $square_size)) {

    switch (strtolower($img_type)) {

        case 'image/png':
            
            imagepng($new_canves, $destination);

            break;
        
        case 'image/gif':

            imagegif($new_canves, $destination);

            break;

        case 'image/jpeg':
        case 'image/pjpeg':
        case 'image/jpg':

            imagejpeg($new_canves, $destination, $quality);

            break;

        case 'image/webp':

            imagewebp($new_canves, $destination);

            break;

        default:
            
            return false;

            break;

    }

    if (is_resource($new_canves)) {

        imagedestroy($new_canves);

    }
 
    return true;

}

}