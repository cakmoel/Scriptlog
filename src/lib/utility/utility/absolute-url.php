<?php
/**
 * absolute_url
 * takes the URL of web page, along with a link from within that page, 
 * and then return an absolute URL.
 *
 * @category function
 * @author M.Noermoehammad
 * @param string $page A web page URL, including http:// preface and domain name
 * @param string $url A link extracted from $page 
 * @license MIT
 * @version 1.0
 * @return string
 * 
 */
function absolute_url($page, $url)
{
   checking_page_url($page, $url);

    $parse = parse_url($page);
    $root = $parse['scheme'] . '://' . $parse['host'];
    $p = strrpos(substr($parse, 7), '/');

    if ($p) {

        $base = substr($page, 0, $p + 8);

    } else {

        $base = "$page/";

    }

    if (substr($url, 0, 1) === '/') {

        $url = $root . $url;

    } elseif (substr($url, 0, 7) !== APP_PROTOCOL . '://') {

        $url = $base . $url;

    }

    return sanitize_urls($url);
    
}

/**
 * checking_page_url
 *
 * @param string $page
 * @param string $url
 */
function checking_page_url($page, $url)
{

 if (substr($page, 0, 7) !== APP_PROTOCOL . '://') {

    return $url;

 }

}