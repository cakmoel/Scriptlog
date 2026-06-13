<?php
/**
 * Authentication Tests
 *
 * Phase 3.1: Core Classes - Authentication (10 tests)
 * Tests for user access control, cookie handling, session management
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    private $userDao;
    private $userToken;
    private $validator;
    private $authentication;

    protected function setUp(): void
    {
        $_COOKIE = [];
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Agent';

        // Create mocks
        $this->userDao = $this->createMock(UserDao::class);
        $this->userToken = $this->createMock(UserTokenDao::class);
        $this->validator = $this->createMock(FormValidator::class);

        $this->authentication = new Authentication(
            $this->userDao,
            $this->userToken,
            $this->validator
        );
    }

    protected function tearDown(): void
    {
        $_COOKIE = [];
        $_SESSION = [];
    }

    // =========================================================================
    // Constructor Tests
    // =========================================================================

    public function testConstructorSetsCookiePath(): void
    {
        $reflection = new ReflectionClass(Authentication::class);
        $cookiePath = $reflection->getConstant('COOKIE_PATH');

        $this->assertEquals('/', $cookiePath);
    }

    public function testConstructorSetsCookieExpire(): void
    {
        $reflection = new ReflectionClass(Authentication::class);
        $cookieExpire = $reflection->getConstant('COOKIE_EXPIRE');

        $this->assertIsInt($cookieExpire);
        $this->assertEquals(3600, $cookieExpire);
    }

    // =========================================================================
    // userAccessControl Tests
    // =========================================================================

    public function testUserAccessControlReturnsTrueForAdministrator(): void
    {
        Session::getInstance()->scriptlog_session_login = 'admin';
        Session::getInstance()->scriptlog_session_level = 'administrator';

        $result = $this->authentication->userAccessControl(ActionConst::USERS);

        $this->assertTrue($result);
    }

    public function testUserAccessControlReturnsFalseForUnauthorized(): void
    {
        Session::getInstance()->scriptlog_session_login = 'user';
        Session::getInstance()->scriptlog_session_level = 'subscriber';

        $result = $this->authentication->userAccessControl(ActionConst::USERS);

        $this->assertFalse($result);
    }

    public function testUserAccessControlWithPrivacyAction(): void
    {
        Session::getInstance()->scriptlog_session_login = 'editor';
        Session::getInstance()->scriptlog_session_level = 'editor';

        $result = $this->authentication->userAccessControl(ActionConst::PRIVACY);

        $this->assertFalse($result);
    }

    // =========================================================================
    // Login Method Structure Tests
    // =========================================================================

    public function testLoginMethodExists(): void
    {
        $this->assertTrue(method_exists($this->authentication, 'login'));
    }

    public function testLoginReturnsNullOnInvalidUser(): void
    {
        $this->userDao->method('getUserByLogin')
            ->willReturn(false);

        $values = [
            'login' => 'nonexistent',
            'user_pass' => 'wrongpass',
            'remember' => false
        ];

        $result = @$this->authentication->login($values);

        // login() returns null when user not found (no explicit return)
        $this->assertNull($result);
    }

    public function testValidateUserAccountMethodExists(): void
    {
        $this->assertTrue(method_exists($this->authentication, 'validateUserAccount'));
    }

    // =========================================================================
    // accessLevel Tests
    // =========================================================================

    public function testAccessLevelReturnsCorrectLevel(): void
    {
        Session::getInstance()->scriptlog_session_login = 'editor';
        Session::getInstance()->scriptlog_session_level = 'editor';

        $result = $this->authentication->accessLevel();

        $this->assertEquals('editor', $result);
    }

    public function testAccessLevelReturnsFalseWhenNotSet(): void
    {
        $result = $this->authentication->accessLevel();

        $this->assertFalse($result);
    }
}
