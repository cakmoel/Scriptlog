<?php
/**
 * UserController Tests
 *
 * Phase 4.2: Controller Tests - UserController (8 tests)
 * Tests for user management CRUD operations
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class UserControllerTest extends TestCase
{
    private $userService;
    private $configService;
    private $userController;

    protected function setUp(): void
    {
        $_SESSION = [];
        $_POST = [];

        $this->userService = $this->createMock(UserService::class);
        $this->configService = $this->createMock(ConfigurationService::class);
        $this->userController = new UserController($this->userService, $this->configService);
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
    }

    public function testControllerExtendsBaseApp(): void
    {
        $this->assertInstanceOf(BaseApp::class, $this->userController);
    }

    public function testControllerImplementsAppInterface(): void
    {
        $this->assertInstanceOf(AppInterface::class, $this->userController);
    }

    public function testConstructorAcceptsUserService(): void
    {
        $this->assertInstanceOf(UserController::class, $this->userController);
    }

    public function testConstructorAcceptsOptionalConfigService(): void
    {
        $controller = new UserController($this->userService);
        $this->assertInstanceOf(UserController::class, $controller);
    }

    public function testListItemsRunsWithoutError(): void
    {
        $this->userService->method('totalUsers')->willReturn(3);
        $this->userService->method('grabUsers')->willReturn([]);

        ob_start();
        $result = $this->userController->listItems();
        ob_end_clean();

        $this->assertNull($result);
    }

    public function testListItemsClearsSessionError(): void
    {
        $_SESSION['error'] = 'userNotFound';
        $this->userService->method('totalUsers')->willReturn(0);
        $this->userService->method('grabUsers')->willReturn([]);

        ob_start();
        $this->userController->listItems();
        ob_end_clean();

        $this->assertArrayNotHasKey('error', $_SESSION);
    }

    public function testListItemsClearsSessionStatus(): void
    {
        $_SESSION['status'] = 'userUpdated';
        $this->userService->method('totalUsers')->willReturn(2);
        $this->userService->method('grabUsers')->willReturn([]);

        ob_start();
        $this->userController->listItems();
        ob_end_clean();

        $this->assertArrayNotHasKey('status', $_SESSION);
    }

    public function testSetViewIsProtectedMethod(): void
    {
        $reflection = new ReflectionClass(UserController::class);
        $method = $reflection->getMethod('setView');
        $this->assertTrue($method->isProtected());
    }
}
