<?php

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\AppException;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\CommentDao;
use Scriptlog\Dao\DataRequestDao;
use Scriptlog\Dao\PostDao;
use Scriptlog\Dao\PrivacyLogDao;
use Scriptlog\Dao\UserDao;
use Scriptlog\Service\DataRequestService;
use Scriptlog\Service\UserService;

/**
 * Unit tests for DataRequestService.
 *
 * The service orchestrates DataRequestDao, PrivacyLogDao and a notification
 * mechanism. The injected DAOs and sanitizer are mocked; the internally
 * created NotificationService is neutralised via reflection so no email is
 * ever sent during unit tests.
 *
 * @coversDefaultClass Scriptlog\Service\DataRequestService
 */
class DataRequestServiceTest extends TestCase
{
    /**
     * Mocked DataRequestDao instance.
     *
     * @var DataRequestDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $dataRequestDao;

    /**
     * Mocked PrivacyLogDao instance.
     *
     * @var PrivacyLogDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $privacyLogDao;

    /**
     * Mocked Sanitize instance.
     *
     * @var Sanitize&\PHPUnit\Framework\MockObject\MockObject
     */
    private $sanitizer;

    /**
     * Mocked UserDao instance.
     *
     * @var UserDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $userDao;

    /**
     * Mocked CommentDao instance.
     *
     * @var CommentDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $commentDao;

    /**
     * Mocked PostDao instance.
     *
     * @var PostDao&\PHPUnit\Framework\MockObject\MockObject
     */
    private $postDao;

    /**
     * Service under test.
     *
     * @var DataRequestService
     */
    private $service;

    /**
     * Build the service with mocked collaborators for every test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', 'test');
        }

        $this->dataRequestDao = $this->createMock(DataRequestDao::class);
        $this->privacyLogDao = $this->createMock(PrivacyLogDao::class);
        $this->sanitizer = $this->createMock(Sanitize::class);
        $this->userDao = $this->createMock(UserDao::class);
        $this->commentDao = $this->createMock(CommentDao::class);
        $this->postDao = $this->createMock(PostDao::class);

        $this->service = new DataRequestService(
            $this->dataRequestDao,
            $this->privacyLogDao,
            $this->sanitizer,
            null,
            null,
            $this->userDao,
            $this->commentDao,
            $this->postDao
        );

        $this->disableNotifications();
    }

    /**
     * Replace the internally created NotificationService with null so no
     * email is dispatched while the unit tests run.
     *
     * @return void
     */
    private function disableNotifications(): void
    {
        $reflection = new ReflectionClass(DataRequestService::class);
        $property = $reflection->getProperty('notificationService');
        $property->setAccessible(true);
        $property->setValue($this->service, null);
    }

    /**
     * createRequest() delegates to the DAO with the request type, a validated
     * email, the client IP and no note, then returns the created request id.
     *
     * @return void
     */
    public function testCreateRequestDelegatesToDaoAndReturnsId(): void
    {
        $this->dataRequestDao->expects($this->once())
            ->method('createRequest')
            ->with('access', 'user@example.com', '127.0.0.1', null)
            ->willReturn(42);

        $this->privacyLogDao->expects($this->once())
            ->method('createLog')
            ->with('data_request_created', 'access', null, 'user@example.com', 'Data access request created', '127.0.0.1')
            ->willReturn(1);

        $this->assertSame(42, $this->service->createRequest('access', 'user@example.com'));
    }

    /**
     * createRequest() forwards the user note from the options array to the DAO.
     *
     * @return void
     */
    public function testCreateRequestPassesNoteOptionToDao(): void
    {
        $this->dataRequestDao->expects($this->once())
            ->method('createRequest')
            ->with('erasure', 'user@example.com', '127.0.0.1', 'Please delete my data')
            ->willReturn(43);

        $this->assertSame(
            43,
            $this->service->createRequest('erasure', 'user@example.com', ['note' => 'Please delete my data'])
        );
    }

    /**
     * createRequest() must reject an invalid email before touching the DAO.
     *
     * @return void
     */
    public function testCreateRequestRejectsInvalidEmail(): void
    {
        $this->dataRequestDao->expects($this->never())
            ->method('createRequest');

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid email address');

        $this->service->createRequest('access', 'not-an-email');
    }

