<?php
/**
 * get_file_extension()
 *
 * read file extension witn SplFileInfo and match it 
 * 
 * @category Function
 * @author M.Noermoehammad
 * @param string $file_name
 * @return bool|string -- false if extension does not match 
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

/**
 * file_extension_explode()
 * 
 * read file extension with explode function
 *
 * @param string $file_name
 * @return array
 * 
 */
function file_extension_explode($file_name)
{

$extension = [];

$extension = explode('.', $file_name);

return $extension[1];

}

/**
 * file_extension_end()
 *
 * read file extension with end--explode function
 * 
 * @param string $file_name
 * @return string
 * 
 */
function file_extension_end($file_name)
{

$extension = end(explode(".", $file_name));

return $extension;

}

/**
 * file_extension_pathinfo()
 * 
 * read file extension with pathinfo
 *
 * @param string $file_name
 * @return string
 * 
 */
function file_extension_pathinfo($file_name)
{

$extension = pathinfo($file_name, PATHINFO_EXTENSION);

return $extension;

}

/**
 * file_extension_str()
 * 
 * read file extensin with substr--strrchr
 *
 * @param string $file_name
 * @return string
 * 
 */
function file_extension_str($file_name)
{

$extension = substr(strrchr($file_name,'.'),1);

return $extension;

}