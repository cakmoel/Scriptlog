<?php
/**
 * Function autolink
 * 
 * @category function
 * @see http://www.couchcode.com/php/auto-link-function/
 * @param string $text
 * @return string
 * 
 */
function autolink($text) {
    $pattern = '/(((http[s]?:\/\/(.+(:.+)?@)?)|(www\.))[a-z0-9](([-a-z0-9]+\.)*\.[a-z]{2,})?\/?[a-z0-9.,_\/~#&=:;%+!?-]+)/is';
    $replace = preg_replace($pattern, ' <a href="$1">$1</a>', $text);
    return preg_replace('/href="www/', 'href="https://www', $replace);
   
}