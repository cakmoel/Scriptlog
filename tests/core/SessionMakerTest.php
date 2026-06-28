<?php
/**
 * SessionMaker Tests
 *
 * Phase 3.2: Core Classes - SessionMaker (8 tests)
 * Tests for session management, encryption, fingerprinting
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class SessionMakerTest extends TestCase
{
    private $sessionMaker;
    private $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Agent';
        $_SESSION = [];
        $_COOKIE = [];

        $this->sessionMaker = new SessionMaker('test-key-12345', '_scriptlog_test');
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_COOKIE = [];
        $_SERVER = $this->originalServer;
    }

    public function testConstructorSetsKey(): void
    {
        $reflection = new ReflectionClass(SessionMaker::class);
        $key = $reflection->getProperty('key');
        $key->setAccessible(true);

        $expected = hash('sha512', 'test-key-12345', true);
        $this->assertEquals($expected, $key->getValue($this->sessionMaker));
    }

    public function testIsExpiredReturnsFalseInitially(): void
    {
        $result = $this->sessionMaker->isExpired(60);
        $this->assertFalse($result);
    }

    public function testIsExpiredReturnsTrueAfterExpiry(): void
    {
        $_SESSION['_last_activity'] = time() - (61 * 3600);
        $result = $this->sessionMaker->isExpired(60);
        $this->assertTrue($result);
    }

    public function testIsGenuineSetsFingerprintOnFirstCall(): void
    {
        putenv('REMOTE_ADDR=192.168.1.1');
        putenv('HTTP_USER_AGENT=Mozilla/5.0 Test');
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 Test';

        $_SESSION = [];

        $reflection = new ReflectionClass(SessionMaker::class);
        $method = $reflection->getMethod('isGenuine');
        $method->setAccessible(true);

        $result = $method->invoke($this->sessionMaker);

        $this->assertTrue($result);
        $this->assertArrayHasKey('_genuine', $_SESSION);
    }

    public function testIsGenuineReturnsTrueForMatchingFingerprint(): void
    {
        $agent = 'Mozilla/5.0 Test';
        $ip = '192.168.1.1';
        putenv("REMOTE_ADDR=$ip");
        putenv("HTTP_USER_AGENT=$agent");
        $_SERVER['REMOTE_ADDR'] = $ip;
        $_SERVER['HTTP_USER_AGENT'] = $agent;

        $hash = md5($agent . (ip2long($ip) & ip2long('255.255.0.0')));
        $_SESSION['_genuine'] = $hash;

        $reflection = new ReflectionClass(SessionMaker::class);
        $method = $reflection->getMethod('isGenuine');
        $method->setAccessible(true);

        $result = $method->invoke($this->sessionMaker);
        $this->assertTrue($result);
    }

    public function testIsValidReturnsTrueWhenNotExpiredAndGenuine(): void
    {
        $_SESSION['_last_activity'] = time();
        $agent = 'PHPUnit Test Agent';
        $ip = '127.0.0.1';
        putenv("REMOTE_ADDR=$ip");
        putenv("HTTP_USER_AGENT=$agent");

        $hash = md5($agent . (ip2long($ip) & ip2long('255.255.0.0')));
        $_SESSION['_genuine'] = $hash;

        $result = $this->sessionMaker->isValid(60);
        $this->assertTrue($result);
    }

    public function testForgetReturnsFalseWhenNoActiveSession(): void
    {
        $result = $this->sessionMaker->forget();
        $this->assertFalse($result);
    }

    public function testReadMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sessionMaker, 'read'));
    }

    public function testWriteMethodExists(): void
    {
        $this->assertTrue(method_exists($this->sessionMaker, 'write'));
    }
}
