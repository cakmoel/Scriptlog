<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class DataRequestServiceTest extends TestCase
{
    private $dataRequestDaoMock;

    private $privacyLogDaoMock;

    private $sanitizeMock;

    private $configServiceMock;

    private $dataRequestService;

    protected function setUp(): void
    {
        $this->dataRequestDaoMock = $this->createMock(\Scriptlog\Dao\DataRequestDao::class);
        $this->privacyLogDaoMock = $this->createMock(\Scriptlog\Dao\PrivacyLogDao::class);
        $this->sanitizeMock = $this->createMock(\Scriptlog\Core\Sanitize::class);
        $this->configServiceMock = $this->createMock(\Scriptlog\Service\ConfigurationService::class);

        $this->dataRequestService = new \Scriptlog\Service\DataRequestService(
            $this->dataRequestDaoMock,
            $this->privacyLogDaoMock,
            $this->sanitizeMock,
            $this->configServiceMock
        );
    }

    public function testGetAllRequestsReturnsArray(): void
    {
        $this->dataRequestDaoMock->method('getAllRequests')->willReturn([]);
        $result = $this->dataRequestService->getAllRequests();
        $this->assertIsArray($result);
    }

    public function testGetPendingCountReturnsInt(): void
    {
        $this->dataRequestDaoMock->method('getPendingCount')->willReturn(3);
        $result = $this->dataRequestService->getPendingCount();
        $this->assertEquals(3, $result);
    }

    public function testGetTotalRequestsReturnsInt(): void
    {
        $this->dataRequestDaoMock->method('totalRequestRecords')->willReturn(10);
        $result = $this->dataRequestService->getTotalRequests();
        $this->assertEquals(10, $result);
    }

    public function testCreateRequestThrowsExceptionOnInvalidEmail(): void
    {
        $this->expectException(\Scriptlog\Core\AppException::class);
        $this->dataRequestService->createRequest('access', 'not-an-email');
    }

    public function testUpdateRequestStatusThrowsExceptionOnNotFound(): void
    {
        $this->dataRequestDaoMock->method('getRequestById')->willReturn(false);
        $this->expectException(\Scriptlog\Core\AppException::class);
        $this->dataRequestService->updateRequestStatus(999, 'completed');
    }

    public function testExportUserDataThrowsExceptionOnInvalidEmail(): void
    {
        $this->expectException(\Scriptlog\Core\AppException::class);
        $this->dataRequestService->exportUserData('bad-email');
    }

    public function testDeleteUserDataThrowsExceptionOnInvalidEmail(): void
    {
        $this->expectException(\Scriptlog\Core\AppException::class);
        $this->dataRequestService->deleteUserData('bad-email');
    }
}
