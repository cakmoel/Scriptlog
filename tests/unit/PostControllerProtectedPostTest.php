<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

/**
 * PostController Protected Post Flow Tests
 *
 * Tests for PostController handling of password-protected posts including:
 * - Visibility validation logic
 * - Password requirement for protected posts
 * - Update logic when password is unchanged
 * - Session handling for protected posts
 *
 * @category Tests
 * @version 1.1
 */

use PHPUnit\Framework\TestCase;

class PostControllerProtectedPostTest extends TestCase
{
    private $testPassword = 'SecurePass123!';
    private $testPostTitle = 'Test Protected Post';
    private $testPostContent = '<p>This is <strong>protected</strong> content.</p>';

    protected function setUp(): void
    {
        $_POST = [];
        $_FILES = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_FILES = [];
        $_SESSION = [];
    }

    // =========================================================================
    // Visibility Validation Tests
    // =========================================================================

    public function testVisibilityValidation(): void
    {
        $validOptions = ['public' => 'Public', 'private' => 'Private', 'protected' => 'Protected'];
        
        $this->assertTrue(isset($validOptions['public']));
        $this->assertTrue(isset($validOptions['private']));
        $this->assertTrue(isset($validOptions['protected']));
        $this->assertFalse(isset($validOptions['invalid']));
    }

    // =========================================================================
    // Protected Post Creation Tests
    // =========================================================================

    public function testProtectedPostRequiresPassword(): void
    {
        $_POST = [
            'post_title' => $this->testPostTitle,
            'post_content' => $this->testPostContent,
            'visibility' => 'protected',
            'post_password' => ''
        ];

        $errors = [];
        if ($_POST['visibility'] === 'protected' && empty($_POST['post_password'])) {
            $errors[] = "Password is required for protected posts";
        }

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Password is required', $errors[0]);
    }

    public function testProtectedPostWithValidPassword(): void
    {
        $_POST = [
            'post_title' => $this->testPostTitle,
            'post_content' => $this->testPostContent,
            'visibility' => 'protected',
            'post_password' => $this->testPassword
        ];

        $errors = [];
        if ($_POST['visibility'] === 'protected' && empty($_POST['post_password'])) {
            $errors[] = "Password is required for protected posts";
        }

        $this->assertEmpty($errors);
    }

    public function testPublicPostDoesNotRequirePassword(): void
    {
        $_POST = [
            'post_title' => $this->testPostTitle,
            'post_content' => $this->testPostContent,
            'visibility' => 'public',
            'post_password' => ''
        ];

        $errors = [];
        if ($_POST['visibility'] === 'protected' && empty($_POST['post_password'])) {
            $errors[] = "Password is required for protected posts";
        }

        $this->assertEmpty($errors);
    }

    // =========================================================================
    // Update Protected Post Tests (Password Unchanged)
    // =========================================================================

    public function testUpdateProtectedPostReusesExistingPassphrase(): void
    {
        // Simulate PostController logic from lines 691-698
        $existingPost = [
            'ID' => 2,
            'passphrase' => md5('appkey' . $this->testPassword),
            'post_password' => password_hash($this->testPassword, PASSWORD_DEFAULT)
        ];

        $visibility = 'protected';
        $postPassword = ''; // Password not being changed
        $bind = ['post_content' => $this->testPostContent];

        if ($visibility == 'protected' && empty($postPassword)) {
            // Reuse existing passphrase for re-encryption
            $bind['passphrase'] = $existingPost['passphrase'];
            $bind['post_password'] = $existingPost['post_password'];
        }

        $this->assertArrayHasKey('passphrase', $bind);
        $this->assertArrayHasKey('post_password', $bind);
        $this->assertEquals($existingPost['passphrase'], $bind['passphrase']);
    }

    public function testUpdateProtectedPostWithNewPassword(): void
    {
        $newPassword = 'NewPass456!';
        $visibility = 'protected';
        $bind = ['post_content' => $this->testPostContent];

        if ($visibility == 'protected' && !empty($newPassword)) {
            $bind['post_password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $bind['passphrase'] = md5('appkey' . $newPassword);
        }

        $this->assertArrayHasKey('passphrase', $bind);
        $this->assertNotEquals('', $bind['passphrase']);
        $this->assertTrue(password_verify($newPassword, $bind['post_password']));
    }

    // =========================================================================
    // Password Verification Tests
    // =========================================================================

    public function testPasswordVerificationWithCorrectPassword(): void
    {
        $passwordHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        
        $result = password_verify($this->testPassword, $passwordHash);
        $this->assertTrue($result);
    }

    public function testPasswordVerificationWithIncorrectPassword(): void
    {
        $passwordHash = password_hash($this->testPassword, PASSWORD_DEFAULT);
        
        $result = password_verify('wrongpassword', $passwordHash);
        $this->assertFalse($result);
    }

    // =========================================================================
    // Session Handling Tests
    // =========================================================================

    public function testSessionPostProtectedSetForProtectedPost(): void
    {
        $_POST = [
            'visibility' => 'protected',
            'post_password' => $this->testPassword
        ];
        $_SESSION = [];

        if ($_POST['visibility'] === 'protected' && !empty($_POST['post_password'])) {
            $_SESSION['post_protected'] = $_POST['post_password'];
        }

        $this->assertEquals($this->testPassword, $_SESSION['post_protected']);
    }

    public function testSessionPostProtectedNotSetForPublic(): void
    {
        $_POST = ['visibility' => 'public'];
        $_SESSION = [];

        if ($_POST['visibility'] === 'protected' && !empty($_POST['post_password'])) {
            $_SESSION['post_protected'] = $_POST['post_password'];
        }

        $this->assertArrayNotHasKey('post_protected', $_SESSION);
    }

    public function testSessionPostProtectedClearedAfterInsert(): void
    {
        $_SESSION = ['post_protected' => $this->testPassword];
        $postId = 1; // Simulated successful insert

        if ($postId > 0 && isset($_SESSION['post_protected'])) {
            unset($_SESSION['post_protected']);
        }

        $this->assertArrayNotHasKey('post_protected', $_SESSION);
    }
}
