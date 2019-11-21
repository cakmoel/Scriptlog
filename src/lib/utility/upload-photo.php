<?php
/**
 * Upload photo
 * 
 * @package SCRIPTLOG FUNCTIONS
 * @category lib\upload-photo.php
 * @param string $file_name
 * @param integer $width
 * @param integer $height
 * @param string $mode
 * 
 */
function upload_photo($file_location, $file_size, $file_type, $width, $height, $mode, $file_name)
{
    
    // picture directory
    $image_path = __DIR__ . '/../../public/files/pictures/';
    $image_path_thumb = __DIR__ . '/../../public/files/pictures/thumbs/';
    $image_uploaded = $image_path.$file_name;
    
    // save picture from resources
    
    if ($file_size > APP_FILE_SIZE) {
        
        move_uploaded_file($file_location, $image_uploaded);
        
        // resize picture
        $resizeImage = new Resize($image_uploaded);
        $resizeImage ->resizeImage($width, $height, $mode);
        $resizeImage ->saveImage($image_uploaded, 100);
               
    } else {
        
        move_uploaded_file($file_location, $image_uploaded);
        
    }
    
    // checking file type
    $img_source = null;
    
    if ($file_type == "image/jpeg") {
        
        $img_source = imagecreatefromjpeg($image_uploaded);
        
    } elseif ($file_type == "image/png") {
        
        $img_source = imagecreatefrompng($image_uploaded);
        
    } elseif ($file_type == "image/gif") {
        
        $img_source = imagecreatefromgif($image_uploaded);
        
    } elseif($file_type == "image/webp") {

        $img_source = imagecreatefromwebp($image_uploaded);

    }
    
    $source_width = imagesx($img_source);
    $source_height = imagesy($img_source);
    
    // set picture's size
    $set_width = 320;
    $set_height = (($set_width/$source_width) * $source_height);
    
    // process
    $img_processed = imagecreatetruecolor($set_width, $set_height);
    imagecopyresampled($img_processed, $img_source, 0, 0, 0, 0, $set_width, $set_height, $source_width, $source_height);
    
    // save picture's thumbnail
    if ($file_type == "image/jpeg") {

        header('Content-Type: image/jpeg');
        imagejpeg($img_processed, $image_path_thumb . "thumb_" . $file_name);
        
    } elseif ($file_type == "image/png") {
        
        header('Content-Type: image/png');
        imagepng($img_processed, $image_path_thumb . "thumb_" . $file_name);
        
    } elseif ($file_type == "image/gif") {
        
        header('Content-Type: image/gif');
        imagegif($img_processed, $image_path_thumb . "thumb_" . $file_name);
        
    } elseif ($file_type == "image/webp") {

        header('Content-Type: image/webp');
        imagewebp($img_processed, $image_path_thumb . "thumb_" . $file_name);

    } 
    
    // Delete Picture in computer's memory
    imagedestroy($img_source);
    imagedestroy($img_processed);
    
}