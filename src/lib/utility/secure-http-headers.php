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
 * - Inline JavaScript (via nonce/hash to be enforced later)
 * - AJAX requests to same origin
 * - Form submissions
 * - Images and media
 * - Safe external resources
 *
 * Hardenend: removed 'unsafe-eval' to block eval-based XSS vectors.
 * A Report-Only header is also sent to monitor violations before
 * fully removing 'unsafe-inline'.
 *
 * @param string $app_url
 */
function content_security_policy($app_url)
{
    $is_ssl = is_ssl();
    $scheme = $is_ssl ? 'https:' : 'http:';

    // 1. Enforced CSP - blocks eval-based attacks
    $csp = "Content-Security-Policy: " .
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' {$scheme}; " .
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

    // 2. Report-Only header for monitoring violations
    // This identifies what would break when we switch to nonce-based CSP.
    // Remove 'unsafe-inline' from report-only to see what inline scripts exist.
    $csp_report = "Content-Security-Policy-Report-Only: " .
        "default-src 'self'; " .
        "script-src 'self' {$scheme}; " .
        "style-src 'self' {$scheme}; " .
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
        $csp_report .= "; upgrade-insecure-requests";
    }

    header($csp_report);
}

/**
 * referrer_policy
 *
 * Controls how much referrer information is sent with requests.
 * strict-origin-when-cross-origin is the recommended default:
 * - Full URL for same-origin requests
 * - Origin only for cross-origin requests (over HTTPS)
 * - No referrer for downgrades (HTTPS → HTTP)
 */
function referrer_policy($policy = "strict-origin-when-cross-origin")
{
    header("Referrer-Policy: $policy");
}

/**
 * permissions_policy
 *
 * Restricts which browser features and APIs can be used.
 * Blocks features not needed by this application.
 */
function permissions_policy()
{
    $policy = "Permissions-Policy: " .
        "accelerometer=(), " .
        "ambient-light-sensor=(), " .
        "autoplay=(self), " .
        "battery=(), " .
        "camera=(), " .
        "cross-origin-isolated=(), " .
        "display-capture=(), " .
        "document-domain=(), " .
        "encrypted-media=(self), " .
        "fullscreen=(self), " .
        "gamepad=(), " .
        "geolocation=(), " .
        "gyroscope=(), " .
        "interest-cohort=(), " .
        "magnetometer=(), " .
        "microphone=(), " .
        "midi=(), " .
        "navigation-override=(), " .
        "payment=(), " .
        "picture-in-picture=(), " .
        "publickey-credentials-get=(), " .
        "screen-wake-lock=(), " .
        "sync-xhr=(self), " .
        "usb=(), " .
        "web-share=(self), " .
        "xr-spatial-tracking=()";

    header($policy);
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
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
        header('Access-Control-Allow-Origin: ' . (app_url() ?? '*'));
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
        http_response_code(204);
        exit();
    }
}
