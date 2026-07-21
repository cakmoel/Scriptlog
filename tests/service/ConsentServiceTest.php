<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class ConsentServiceTest extends TestCase
{
    private $consentDaoMock;

    private $consentService;

    protected function setUp(): void
    {
        $this->consentDaoMock = $this->createMock(\Scriptlog\Dao\ConsentDao::class);
        $this->consentService = new \Scriptlog\Service\ConsentService($this->consentDaoMock);
    }

    public function testRecordConsentReturnsValueFromDao(): void
    {
        $this->consentDaoMock->method('recordConsent')->willReturn(1);
        $result = $this->consentService->recordConsent('cookie', 'accepted', '127.0.0.1', 'test-agent');
        $this->assertEquals(1, $result);
    }

    public function testUpdateConsentDelegatesToDao(): void
    {
        $this->consentDaoMock->method('updateConsent')->willReturn(true);
        $result = $this->consentService->updateConsent(1, 'rejected');
        $this->assertTrue($result);
    }

    public function testGetLatestConsentReturnsArrayFromDao(): void
    {
        $this->consentDaoMock->method('getLatestConsent')->willReturn(['consent_status' => 'accepted']);
        $result = $this->consentService->getLatestConsent('cookie');
        $this->assertIsArray($result);
        $this->assertEquals('accepted', $result['consent_status']);
    }

    public function testGetAllConsentsReturnsArray(): void
    {
        $this->consentDaoMock->method('getAllConsents')->willReturn([]);
        $result = $this->consentService->getAllConsents();
        $this->assertIsArray($result);
    }

    public function testHasConsentedReturnsTrueWhenDaoReturnsTrue(): void
    {
        $this->consentDaoMock->method('hasConsented')->willReturn(true);
        $this->assertTrue($this->consentService->hasConsented('cookie'));
    }

    public function testProcessCookieConsentRecordsAndReturnsTrue(): void
    {
        $this->consentDaoMock->method('recordConsent')->willReturn(1);
        $result = $this->consentService->processCookieConsent('accepted');
        $this->assertTrue($result);
    }

    public function testIsCookieConsentAcceptedReturnsTrueWhenLatestIsAccepted(): void
    {
        $this->consentDaoMock->method('getLatestConsent')->willReturn(['consent_status' => 'accepted']);
        $this->assertTrue($this->consentService->isCookieConsentAccepted());
    }

    public function testCleanOldConsentsDelegatesToDao(): void
    {
        $this->consentDaoMock->method('deleteOldConsents')->willReturn(true);
        $result = $this->consentService->cleanOldConsents(30);
        $this->assertTrue($result);
    }
}
