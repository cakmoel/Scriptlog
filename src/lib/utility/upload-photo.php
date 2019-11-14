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
function upload_photo($field_name, $width, $height, $mode, $folder)
{
    
    // picture directory
    if(!is_dir('../../public/files/pictures/'.$folder . DIRECTORY_SEPARATOR)) {

        if(!is_writable('../../public/files/pictures/'.$folder . DIRECTORY_SEPARATOR)) {

            scriptlog_error('Directory destination is not writable', E_NOTICE);

        } else {


           $upload_path = mkdir('../../public/files/pictures/'.$folder . DIRECTORY_SEPARATOR);

           $upload_path_thumb = mkdir('../../public/files/pictures/'.$folder. DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR);
        
        }
        
    }
    
    $file_uploaded = $upload_path . $_FILES[$field_name]['name'];
    $file_type = $_FILES[$field_name]['type'];
    $file_size = $_FILES[$field_name]['size'];
    
    // save picture from resources
    
    if ($file_size > 524867) {
        
        move_uploaded_file($_FILES[$field_name]['tmp_name'], $file_uploaded);
        
        // resize picture
        $resizeImage = new Resize($file_uploaded);
        $resizeImage ->resizeImage($width, $height, $mode);
        $resizeImage ->saveImage($file_uploaded, 100);
               
    } else {
        
        move_uploaded_file($_FILES[$field_name]['tmp_name'], $file_uploaded);
        
    }
    
    // checking file type
    $img_source = null;
    
    if ($file_type == "image/jpeg") {
        
        $img_source = imagecreatefromjpeg($file_uploaded);
        
    } elseif ($file_type == "image/png") {
        
        $img_source = imagecreatefrompng($file_uploaded);
        
    } elseif ($file_type == "image/jpg") {
        
        $img_source = imagecreatefromjpeg($file_uploaded);
        
    } elseif ($file_type == "image/gif") {
        
        $img_source = imagecreatefromgif($file_uploaded);
        
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
    if ($_FILES['image']['type'] == "image/jpeg") {
        
        imagejpeg($img_processed, $upload_path_thumb . "thumb_" . $file_name);
        
    } elseif ($_FILES['image']['type'] == "image/png") {
        
        imagepng($img_processed, $upload_path_thumb . "thumb_" . $file_name);
        
    } elseif ($_FILES['image']['type'] == "image/gif") {
        
        imagegif($img_processed, $upload_path_thumb . "thumb_" . $file_name);
        
    } elseif ($_FILES['image']['type'] == "image/jpg") {
        
        imagejpeg($img_processed, $upload_path_thumb . "thumb_" . $file_name);
        
    }
    
    // Delete Picture in computer's memory
    imagedestroy($img_source);
    imagedestroy($img_processed);
    
}