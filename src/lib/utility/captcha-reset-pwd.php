<?php
/**
 * captcha_reset_pwd()
 * 
 * Generating captcha for reset password
 *
 * @param number $length
 * @return void
 * 
 */
function captcha_reset_pwd()
{
 
$random_alpha =  ircmaxell_generator_string('medium');
$captcha_code = substr($random_alpha, 0, 6);
    
$_SESSION['scriptlog_reset_pwd'] = $captcha_code;
    
 $layer = imagecreatetruecolor(70,30);
 $background = imagecolorallocate($layer, 127, 255, 0);
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