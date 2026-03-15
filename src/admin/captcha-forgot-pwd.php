<?php
/**
 * captcha-forgot-pwd.php
 * 
 * @category generate captcha code on reset password form
 * @author M.Noermoehammad <scriptlog@yandex.com>
 * @license MIT
 * @version 1.0
 * 
 */
require __DIR__ . '/../lib/main.php';

$random_alpha = class_exists('Util') ? Util::secure_random_string(16) : "";

$captcha_code = substr(str_shuffle($random_alpha), 0, 6);
    
class_exists('Session') ? Session::getInstance()->forgot_pwd = $captcha_code : "";

try {

    if (get_extension_funcs('gd') && function_exists('imagecreatetruecolor')) { 
        
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
    
} catch (\Throwable $th) {
    //throw $th;
}
