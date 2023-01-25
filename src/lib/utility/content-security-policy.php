<?php
/**
 * content_security_policy()
 * 
 * set content_securiy_policy header
 * 
 * @category function
 * @author M.Noermoehammad
 * @see https://scotthelme.co.uk/content-security-policy-an-introduction/
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy
 * @license MIT
 * @version 1.0
 * @return void
 * 
 */
function content_security_policy($app_url)
{

if (is_ssl() === false) {

$csp_non_ssl = "Content-Security-Policy: ".
    "connect-src 'self' http:; ". 
    "default-src 'self' http:; ". 
    "font-src 'unsafe-inline' data: http:; ". 
    "form-action 'self' ".$app_url."; ". 
    "img-src data: http:; ".
    "frame-ancestors 'none' ; ". 
    "frame-src 'none'; ". 
    "child-src http:; ". 
    "media-src 'self' http:; ". 
    "object-src 'self' www.google-analytics.com ajax.googleapis.com platform-api.sharethis.com yourusername.disqus.com;". 
    "script-src 'self' 'unsafe-inline' http:; ". 
    "style-src 'self' 'unsafe-inline' http:;";
    
header($csp_non_ssl);
    
} else {

$csp_ssl = "Content-Security-Policy:".
    "frame-ancestors 'none'; ".
    "connect-src 'self' https:; ". 
    "default-src 'self' https:; ". 
    "font-src 'unsafe-inline' data: https:; ". 
    "form-action 'self' ".$app_url."; ". 
    "img-src data: https:; ".
    "frame-ancestors 'none'; ". 
    "frame-src 'none; ". 
    "child-src https:; ". 
    "media-src 'self' https:; ". 
    "object-src 'self' www.google-analytics.com ajax.googleapis.com platform-api.sharethis.com yourusername.disqus.com;". 
    "script-src 'self' 'unsafe-inline' https:; ". 
    "style-src 'self' 'unsafe-inline' https:;";
    
header($csp_ssl);
    
}

}