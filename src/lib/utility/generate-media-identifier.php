<?php
/**
 * generate media identifier function
 *
 * @param integer $length
 * @return string
 * 
 */
function generate_media_identifier($length = 60)
{

  return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$', ceil($length/strlen($x)) )),1,$length);

}