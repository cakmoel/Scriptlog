<?php
/**
 * Image Encoder Function
 * return local images as base64 encrypted code, i.e embedding the image
 * source into the html request
 * 
 * @category Function
 * @link https://www.php.net/manual/en/function.base64-encode.php#105200
 * @param string $filename
 * @param string $filetype
 * @return string
 * 
 */
function image_encoder($filename, $filetype)
{
    if ($filename) {
        
        $imgbinary = fread(fopen($filename, "r"), filesize($filename));
        return 'data:image/' . $filetype . ';base64,' . base64_encode($imgbinary);
        
    }
}