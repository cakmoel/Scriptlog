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
function content_security_policy()
{

if (is_ssl() === false) {

$csp_non_ssl = "Content-Security-Policy: ".
    "connect-src 'self' http:; ". // XMLHttpRequest (AJAX request), WebSocket or EventSource.
    "default-src 'self' http:; ". // Default policy for loading html elements
    "font-src 'unsafe-inline' data: http:; ". // this defines valid sources for fonts to be loaded
    "form-action 'self' ".app_url()."; ". // you can update this with your own domain
    "img-src data: http:; ".
    "frame-ancestors 'self' ; ". //allow parent framing - this one blocks click jacking and ui redress
    "frame-src 'none'; ". // valid sources for frames
    "child-src http:; ". // This defines the valid sources for web workers and nested browsing contexts like iframes.
    "media-src 'self' http:; ". // vaid sources for media (audio and video html tags src) - update with your own domain
    "object-src 'self' www.google-analytics.com ajax.googleapis.com platform-api.sharethis.com yourusername.disqus.com;". // valid object embed and applet tags src
    "script-src 'self' 'unsafe-inline' http:; ". // allows js from self, jquery and google analytics.  Inline allows inline js
    "style-src 'self' 'unsafe-inline' http:;";// allows css from self and inline allows inline css
    //Sends the Header in the HTTP response to instruct the Browser how it should handle content and what is whitelisted
    //Its up to the browser to follow the policy which each browser has varying support

header($csp_non_ssl);
    
} else {

$csp_ssl = "Content-Security-Policy:".
    "connect-src 'self' https:; ". // XMLHttpRequest (AJAX request), WebSocket or EventSource.
    "default-src 'self' https:; ". // Default policy for loading html elements
    "font-src 'unsafe-inline' data: https:; ". // this defines valid sources for fonts to be loaded
    "form-action 'self' ".app_url()."; ". // you can update this with your own domain
    "img-src data: https:; ".
    "frame-ancestors 'self'; ". //allow parent framing - this one blocks click jacking and ui redress
    "frame-src 'none; ". // valid sources for frames
    "child-src https:; ". // This defines the valid sources for web workers and nested browsing contexts like iframes.
    "media-src 'self' https:; ". // vaid sources for media (audio and video html tags src) - update with your own domain
    "object-src 'self' www.google-analytics.com ajax.googleapis.com platform-api.sharethis.com yourusername.disqus.com;". // valid object embed and applet tags src
    "script-src 'self' 'unsafe-inline' https:; ". // allows js from self, jquery and google analytics.  Inline allows inline js
    "style-src 'self' 'unsafe-inline' https:;";// allows css from self and inline allows inline css
    //Sends the Header in the HTTP response to instruct the Browser how it should handle content and what is whitelisted
    //Its up to the browser to follow the policy which each browser has varying support

header($csp_ssl);
    
}

}