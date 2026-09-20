<?php

use PHPUnit\Framework\TestCase;
use Scriptlog\Dao\ConsentDao;
use Scriptlog\Service\ConsentService;

/**
 * Unit tests for ConsentService.
 *
 * ConsentService is a thin orchestration layer over ConsentDao. Every public
 * method must delegate to the injected DAO (or derive from its return value),
 * so this suite uses a mocked ConsentDao and asserts delegation contracts.
 *
 * @coversDefaultClass Scriptlog\Service\ConsentService
 */
class ConsentServiceTest extends TestCase
{
    /**
     * Mocked ConsentDao instance.
     *
     * @var ConsentDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $consentDao;

    /**
     * Service under test.
     *
     * @var ConsentService
     */
    private $service;

    /**
     * Set up a fresh service with a mocked DAO for every test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', 'test');
        }

        $this->consentDao = $this->createMock(ConsentDao::class);
        $this->service = new ConsentService($this->consentDao);
    }

    /**
     * recordConsent() must forward its arguments to the DAO and return the
     * DAO result.
     *
     * @return void
     */
    public function testRecordConsentDelegatesToDao(): void
    {
        $this->consentDao->expects($this->once())
            ->method('recordConsent')
            ->with('cookie', 'accepted', '127.0.0.1', 'Unit Test UA')
            ->willReturn(42);

        $this->assertSame(
            42,
            $this->service->recordConsent('cookie', 'accepted', '127.0.0.1', 'Unit Test UA')
        );
    }

    /**
     * recordConsent() forwards a null user agent when the argument is omitted.
     *
     * @return void
     */
    public function testRecordConsentDefaultsUserAgentToNull(): void
    {
        $this->consentDao->expects($this->once())
            ->method('recordConsent')
            ->with('analytics', 'rejected', '10.0.0.1', null)
            ->willReturn(7);

        $this->assertSame(7, $this->service->recordConsent('analytics', 'rejected', '10.0.0.1'));
    }

    /**
     * updateConsent() must forward id and status to the DAO.
     *
     * @return void
     */
    public function testUpdateConsentDelegatesToDao(): void
    {
        $this->consentDao->expects($this->once())
            ->method('updateConsent')
            ->with(9, 'rejected')
            ->willReturn(true);

        $this->assertTrue($this->service->updateConsent(9, 'rejected'));
    }

    /**
     * getLatestConsent() must return exactly what the DAO returns.
     *
     * @return void
     */
    public function testGetLatestConsentDelegatesToDao(): void
    {
        $consent = ['ID' => 3, 'consent_type' => 'cookie', 'consent_status' => 'accepted'];

        $this->consentDao->expects($this->once())
            ->method('getLatestConsent')
            ->with('cookie')
            ->willReturn($consent);

        $this->assertSame($consent, $this->service->getLatestConsent('cookie'));
    }

    /**
     * getAllConsents() must return exactly what the DAO returns.
     *
     * @return void
     */
    public function testGetAllConsentsDelegatesToDao(): void
    {
        $consents = [
            ['ID' => 1, 'consent_type' => 'cookie', 'consent_status' => 'accepted'],
            ['ID' => 2, 'consent_type' => 'analytics', 'consent_status' => 'rejected'],
        ];

        $this->consentDao->expects($this->once())
            ->method('getAllConsents')
            ->willReturn($consents);

        $this->assertSame($consents, $this->service->getAllConsents());
    }

    /**
     * hasConsented() must return the DAO boolean verdict. Without an IP the
     * per-visitor check is skipped and the site-global record is consulted.
     *
     * @return void
     */
    public function testHasConsentedDelegatesToDao(): void
    {
        $this->consentDao->expects($this->once())
            ->method('hasConsented')
            ->with('cookie', null)
            ->willReturn(true);

        $this->assertTrue($this->service->hasConsented('cookie'));
    }

    /**
     * hasConsented() forwards the visitor IP to the DAO so consent is
     * evaluated per visitor rather than site-global.
     *
     * @return void
     */
    public function testHasConsentedForwardsIpAddress(): void
    {
        $this->consentDao->expects($this->once())
            ->method('hasConsented')
            ->with('cookie', '203.0.113.7')
            ->willReturn(false);

        $this->assertFalse($this->service->hasConsented('cookie', '203.0.113.7'));
    }

    /**
     * processCookieConsent() must record a consent of type "cookie" with the
     * client IP and user agent taken from the request environment.
     *
     * @return void
     */
    public function testProcessCookieConsentRecordsCookieConsent(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.0.10';
        $_SERVER['HTTP_USER_AGENT'] = 'Unit Test Browser';

        $this->consentDao->expects($this->once())
            ->method('recordConsent')
            ->with('cookie', 'accepted', '192.168.0.10', 'Unit Test Browser')
            ->willReturn(11);

        $this->assertTrue($this->service->processCookieConsent('accepted'));
    }

    /**
     * isCookieConsentAccepted() returns true when the latest cookie consent
     * record has status "accepted".
     *
     * @return void
     */
    public function testIsCookieConsentAcceptedWhenLatestIsAccepted(): void
    {
        $this->consentDao->expects($this->once())
            ->method('getLatestConsent')
            ->with('cookie')
            ->willReturn(['ID' => 1, 'consent_status' => 'accepted']);

        $this->assertTrue($this->service->isCookieConsentAccepted());
    }

    /**
     * isCookieConsentAccepted() returns false when the latest cookie consent
     * record has status "rejected".
     *
     * @return void
     */
    public function testIsCookieConsentAcceptedWhenLatestIsRejected(): void
    {
        $this->consentDao->expects($this->once())
            ->method('getLatestConsent')
            ->with('cookie')
            ->willReturn(['ID' => 1, 'consent_status' => 'rejected']);

        $this->assertFalse($this->service->isCookieConsentAccepted());
    }

    /**
     * isCookieConsentAccepted() returns false when no consent record exists.
     *
     * @return void
     */
    public function testIsCookieConsentAcceptedWhenNoConsentRecorded(): void
    {
        $this->consentDao->expects($this->once())
            ->method('getLatestConsent')
            ->with('cookie')
            ->willReturn(false);

        $this->assertFalse($this->service->isCookieConsentAccepted());
    }

    /**
     * cleanOldConsents() must forward the retention window to the DAO.
     *
     * @return void
     */
    public function testCleanOldConsentsDelegatesToDao(): void
    {
        $this->consentDao->expects($this->once())
            ->method('deleteOldConsents')
            ->with(180)
            ->willReturn(true);

        $this->assertTrue($this->service->cleanOldConsents(180));
    }
}
