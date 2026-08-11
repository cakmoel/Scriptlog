<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * UserErasureService Test
 *
 * Covers UserService::removeUserWithAnonymization() — the GDPR account
 * erasure flow introduced in v1.6.0. Verifies comment anonymization, post
 * reassignment to a validated fallback author, transactional execution and
 * the no-fallback-author error path.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class UserErasureServiceTest extends TestCase
{
    private $userService;

    private $userDaoMock;

    private $formValidatorMock;

    private $sanitizeMock;

    private $userTokenMock;

    private $commentDaoMock;

    private $postDaoMock;

    protected function setUp(): void
    {
        $this->userDaoMock = $this->createMock(\Scriptlog\Dao\UserDao::class);
        $this->formValidatorMock = $this->createMock(\Scriptlog\Core\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Scriptlog\Core\Sanitize::class);
        $this->userTokenMock = $this->createMock(\Scriptlog\Dao\UserTokenDao::class);
        $this->commentDaoMock = $this->createMock(\Scriptlog\Dao\CommentDao::class);
        $this->postDaoMock = $this->createMock(\Scriptlog\Dao\PostDao::class);

        $this->formValidatorMock->method('sanitize')->willReturnArgument(0);

        $this->userService = new \Scriptlog\Service\UserService(
            $this->userDaoMock,
            $this->formValidatorMock,
            $this->userTokenMock,
            $this->sanitizeMock,
            $this->commentDaoMock,
            $this->postDaoMock
        );
    }

    private function runTransactionInPlace(): void
    {
        $this->userDaoMock->method('runInTransaction')
            ->willReturnCallback(function ($work) {
                return $work();
            });
    }

    public function testRemoveUserWithoutEmailOnlyDeletesAccount(): void
    {
        $this->runTransactionInPlace();

        $this->userDaoMock->expects($this->once())
            ->method('deleteUser')
            ->willReturn(true);

        $this->commentDaoMock->expects($this->never())
            ->method('anonymizeCommentsByEmail');

        $this->postDaoMock->expects($this->never())
            ->method('anonymizePostAuthor');

        $result = $this->userService->removeUserWithAnonymization(42);

        $this->assertTrue($result);
    }

    public function testRemoveUserAnonymizesCommentsForProvidedEmail(): void
    {
        $this->runTransactionInPlace();

        $this->userDaoMock->method('getUserById')->willReturn(['ID' => 1]);
        $this->userDaoMock->method('deleteUser')->willReturn(true);

        $this->commentDaoMock->expects($this->once())
            ->method('anonymizeCommentsByEmail')
            ->with('erased@example.com');

        $this->userService->removeUserWithAnonymization(42, 'erased@example.com');
    }

    public function testRemoveUserReassignsPostsToUserIdOneWhenAvailable(): void
    {
        $this->runTransactionInPlace();

        $this->userDaoMock->method('getUserById')->willReturn(['ID' => 1]);
        $this->userDaoMock->method('deleteUser')->willReturn(true);

        $this->postDaoMock->expects($this->once())
            ->method('anonymizePostAuthor')
            ->with(42, 1);

        $this->userService->removeUserWithAnonymization(42, 'erased@example.com');
    }

    public function testRemoveUserDoesNotReassignToErasedAccount(): void
    {
        $this->runTransactionInPlace();

        $this->userDaoMock->method('getUserById')->willReturn(['ID' => 42]);
        $this->userDaoMock->method('getUsers')->willReturn([
            ['ID' => 1],
            ['ID' => 42],
        ]);
        $this->userDaoMock->method('deleteUser')->willReturn(true);

        $this->postDaoMock->expects($this->once())
            ->method('anonymizePostAuthor')
            ->with(42, 1);

        $this->userService->removeUserWithAnonymization(42, 'erased@example.com');
    }

    public function testRemoveUserFallsBackToFirstOtherUserWhenIdOneUnavailable(): void
    {
        $this->runTransactionInPlace();

        $this->userDaoMock->method('getUserById')->willReturn(false);
        $this->userDaoMock->method('getUsers')->willReturn([
            ['ID' => 7],
            ['ID' => 9],
        ]);
        $this->userDaoMock->method('deleteUser')->willReturn(true);

        $this->postDaoMock->expects($this->once())
            ->method('anonymizePostAuthor')
            ->with(42, 7);

        $this->userService->removeUserWithAnonymization(42, 'erased@example.com');
    }

    public function testRemoveUserThrowsWhenNoFallbackAuthorExists(): void
    {
        $this->runTransactionInPlace();

        $this->userDaoMock->method('getUserById')->willReturn(false);
        $this->userDaoMock->method('getUsers')->willReturn([]);

        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->userService->removeUserWithAnonymization(42, 'erased@example.com');
    }

    public function testRemoveUserThrowsWhenOnlyErasedAccountRemains(): void
    {
        $this->runTransactionInPlace();

        $this->userDaoMock->method('getUserById')->willReturn(false);
        $this->userDaoMock->method('getUsers')->willReturn([
            ['ID' => 42],
        ]);

        $this->expectException(\Scriptlog\Core\AppException::class);

        $this->userService->removeUserWithAnonymization(42, 'erased@example.com');
    }

    public function testRemoveUserReturnsAccountDeletionResult(): void
    {
        $this->runTransactionInPlace();

        $this->userDaoMock->method('getUserById')->willReturn(['ID' => 1]);
        $this->userDaoMock->method('deleteUser')->willReturn(false);

        $result = $this->userService->removeUserWithAnonymization(42, 'erased@example.com');

        $this->assertFalse($result);
    }
}
