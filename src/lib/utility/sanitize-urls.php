<?php
/**
 * Function sanitize_urls
 * 
 * @category function
 * @see https://github.com/vito/chyrp/blob/35c646dda657300b345a233ab10eaca7ccd4ec10/includes/helpers.php#L515
 * @return string
 * 
 */
function sanitize_urls($string, $force_lowercase = true, $anal = false)
{
    $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]",
        "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;",
        "—", "–", ",", "<", ".", ">", "/", "?");

    if (!is_array($string)) {

      $clean = trim(str_replace($strip, "", htmlspecialchars($string,  ENT_QUOTES|ENT_HTML5, 'UTF-8', false)));

    } else {

      $clean = implode($string);
      $clean = trim(str_replace($strip, "", htmlspecialchars($clean,  ENT_QUOTES|ENT_HTML5, 'UTF-8', false)));

    }
    
    $clean = preg_replace('/\s+/', "-", $clean);

    $clean = ($anal ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean);

    return ($force_lowercase) ? (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean) : $clean;

}