<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * x_frame_option
 */
function x_frame_option($options = "sameorigin")
{
    header("X-Frame-Options: $options");
}

/**
 * x_xss_protection
 */
function x_xss_protection()
{
    header("X-XSS-Protection: 1; mode=block");
}

/**
 * x_content_type_options
 */
function x_content_type_options($options = "nosniff")
{
    header("X-Content-Type-Options: $options");
}

/**
 * strict_transport_security
 */
function strict_transport_security()
{
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
}

/**
 * remove_x_powered_by
 */
function remove_x_powered_by()
{
    header_remove("X-Powered-By");
}

/**
 * content_security_policy
 * 
 * Configured to allow:
 * - Inline JavaScript and styles
 * - AJAX requests to same origin
 * - Form submissions
 * - Images and media
 * - Safe external resources
 * 
 * @param string $app_url
 */
function content_security_policy($app_url)
{
    $is_ssl = is_ssl();
    $scheme = $is_ssl ? 'https:' : 'http:';
    
    // Base CSP - allows inline scripts/styles, AJAX, forms, images
    $csp = "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval' {$scheme}; " .
        "style-src 'self' 'unsafe-inline' {$scheme}; " .
        "img-src 'self' data: {$scheme}; " .
        "font-src 'self' data: {$scheme}; " .
        "connect-src 'self' {$scheme}; " .
        "media-src 'self' {$scheme}; " .
        "frame-src 'self' {$scheme}; " .
        "child-src 'self' {$scheme}; " .
        "object-src 'self'; " .
        "frame-ancestors 'self'; " .
        "base-uri 'self'; " .
        "form-action 'self' {$app_url}; " .
        "manifest-src 'self';";
    
    if (!$is_ssl) {
        $csp .= "; upgrade-insecure-requests";
    }
    
    header($csp);
}

/**
 * Allow AJAX from admin area
 * Use this in admin AJAX endpoints before any output
 */
function allow_admin_ajax()
{
    header('Access-Control-Allow-Origin: ' . (app_url() . '/admin/'));
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Cache-Control: no-cache, no-store, must-revalidate');
}

/**
 * Set CORS headers for API endpoints
 */
function set_cors_headers($origin = null)
{
    if ($origin === null) {
        $origin = app_url();
    }
    
    header("Access-Control-Allow-Origin: {$origin}");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400");
}

/**
 * Handle preflight OPTIONS request
 */
function handle_preflight_request()
{
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: ' . (app_url() ?? '*'));
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        http_response_code(204);
        exit();
    }
}