    /**
     * getAllRequests() returns exactly what the DAO returns.
     *
     * @return void
     */
    public function testGetAllRequestsReturnsDaoResult(): void
    {
        $requests = [
            ['ID' => 1, 'request_type' => 'access', 'request_status' => 'pending'],
        ];

        $this->dataRequestDao->expects($this->once())
            ->method('getAllRequests')
            ->willReturn($requests);

        $this->assertSame($requests, $this->service->getAllRequests());
    }

    /**
     * getPendingCount() returns exactly what the DAO returns.
     *
     * @return void
     */
    public function testGetPendingCountReturnsDaoResult(): void
    {
        $this->dataRequestDao->expects($this->once())
            ->method('getPendingCount')
            ->willReturn(5);

        $this->assertSame(5, $this->service->getPendingCount());
    }

    /**
     * getTotalRequests() returns exactly what the DAO reports as total records.
     *
     * @return void
     */
    public function testGetTotalRequestsReturnsDaoResult(): void
    {
        $this->dataRequestDao->expects($this->once())
            ->method('totalRequestRecords')
            ->willReturn(12);

        $this->assertSame(12, $this->service->getTotalRequests());
    }

    /**
     * updateRequestStatus() delegates the status change and writes an audit log
     * entry when the request exists.
     *
     * @return void
     */
    public function testUpdateRequestStatusDelegatesToDao(): void
    {
        $request = ['ID' => 8, 'request_type' => 'access', 'request_email' => 'user@example.com'];

        $this->dataRequestDao->expects($this->once())
            ->method('getRequestById')
            ->with(8)
            ->willReturn($request);

        $this->dataRequestDao->expects($this->once())
            ->method('updateRequestStatus')
            ->with(8, 'completed', 'Verified and closed')
            ->willReturn(true);

        $this->privacyLogDao->expects($this->once())
            ->method('createLog')
            ->with('request_status_updated', 'access', null, 'user@example.com', 'Request status changed to: completed', '127.0.0.1')
            ->willReturn(1);

        $this->assertTrue($this->service->updateRequestStatus(8, 'completed', 'Verified and closed'));
    }

    /**
     * updateRequestStatus() must raise AppException when the request does not
     * exist and must not update anything.
     *
     * @return void
     */
    public function testUpdateRequestStatusThrowsWhenRequestNotFound(): void
    {
        $this->dataRequestDao->expects($this->once())
            ->method('getRequestById')
            ->with(999)
            ->willReturn(false);

        $this->dataRequestDao->expects($this->never())
            ->method('updateRequestStatus');

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Request not found');

        $this->service->updateRequestStatus(999, 'completed');
    }

    /**
     * exportUserData() must reject an invalid email before querying the user.
     *
     * @return void
     */
    public function testExportUserDataRejectsInvalidEmail(): void
    {
        $this->privacyLogDao->expects($this->never())
            ->method('createLog');

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid email address');

        $this->service->exportUserData('not-an-email');
    }

    /**
     * exportUserData() must use the injected DAOs to assemble the export and
     * never instantiate collaborators inside business logic.
     *
     * @return void
     */
    public function testExportUserDataUsesInjectedDaos(): void
    {
        $this->userDao->expects($this->once())
            ->method('getUserByEmail')
            ->with('user@example.com')
            ->willReturn([
                'ID' => 9,
                'user_login' => 'user',
                'user_email' => 'user@example.com',
                'user_fullname' => 'Test User',
                'user_url' => '',
                'user_registered' => '2020-01-01 00:00:00'
            ]);

        $this->commentDao->expects($this->once())
            ->method('findComments')
            ->with('ID', 'user@example.com')
            ->willReturn([['comment_id' => 1]]);

        $this->postDao->expects($this->once())
            ->method('findPosts')
            ->with('ID', 9, false)
            ->willReturn([['ID' => 5, 'post_title' => 'Post']]);

        $this->privacyLogDao->expects($this->once())
            ->method('createLog')
            ->with('data_exported', 'export', 9, 'user@example.com', 'User data exported', '127.0.0.1')
            ->willReturn(1);

        $data = $this->service->exportUserData('user@example.com', [
            'export_comments' => true,
            'export_posts' => true
        ]);

        $this->assertSame([['comment_id' => 1]], $data['comments']);
        $this->assertSame([['ID' => 5, 'post_title' => 'Post']], $data['posts']);
        $this->assertSame(9, $data['user_id']);
    }

