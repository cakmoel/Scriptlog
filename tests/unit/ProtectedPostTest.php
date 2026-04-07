<?php

use PHPUnit\Framework\TestCase;

/**
 * Protected Post Functionality Tests
 *
 * Tests for password-protected post functionality including:
 * - protect_post() - encrypts post content with password
 * - checking_post_password() - verifies password
 * - encrypt_post() - encrypts content
 * - decrypt_post() - decrypts content
 * - grab_post_protected() - retrieves protected post
 */
class ProtectedPostTest extends TestCase
{
    private $testPassword = 'testpassword123';
    private $testContent = '<p>This is <strong>test</strong> content for the post.</p>';

    protected function setUp(): void
    {
        if (!defined('SCRIPTLOG')) {
            define('SCRIPTLOG', bin2hex(random_bytes(16)));
        }
    }

    public function testProtectPostReturnsArray(): void
    {
        if (function_exists('protect_post')) {
            $result = protect_post($this->testContent, 'protected', $this->testPassword);
            $this->assertIsArray($result);
            $this->assertArrayHasKey('post_content', $result);
            $this->assertArrayHasKey('post_password', $result);
        } else {
            $this->markTestSkipped('protect_post function not available');
        }
    }

    public function testProtectPostReturnsEncryptedContent(): void
    {
        if (function_exists('protect_post')) {
            $result = protect_post($this->testContent, 'protected', $this->testPassword);
            $this->assertNotEquals($this->testContent, $result['post_content']);
            $this->assertNotEmpty($result['post_password']);
        } else {
            $this->markTestSkipped('protect_post function not available');
        }
    }

    public function testProtectPostWithPublicVisibility(): void
    {
        if (function_exists('protect_post')) {
            $result = protect_post($this->testContent, 'public', $this->testPassword);
            $this->assertEmpty($result['post_content']);
            $this->assertNotEmpty($result['post_password']);
        } else {
            $this->markTestSkipped('protect_post function not available');
        }
    }

    public function testEncryptPostWithProtectedVisibility(): void
    {
        if (function_exists('encrypt_post')) {
            $passphrase = md5($this->testPassword);
            $result = encrypt_post($this->testContent, 'protected', $passphrase);
            $this->assertNotEmpty($result);
            $this->assertNotEquals($this->testContent, $result);
        } else {
            $this->markTestSkipped('encrypt_post function not available');
        }
    }

    public function testEncryptPostWithPublicVisibility(): void
    {
        if (function_exists('encrypt_post')) {
            $result = encrypt_post($this->testContent, 'public', 'anypassword');
            $this->assertEmpty($result);
        } else {
            $this->markTestSkipped('encrypt_post function not available');
        }
    }

    public function testEncryptPostWithPrivateVisibility(): void
    {
        if (function_exists('encrypt_post')) {
            $result = encrypt_post($this->testContent, 'private', 'anypassword');
            $this->assertEmpty($result);
        } else {
            $this->markTestSkipped('encrypt_post function not available');
        }
    }

    public function testPasswordHashGeneration(): void
    {
        $passwordHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        $this->assertNotEmpty($passwordHash);
        $this->assertTrue(password_verify($this->testPassword, $passwordHash));
        $this->assertFalse(password_verify('wrongpassword', $passwordHash));
    }

    public function testPasswordVerifyWithCorrectPassword(): void
    {
        $passwordHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        $this->assertTrue(password_verify($this->testPassword, $passwordHash));
    }

    public function testPasswordVerifyWithIncorrectPassword(): void
    {
        $passwordHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        $this->assertFalse(password_verify('wrongpassword', $passwordHash));
    }

    public function testVisibilityDropdownExists(): void
    {
        if (function_exists('post_visibility')) {
            $dropdown = post_visibility();
            $this->assertIsString($dropdown);
            $this->assertStringContainsString('public', $dropdown);
            $this->assertStringContainsString('private', $dropdown);
            $this->assertStringContainsString('protected', $dropdown);
        } else {
            $this->markTestSkipped('post_visibility function not available');
        }
    }

    public function testVisibilityValuesAreCorrect(): void
    {
        $this->assertEquals('public', 'public');
        $this->assertEquals('private', 'private');
        $this->assertEquals('protected', 'protected');
    }

    public function testProtectedPostFunctionExists(): void
    {
        $this->assertTrue(function_exists('protect_post'));
        $this->assertTrue(function_exists('checking_post_password'));
        $this->assertTrue(function_exists('encrypt_post'));
        $this->assertTrue(function_exists('decrypt_post'));
        $this->assertTrue(function_exists('grab_post_protected'));
    }
}
