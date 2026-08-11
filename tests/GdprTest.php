<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

class GdprTest extends TestCase
{
    private $dataRequestDaoMock;

    private $privacyLogDaoMock;

    private $sanitizerMock;

    private $configServiceMock;

    private $userServiceMock;

    private $userDaoMock;

    private $commentDaoMock;

    private $postDaoMock;

    private $dataRequestService;

    private $consentDaoMock;

    private $consentService;

    protected function setUp(): void
    {
        $this->dataRequestDaoMock = $this->createMock(\Scriptlog\Dao\DataRequestDao::class);
        $this->privacyLogDaoMock = $this->createMock(\Scriptlog\Dao\PrivacyLogDao::class);
        $this->sanitizerMock = $this->createMock(\Scriptlog\Core\Sanitize::class);
        $this->configServiceMock = $this->createMock(\Scriptlog\Service\ConfigurationService::class);
        $this->userServiceMock = $this->createMock(\Scriptlog\Service\UserService::class);
        $this->userDaoMock = $this->createMock(\Scriptlog\Dao\UserDao::class);
        $this->commentDaoMock = $this->createMock(\Scriptlog\Dao\CommentDao::class);
        $this->postDaoMock = $this->createMock(\Scriptlog\Dao\PostDao::class);

        $this->dataRequestService = new \Scriptlog\Service\DataRequestService(
            $this->dataRequestDaoMock,
            $this->privacyLogDaoMock,
            $this->sanitizerMock,
            $this->configServiceMock,
            $this->userServiceMock,
            $this->userDaoMock,
            $this->commentDaoMock,
            $this->postDaoMock
        );

        $this->consentDaoMock = $this->createMock(\Scriptlog\Dao\ConsentDao::class);
        $this->consentService = new \Scriptlog\Service\ConsentService($this->consentDaoMock);
    }

    public function testGetAllRequestsDelegatesToDao(): void
    {
        $this->dataRequestDaoMock->method('getAllRequests')->willReturn([]);

        $this->assertSame([], $this->dataRequestService->getAllRequests());
    }

    public function testGetPendingCountDelegatesToDao(): void
    {
        $this->dataRequestDaoMock->method('getPendingCount')->willReturn(3);

        $this->assertSame(3, $this->dataRequestService->getPendingCount());
    }

    public function testGetTotalRequestsDelegatesToDao(): void
    {
        $this->dataRequestDaoMock->method('totalRequestRecords')->willReturn(10);

        $this->assertSame(10, $this->dataRequestService->getTotalRequests());
    }

    public function testCreateRequestRejectsInvalidEmail(): void
    {
        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->createRequest('access', 'not-an-email');
    }

    public function testUpdateRequestStatusThrowsWhenRequestNotFound(): void
    {
        $this->dataRequestDaoMock->method('getRequestById')->willReturn(false);

        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->updateRequestStatus(999, 'processing');
    }

    public function testUpdateRequestStatusUpdatesRequestAndLogsActivity(): void
    {
        $this->dataRequestDaoMock->method('getRequestById')->willReturn([
            'ID' => 7,
            'request_type' => 'access',
            'request_email' => 'user@example.com',
        ]);

        $this->dataRequestDaoMock->expects($this->once())
            ->method('updateRequestStatus')
            ->with(7, 'processing', null)
            ->willReturn(true);

        $this->privacyLogDaoMock->expects($this->once())
            ->method('createLog')
            ->with('request_status_updated', 'access');

        $this->assertTrue($this->dataRequestService->updateRequestStatus(7, 'processing'));
    }

    public function testExportUserDataRejectsInvalidEmail(): void
    {
        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->exportUserData('bad-email');
    }

    public function testExportUserDataBuildsPortableArchive(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn([
            'ID' => 11,
            'user_login' => 'jdoe',
            'user_email' => 'user@example.com',
            'user_fullname' => 'Jane Doe',
            'user_url' => '',
            'user_registered' => '2024-01-01 00:00:00',
        ]);

        $this->commentDaoMock->method('findComments')->willReturn([['ID' => 1]]);
        $this->postDaoMock->method('findPosts')->willReturn([['ID' => 2]]);
        $this->privacyLogDaoMock->method('getLogsByEmail')->willReturn([['ID' => 3]]);

        $archive = $this->dataRequestService->exportUserData('user@example.com', [
            'export_comments' => true,
            'export_posts' => true,
            'export_activity' => true,
        ]);

        $this->assertIsArray($archive);
        $this->assertSame('user@example.com', $archive['email']);
        $this->assertSame('jdoe', $archive['profile']['user_login']);
        $this->assertSame(11, $archive['user_id']);
        $this->assertCount(1, $archive['comments']);
        $this->assertCount(1, $archive['posts']);
        $this->assertCount(1, $archive['activity']);
    }

    public function testDeleteUserDataRejectsInvalidEmail(): void
    {
        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->deleteUserData('bad-email');
    }

