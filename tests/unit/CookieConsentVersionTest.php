<?php

use PHPUnit\Framework\TestCase;

/**
 * Cookie Consent Versioning Tests
 *
 * Covers the consent-version behaviour added to cookie-consent.php: consent
 * is only valid for the current COOKIE_CONSENT_VERSION, and a bumped policy
 * version forces re-consent.
 */
class CookieConsentVersionTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/utility/cookie-consent.php';
        unset($_COOKIE['cookie_consent'], $_COOKIE['cookie_consent_version']);
    }

    protected function tearDown(): void
    {
        unset($_COOKIE['cookie_consent'], $_COOKIE['cookie_consent_version']);
    }

    public function testGetCookieConsentVersionReturnsEmptyWhenAbsent(): void
    {
        $this->assertSame('', get_cookie_consent_version());
    }

    public function testGetCookieConsentVersionReturnsStoredValue(): void
    {
        $_COOKIE['cookie_consent_version'] = '1';
        $this->assertSame('1', get_cookie_consent_version());
    }

    public function testHasConsentFalseWhenCookieAbsent(): void
    {
        $this->assertFalse(has_cookie_consent());
    }

    public function testHasConsentFalseWhenRejected(): void
    {
        $_COOKIE['cookie_consent'] = 'rejected';
        $_COOKIE['cookie_consent_version'] = COOKIE_CONSENT_VERSION;
        $this->assertFalse(has_cookie_consent());
    }

    public function testHasConsentTrueWhenAcceptedWithMatchingVersion(): void
    {
        $_COOKIE['cookie_consent'] = 'accepted';
        $_COOKIE['cookie_consent_version'] = COOKIE_CONSENT_VERSION;
        $this->assertTrue(has_cookie_consent());
    }

    public function testHasConsentFalseWhenVersionStale(): void
    {
        $_COOKIE['cookie_consent'] = 'accepted';
        $_COOKIE['cookie_consent_version'] = '0';
        $this->assertFalse(has_cookie_consent());
    }

    public function testHasConsentFalseWhenVersionMissing(): void
    {
        $_COOKIE['cookie_consent'] = 'accepted';
        $this->assertFalse(has_cookie_consent());
    }

    public function testBannerShownWhenNoConsentCookie(): void
    {
        $this->assertTrue(should_show_consent_banner());
    }

    public function testBannerHiddenWhenConsentMatchesVersion(): void
    {
        $_COOKIE['cookie_consent'] = 'accepted';
        $_COOKIE['cookie_consent_version'] = COOKIE_CONSENT_VERSION;
        $this->assertFalse(should_show_consent_banner());
    }

    public function testBannerShownWhenConsentVersionStale(): void
    {
        $_COOKIE['cookie_consent'] = 'accepted';
        $_COOKIE['cookie_consent_version'] = '0';
        $this->assertTrue(should_show_consent_banner());
    }
}
