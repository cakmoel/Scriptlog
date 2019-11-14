<?php
/**
 * Upload file
 * 
 * @param string $filename
 * @param string $folder
 * 
 */
function upload_file($field_name, $folder)
{

 if (!is_dir('../../public/files/docs/'.$folder . DS)) {

    $file_path = mkdir('../../public/files/docs/'.$folder.DS);

 }

 move_uploaded_file($_FILES[$field_name]['tmp_name'], $file_path);
    
}