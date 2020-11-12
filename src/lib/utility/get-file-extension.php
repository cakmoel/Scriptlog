<?php
/**
 * get_file_extension
 *
 * @category Function
 * @param string $file_name
 * @return string
 * 
 */
function get_file_extension($file_name)
{

$file_info = new SplFileInfo($file_name);
$file_ext = $file_info->getExtension();

if (($file_ext == file_extension_str($file_name)) || ($file_ext == file_extension_pathinfo($file_name)) || 
    ($file_ext == file_extension_explode($file_name)) || ($file_ext == file_extension_end($file_name)) ) {

    return $file_ext;

} else {

    return false;
}
 
}

function file_extension_explode($file_name)
{

$extension = [];

$extension = explode('.', $file_name);

return $extension[1];

}

function file_extension_end($file_name)
{

$extension = end(explode(".", $file_name));

return $extension;

}

function file_extension_pathinfo($file_name)
{

$extension = pathinfo($file_name, PATHINFO_EXTENSION);

return $extension;

}

function file_extension_str($file_name)
{

$extension = substr(strrchr($file_name,'.'),1);

return $extension;

}