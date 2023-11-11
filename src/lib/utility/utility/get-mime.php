<?php
/**
 * Function get mime
 * retrieve media type info from file
 * 
 * @category Function
 * @param string $filename
 * @param number|int $mode
 * @return string
 * 
 */
function get_mime($filename, $mode = 0)
{
    $mime_types = array(

        // images
        'png'  => 'image/png',
        'jpe'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'webp' => 'image/webp',
      
        // archives
        'zip' => 'application/zip',
        
        // audio/video
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'ogg' => 'audio/ogg',
        'mp4' => 'video/mp4',
        'ogg' => 'video/ogg',
        'webm' => 'video/webm',

        // adobe
        'pdf' => 'application/pdf',
     
        // ms office
        'doc' => 'application/msword',
        'docx' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.ms-powerpoint',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        
    );

    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
    if (function_exists('mime_content_type') && $mode==0) {
        $mimetype = mime_content_type($filename);
        return $mimetype;
    }

    if (function_exists('finfo_open') && $mode==0) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimetype;

    } elseif (array_key_exists($ext, $mime_types)) {

        return $mime_types[$ext];

    } else {
        
        return 'application/octet-stream';

    }
    
}