<?php
/**
 * Image Encoder Function
 * return local images as base64 encrypted code, i.e embedding the image
 * source into the html request.
 * Example:
 * <img src="<?php echo base64_encode_image ('img/logo.png','png'); ?>"/>
 * OR
 *  <style type="text/css">
 *    .logo {
 *    background: url("<?php echo base64_encode_image ('img/logo.png','png'); ?>") no-repeat right 5px;
 *    }
 *  </style>
 * 
 * @category Function
 * @see  https://www.php.net/manual/en/function.base64-encode.php#105200
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