    public function testDeleteUserDataThrowsWhenUserNotFound(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn(false);

        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->deleteUserData('user@example.com');
    }

    public function testDeleteUserDataAnonymizesAccountAndLogsDeletion(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn([
            'ID' => 11,
            'user_email' => 'user@example.com',
        ]);

        $this->userServiceMock->expects($this->once())
            ->method('removeUserWithAnonymization')
            ->with(11, 'user@example.com')
            ->willReturn(true);

        $this->privacyLogDaoMock->expects($this->once())
            ->method('createLog')
            ->with('data_deleted', 'deletion');

        $this->assertTrue($this->dataRequestService->deleteUserData('user@example.com'));
    }

    public function testDeleteUserDataThrowsWhenAnonymizationFails(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn([
            'ID' => 11,
            'user_email' => 'user@example.com',
        ]);

        $this->userServiceMock->method('removeUserWithAnonymization')->willReturn(false);

        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->deleteUserData('user@example.com');
    }

    public function testRecordConsentDelegatesToDao(): void
    {
        $this->consentDaoMock->method('recordConsent')->willReturn(4);

        $this->assertSame(4, $this->consentService->recordConsent('analytics', 'accepted', '127.0.0.1'));
    }

    public function testProcessCookieConsentDelegatesToDao(): void
    {
        $this->consentDaoMock->expects($this->once())
            ->method('recordConsent')
            ->with('cookie', 'accepted', '127.0.0.1')
            ->willReturn(1);

        $this->assertTrue($this->consentService->processCookieConsent('accepted'));
    }

    public function testIsCookieConsentAcceptedWhenLatestConsentAccepted(): void
    {
        $this->consentDaoMock->method('getLatestConsent')->willReturn(['consent_status' => 'accepted']);

        $this->assertTrue($this->consentService->isCookieConsentAccepted());
    }

    public function testIsCookieConsentRejectedWhenLatestConsentRejected(): void
    {
        $this->consentDaoMock->method('getLatestConsent')->willReturn(['consent_status' => 'rejected']);

        $this->assertFalse($this->consentService->isCookieConsentAccepted());
    }

    public function testHasConsentedReflectsDaoResult(): void
    {
        $this->consentDaoMock->method('hasConsented')->willReturn(false);

        $this->assertFalse($this->consentService->hasConsented('analytics', '127.0.0.1'));
    }

    public function testCleanOldConsentsDelegatesToDao(): void
    {
        $this->consentDaoMock->method('deleteOldConsents')->willReturn(true);

        $this->assertTrue($this->consentService->cleanOldConsents(30));
    }

    /**
     * @dataProvider gdprDaoContractsProvider
     */
    public function testGdprDaoPublicContract(string $class, array $methods): void
    {
        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                sprintf('%s must expose a %s() method', $class, $method)
            );
        }
    }

    public function gdprDaoContractsProvider(): array
    {
        return [
            'DataRequestDao' => [
                'Scriptlog\Dao\DataRequestDao',
                [
                    'createRequest',
                    'updateRequestStatus',
                    'getRequestById',
                    'getRequestByEmail',
                    'getAllRequests',
                    'getRequestsByStatus',
                    'deleteRequest',
                    'totalRequestRecords',
                    'getPendingCount',
                ],
            ],
            'PrivacyLogDao' => [
                'Scriptlog\Dao\PrivacyLogDao',
                [
                    'createLog',
                    'getLogById',
                    'getLogsByUserId',
                    'getLogsByEmail',
                    'getLogsByAction',
                    'getAllLogs',
                    'getRecentLogs',
                    'deleteOldLogs',
                    'totalLogRecords',
                ],
            ],
            'ConsentDao' => [
                'Scriptlog\Dao\ConsentDao',
                [
                    'recordConsent',
                    'updateConsent',
                    'getLatestConsent',
                    'getAllConsents',
                    'getConsentsByIp',
                    'hasConsented',
                    'deleteOldConsents',
                    'totalConsentRecords',
                ],
            ],
        ];
    }

    /**
     * @dataProvider gdprServiceContractsProvider
     */
    public function testGdprServicePublicContract(string $class, array $methods): void
    {
        foreach ($methods as $method) {
            $this->assertTrue(
                method_exists($class, $method),
                sprintf('%s must expose a %s() method', $class, $method)
            );
        }
    }

    public function gdprServiceContractsProvider(): array
    {
        return [
            'DataRequestService' => [
                'Scriptlog\Service\DataRequestService',
                [
                    'createRequest',
                    'getAllRequests',
                    'getPendingCount',
                    'getTotalRequests',
                    'updateRequestStatus',
                    'exportUserData',
                    'deleteUserData',
                ],
            ],
            'ConsentService' => [
                'Scriptlog\Service\ConsentService',
                [
                    'recordConsent',
                    'updateConsent',
                    'getLatestConsent',
                    'getAllConsents',
                    'hasConsented',
                    'processCookieConsent',
                    'isCookieConsentAccepted',
                ],
            ],
        ];
    }
}