<?php
/**
 * defender
 * 
 * send defender to stopping unpleasant attempt of vulnerable scanner
 * on production stage.
 *
 * @category function
 * @see https://blog.haschek.at/2017/how-to-defend-your-website-with-zip-bombs.html
 * @see https://www.sitepoint.com/how-to-defend-your-website-with-zip-bombs/
 * @see https://news.ycombinator.com/item?id=14707674
 * @return int|false
 * 
 */
function defender()
{

if (file_exists(basename(__DIR__ . DS . '.guard'. DS . 'fortres.gzip'))) {

    header("Content-Encoding: gzip");
    header("Content-Length:".filesize(basename(__DIR__ . DS . '.guard' . DS . 'fortres.gzip')));

    if (ob_get_level()) {

        ob_end_clean();

        readfile(basename(__DIR__ . DS . '.guard' . DS . 'fortres.gzip'));
        
    }

}

}

/**
 * midfielder
 * 
 * @category function
 * 
 * @return bool
 * 
 */
function midfielder()
{

$is_scanner = true;

$agent = filter_input(INPUT_SERVER, 'HTTP_USER_AGENT');
$url = current_load_url();

if (strpos($agent, 'sqlmap') !== false || strpos($agent, 'nikto') !== false 
    || starts_with($url, 'wp-') || starts_with($url, 'wordpress') || starts_with($url, 'wp/'))  {

    $is_scanner = true;

} else {

    $is_scanner = false;
}

return $is_scanner;

}

/**
 * starts_with
 * 
 * Checking if a text starts with an given string.
 * 
 * @category function
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function starts_with($haystack, $needle)
{
  return strpos($haystack, $needle) === 0;
}

/**
 * ends_with
 *
 * @param [type] $haystack
 * @param [type] $needle
 * @return void
 */
function ends_with($haystack, $needle)
{
    return strrpos($haystack, $needle) === strlen($haystack)-strlen($needle);
}