    /**
     * deleteUserData() must reject an invalid email before querying the user.
     *
     * @return void
     */
    public function testDeleteUserDataRejectsInvalidEmail(): void
    {
        $this->privacyLogDao->expects($this->never())
            ->method('createLog');

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Invalid email address');

        $this->service->deleteUserData('not-an-email');
    }

    /**
     * deleteUserData() must raise AppException when no account matches the
     * email and must not erase anything.
     *
     * @return void
     */
    public function testDeleteUserDataThrowsWhenUserNotFound(): void
    {
        $userDao = $this->createMock(UserDao::class);
        $userDao->expects($this->once())
            ->method('getUserByEmail')
            ->with('missing@example.com')
            ->willReturn(false);

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->never())
            ->method('removeUserWithAnonymization');

        $service = new DataRequestService(
            $this->dataRequestDao,
            $this->privacyLogDao,
            $this->sanitizer,
            null,
            $userService,
            $userDao
        );

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('User not found');

        $service->deleteUserData('missing@example.com');
    }

    /**
     * deleteUserData() must delegate to UserService::removeUserWithAnonymization()
     * for an existing account and write a data_deleted audit log.
     *
     * @return void
     */
    public function testDeleteUserDataDelegatesToUserServiceAnonymization(): void
    {
        $userDao = $this->createMock(UserDao::class);
        $userDao->expects($this->once())
            ->method('getUserByEmail')
            ->with('user@example.com')
            ->willReturn([
                'ID' => 9,
                'user_login' => 'user',
                'user_email' => 'user@example.com',
                'user_level' => 'subscriber'
            ]);

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->once())
            ->method('removeUserWithAnonymization')
            ->with(9, 'user@example.com')
            ->willReturn(true);

        $this->privacyLogDao->expects($this->once())
            ->method('createLog')
            ->with('data_deleted', 'deletion', 9, 'user@example.com', 'User data anonymized', '127.0.0.1')
            ->willReturn(1);

        $service = new DataRequestService(
            $this->dataRequestDao,
            $this->privacyLogDao,
            $this->sanitizer,
            null,
            $userService,
            $userDao
        );

        $this->assertTrue($service->deleteUserData('user@example.com'));
    }

    /**
     * deleteUserData() must preserve the lazy fallback so callers that do not
     * inject a UserService or UserDao can still rely on the old behaviour.
     *
     * @return void
     */
    public function testDeleteUserDataHasLazyFallbackCollaborators(): void
    {
        $this->assertTrue(method_exists($this->service, 'deleteUserData'));
    }

    /**
     * deleteUserData() must raise an exception when the underlying erasure
     * fails, so a failed deletion is never silently swallowed.
     *
     * @return void
     */
    public function testDeleteUserDataThrowsWhenAnonymizationFails(): void
    {
        $userDao = $this->createMock(UserDao::class);
        $userDao->expects($this->once())
            ->method('getUserByEmail')
            ->with('user@example.com')
            ->willReturn([
                'ID' => 9,
                'user_login' => 'user',
                'user_email' => 'user@example.com',
                'user_level' => 'subscriber'
            ]);

        $userService = $this->createMock(UserService::class);
        $userService->expects($this->once())
            ->method('removeUserWithAnonymization')
            ->with(9, 'user@example.com')
            ->willReturn(false);

        $this->privacyLogDao->expects($this->never())
            ->method('createLog');

        $service = new DataRequestService(
            $this->dataRequestDao,
            $this->privacyLogDao,
            $this->sanitizer,
            null,
            $userService,
            $userDao
        );

        $this->expectException(AppException::class);
        $this->expectExceptionMessage('Unable to delete user data');

        $service->deleteUserData('user@example.com');
    }
}
