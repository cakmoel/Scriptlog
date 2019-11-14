<?php
/**
 * Upload Album
 * 
 * @param string $file_name
 * @param number $width
 * @param number $height
 * @param string $mode
 */
function upload_album($file_name, $width, $height, $mode)
{
    // picture directory
    $upload_path = __DIR__ . '/../../public/files/pictures/';
    $file_destination = $upload_path . $file_name;
    
    // save picture from resources
    move_uploaded_file($_FILES['image']['tmp_name'], $file_destination);
    
    // resize picture
    $resizeImage = new Resize($file_destination);
    $resizeImage ->resizeImage($width, $height, $mode);
    $resizeImage ->saveImage($file_destination, 100);
    
}