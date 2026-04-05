<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Cookie Consent Utility Functions
 *
 * Helper functions for cookie consent management
 *
 * @category  Utility
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

if (!class_exists('ConsentDao')) {
    require_once __DIR__ . '/../dao/ConsentDao.php';
}

if (!class_exists('ConsentService')) {
    require_once __DIR__ . '/../service/ConsentService.php';
}

/**
 * Check if user has given cookie consent
 *
 * @return bool
 */
function has_cookie_consent()
{
    return isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === 'accepted';
}

/**
 * Get cookie consent status from database
 *
 * @return bool
 */
function get_cookie_consent_from_db()
{
    static $consentService = null;

    if (null === $consentService) {
        $consentDao = new ConsentDao();
        $consentService = new ConsentService($consentDao);
    }

    return $consentService->getCookieConsentStatus();
}

/**
 * Record cookie consent
 *
 * @param string $status 'accepted' or 'rejected'
 * @return bool
 */
function record_cookie_consent($status)
{
    static $consentService = null;

    if (null === $consentService) {
        $consentDao = new ConsentDao();
        $consentService = new ConsentService($consentDao);
    }

    return $consentService->processCookieConsent($status);
}

/**
 * Set cookie consent cookie
 *
 * @param string $status 'accepted' or 'rejected'
 * @param int $expiryDays Number of days to remember
 * @return bool
 */
function set_cookie_consent_cookie($status, $expiryDays = 365)
{
    $expiry = time() + (86400 * $expiryDays); // 86400 = 1 day

    return setcookie('cookie_consent', $status, [
        'expires' => $expiry,
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => is_cookies_secured(),
        'httponly' => true
    ]);
}

/**
 * Get privacy policy URL
 *
 * @return string
 */
function get_privacy_policy_url()
{
    if (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes') {
        return app_url() . '/privacy';
    }
    return app_url() . '?privacy';
}

/**
 * Check if consent banner should be shown
 *
 * @return bool
 */
function should_show_consent_banner()
{
    return !isset($_COOKIE['cookie_consent']);
}

/**
 * Process consent via AJAX
 *
 * @return void
 */
function process_consent_ajax()
{
    header('Content-Type: application/json');

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!isset($data['status']) || !in_array($data['status'], ['accepted', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid consent status']);
        exit;
    }

    $status = $data['status'];

    // Record in database
    record_cookie_consent($status);

    // Set cookie
    set_cookie_consent_cookie($status);

    echo json_encode(['success' => true, 'message' => 'Consent recorded']);
    exit;
}
