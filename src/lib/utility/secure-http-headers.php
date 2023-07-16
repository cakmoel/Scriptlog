<?php
/**
 * x_frame_option
 * 
 * http header x-frame-options
 * This http header helps avoiding clickjacking attacks. 
 * Browser support is as follow: IE 8+, Chrome 4.1+, Firefox 3.6.9+, Opera 10.5+, Safari 4+. P
 * The Content-Security-Policy HTTP header has a frame-ancestors directive 
 * which obsoletes this header for supporting browsers.Posible values are:
 * 
 * deny -- browser refuses to display requested document in a frame 
 * sameorigin -- browser refuses to display requested document in a frame, in case that origin does not match 
 * allow-from: DOMAIN -- browser displays requested document in a frame only if it loaded from DOMAIN
 * 
 * @param string $options
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options
 * @return void
 * 
 */
function x_frame_option($options = "sameorigin")
{
  header("X-Frame-Options: $options");
}

/**
 * x_xss_protection
 * 
 * http_header x-xss-protection
 * Use this header to enable browser built-in XSS Filter. 
 * It prevent cross-site scripting attacks. X-XSS-Protection header is supported by IE 8+, Opera, Chrome, and Safari.
 * Available directives:
 * 
 * 0 -- disable XSS Filter
 * 1 -- enables the XSS Filter. If a cross-site scripting attack is detected, in order to stop the attack, the browser will sanitize the page.
 * 1; mode=block -- enables the XSS Filter. Rather than sanitize the page, when a XSS attack is detected, the browser will prevent rendering of the page.
 * 1; report=<reporting-URI> -- enables the XSS Filter. If a cross-site scripting attack is detected, the browser will sanitize the page and report the violation.
 * 
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection
 * @return void
 * 
 */
function x_xss_protection()
{
 header("X-XSS-Protection: 1; mode=block");
}

/**
 * x_content_type_protection
 *
 * @return void
 * 
 */
function x_content_type_options()
{
  header("X-Content-Type-Options: nosniff");
}

/**
 * strict_transport_security
 * 
 * @return void
 * 
 */
function strict_transport_security()
{
  header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

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
