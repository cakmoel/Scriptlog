<?php

 require __DIR__ . '/../lib/main.php';

 $random_alpha = md5(rand());
 $captcha_code = substr($random_alpha, 0, 6);
 
 $_SESSION["captcha_code"] = $captcha_code;
 
 $layer = imagecreatetruecolor(70,30);
 $background = imagecolorallocate($layer, 153, 204, 0);
 imagefill($layer, 0, 0, $background);
 $text_color =  imagecolorallocate($layer, 0, 0, 0);
 imagestring($layer, 5, 5, 5, $captcha_code, $text_color);

 header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
 header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); 
 header("Cache-Control: no-store, no-cache, must-revalidate"); 
 header("Cache-Control: post-check=0, pre-check=0", false); 
 header("Pragma: no-cache"); 	
 header("Content-Type: image/jpeg");
 
 imagejpeg($layer);
 

 
 
 