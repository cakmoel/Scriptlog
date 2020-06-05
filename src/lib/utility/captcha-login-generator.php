<?php
/**
 * Captcha login generator
 * Generating captcha for login when 3 times attempts failed
 * 
 * @category function
 * 
 */
function captcha_login_generator()
{

 $random_alpha = Util::secure_random_string(16);
 $captcha_code = substr(str_shuffle($random_alpha), 0, 6);
    
 $_SESSION['scriptlog_captcha_code'] = $captcha_code;
    
 $layer = imagecreatetruecolor(70,30);
 $background = imagecolorallocate($layer, 153, 204, 0);
 imagefill($layer, 0, 0, $background);
 $text_color =  imagecolorallocate($layer, 0, 0, 0);
 imagestring($layer, 5, 5, 5, $captcha_code, $text_color);
   
 /* Image header */
 header("Content-Type: image/jpeg");
 /* Invalidate cache. */
 header("Expires: Mon, 01 Jul 1988 05:00:00 GMT");
 header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
 header("Cache-Control: no-store, no-cache, must-revalidate");
 header("Cache-Control: post-check=0, pre-check=0", false);
 header("Pragma: no-cache");
    
 imagejpeg($layer);
 
 imagedestroy($layer);


}