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
 * Check if user has given cookie consent for the current consent version
 *
 * Consent is only considered valid when both the consent cookie is set to
 * "accepted" and the consent version cookie matches COOKIE_CONSENT_VERSION.
 *
 * @return bool
 */
function has_cookie_consent()
{
    return isset($_COOKIE['cookie_consent'])
        && $_COOKIE['cookie_consent'] === 'accepted'
        && get_cookie_consent_version() === COOKIE_CONSENT_VERSION;
}

/**
 * Get the stored cookie consent version
 *
 * @return string Consent version stored in the cookie, or '' when absent
 */
function get_cookie_consent_version()
{
    return isset($_COOKIE['cookie_consent_version']) ? $_COOKIE['cookie_consent_version'] : '';
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
 * Sets the consent status cookie and a companion version cookie so that a
 * policy change (bumped COOKIE_CONSENT_VERSION) forces re-consent. The
 * default lifetime is controlled by COOKIE_CONSENT_LIFETIME_DAYS.
 *
 * @param string $status 'accepted' or 'rejected'
 * @param int $expiryDays Number of days to remember
 * @return bool True when both cookies were set successfully
 */
function set_cookie_consent_cookie($status, $expiryDays = COOKIE_CONSENT_LIFETIME_DAYS)
{
    $expiry = time() + (86400 * $expiryDays); // 86400 = 1 day

    $options = [
        'expires' => $expiry,
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => is_cookies_secured(),
        'httponly' => true
    ];

    $consentSet = setcookie('cookie_consent', $status, $options);
    $versionSet = setcookie('cookie_consent_version', COOKIE_CONSENT_VERSION, $options);

    return $consentSet && $versionSet;
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
 * The banner is shown when no consent cookie exists yet, or when the stored
 * consent version does not match COOKIE_CONSENT_VERSION (policy change).
 *
 * @return bool
 */
function should_show_consent_banner()
{
    if (!isset($_COOKIE['cookie_consent'])) {
        return true;
    }

    return get_cookie_consent_version() !== COOKIE_CONSENT_VERSION;
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
