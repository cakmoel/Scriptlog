<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * DataRequestErasure Test
 *
 * Covers the v1.6.0 erasure and export flow in DataRequestService:
 * deleteUserData() now delegates to UserService::removeUserWithAnonymization()
 * and exportUserData() streams comments/posts from the DAO layer.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class DataRequestErasureTest extends TestCase
{
    private $dataRequestDaoMock;

    private $privacyLogDaoMock;

    private $sanitizeMock;

    private $configServiceMock;

    private $userServiceMock;

    private $userDaoMock;

    private $commentDaoMock;

    private $postDaoMock;

    private $dataRequestService;

    protected function setUp(): void
    {
        $this->dataRequestDaoMock = $this->createMock(\Scriptlog\Dao\DataRequestDao::class);
        $this->privacyLogDaoMock = $this->createMock(\Scriptlog\Dao\PrivacyLogDao::class);
        $this->sanitizeMock = $this->createMock(\Scriptlog\Core\Sanitize::class);
        $this->configServiceMock = $this->createMock(\Scriptlog\Service\ConfigurationService::class);
        $this->userServiceMock = $this->createMock(\Scriptlog\Service\UserService::class);
        $this->userDaoMock = $this->createMock(\Scriptlog\Dao\UserDao::class);
        $this->commentDaoMock = $this->createMock(\Scriptlog\Dao\CommentDao::class);
        $this->postDaoMock = $this->createMock(\Scriptlog\Dao\PostDao::class);

        $this->dataRequestService = new \Scriptlog\Service\DataRequestService(
            $this->dataRequestDaoMock,
            $this->privacyLogDaoMock,
            $this->sanitizeMock,
            $this->configServiceMock,
            $this->userServiceMock,
            $this->userDaoMock,
            $this->commentDaoMock,
            $this->postDaoMock
        );
    }

    private function existingUser(): array
    {
        return [
            'ID' => 5,
            'user_login' => 'john',
            'user_email' => 'john@example.com',
            'user_fullname' => 'John Doe',
            'user_url' => 'https://example.com',
            'user_registered' => '2026-01-01 00:00:00',
        ];
    }

    public function testDeleteUserDataAnonymizesAccountAndLogs(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn($this->existingUser());
        $this->userServiceMock->expects($this->once())
            ->method('removeUserWithAnonymization')
            ->with(5, 'john@example.com')
            ->willReturn(true);

        $this->privacyLogDaoMock->expects($this->once())
            ->method('createLog')
            ->with('data_deleted', 'deletion', 5, 'john@example.com');

        $result = $this->dataRequestService->deleteUserData('john@example.com');

        $this->assertTrue($result);
    }

    public function testDeleteUserDataThrowsWhenUserNotFound(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn(false);

        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->deleteUserData('ghost@example.com');
    }

    public function testDeleteUserDataThrowsWhenErasureFails(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn($this->existingUser());
        $this->userServiceMock->method('removeUserWithAnonymization')->willReturn(false);

        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->deleteUserData('john@example.com');
    }

    public function testDeleteUserDataThrowsOnInvalidEmail(): void
    {
        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->dataRequestService->deleteUserData('not-an-email');
    }

    public function testExportUserDataExportsProfileCommentsAndPosts(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn($this->existingUser());
        $this->commentDaoMock->method('findComments')->willReturn([['ID' => 11]]);
        $this->postDaoMock->method('findPosts')->willReturn([['ID' => 22]]);
        $this->privacyLogDaoMock->method('getLogsByEmail')->willReturn([['ID' => 33]]);

        $result = $this->dataRequestService->exportUserData('john@example.com', [
            'export_comments' => true,
            'export_posts' => true,
            'export_activity' => true,
        ]);

        $this->assertSame('john', $result['profile']['user_login']);
        $this->assertSame(5, $result['user_id']);
        $this->assertCount(1, $result['comments']);
        $this->assertCount(1, $result['posts']);
        $this->assertCount(1, $result['activity']);
    }

    public function testExportUserDataSkipsCollectionsWhenOptionsDisabled(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn($this->existingUser());

        $this->commentDaoMock->expects($this->never())
            ->method('findComments');

        $result = $this->dataRequestService->exportUserData('john@example.com', []);

        $this->assertSame([], $result['comments']);
        $this->assertSame([], $result['posts']);
    }

    public function testExportUserDataLogsExportEvent(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn($this->existingUser());

        $this->privacyLogDaoMock->expects($this->once())
            ->method('createLog')
            ->with('data_exported', 'export', 5, 'john@example.com');

        $this->dataRequestService->exportUserData('john@example.com');
    }

    public function testExportUserDataReturnsBaseStructureWhenUserUnknown(): void
    {
        $this->userDaoMock->method('getUserByEmail')->willReturn(false);

        $result = $this->dataRequestService->exportUserData('ghost@example.com');

        $this->assertArrayHasKey('profile', $result);
        $this->assertArrayHasKey('comments', $result);
        $this->assertArrayHasKey('posts', $result);
        $this->assertSame([], $result['profile']);
    }
}
