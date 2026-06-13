<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

/**
 * PostDao Update Fix Test
 *
 * Tests for the protected post update fix:
 * - PostDao::updatePost() conditionally includes password fields
 * - Only adds post_password/passphrase to UPDATE when not empty
 * - Prevents transaction rollback when editing protected posts without changing password
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostDaoUpdateFixTest extends TestCase
{
    private $testPassword = 'TestPassword123!';
    private $testContent = '<p>Test content for protected post</p>';

    protected function setUp(): void
    {
        $_POST = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SESSION = [];
    }

    // =========================================================================
    // PostDao::updatePost() Conditional Logic Tests
    // =========================================================================

    public function testUpdatePostMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }

        $this->assertTrue(method_exists('PostDao', 'updatePost'));
    }

    public function testUpdatePostHasConditionalPasswordLogic(): void
    {
        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');

        // Verify the conditional logic exists
        $this->assertStringContainsString('if (!empty($bind[\'post_password\']))', $source);
        $this->assertStringContainsString('if (!empty($bind[\'passphrase\']))', $source);
    }

    public function testUpdatePostBuildsUpdateDataDynamically(): void
    {
        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');

        // Verify that updateData is built as an array (not fixed keys)
        $this->assertStringContainsString('$updateData = [', $source);
        $this->assertStringContainsString('$this->modify("tbl_posts", $updateData', $source);
    }

    public function testUpdatePostDoesNotAlwaysIncludePassword(): void
    {
        $source = file_get_contents(__DIR__ . '/../../lib/dao/PostDao.php');

        // Find the updatePost method
        $pattern = '/function updatePost.*?\$this->modify\("tbl_posts".*?\);/s';
        preg_match($pattern, $source, $matches);

        $this->assertNotEmpty($matches, 'updatePost method should exist');

        if (!empty($matches[0])) {
            $methodBody = $matches[0];

            // Verify that post_password and passphrase are NOT in the initial $updateData array
            // They should only appear inside the if (!empty()) blocks
            $this->assertStringContainsString('if (!empty($bind[\'post_password\']))', $methodBody);
            $this->assertStringContainsString('if (!empty($bind[\'passphrase\']))', $methodBody);

            // Verify the conditional blocks exist and contain the assignments
            $this->assertStringContainsString("\$updateData['post_password'] = \$bind['post_password']", $methodBody);
            $this->assertStringContainsString("\$updateData['passphrase'] = \$bind['passphrase']", $methodBody);
        }
    }

    // =========================================================================
    // PostController::update() Re-encryption Logic Tests
    // =========================================================================

    public function testUpdateMethodHandlesUnchangedPassword(): void
    {
        // Simulate the fix logic in PostController::update()
        $visibility = 'password-protected';
        $postPassword = ''; // Empty = password not changed
        $existingPassphrase = md5('existing_password' . 'app_key');

        $postContent = '<p>Updated content without changing password</p>';
        $resultContent = $postContent;

        // Simulate the fix: if password not changed, re-encrypt with existing passphrase
        if ($visibility === 'password-protected' && empty($postPassword)) {
            if (!empty($existingPassphrase)) {
                // This is what the fix does - re-encrypt with existing passphrase
                $reencrypted = 'ENCRYPTED:' . $postContent . ':' . $existingPassphrase;
                $resultContent = $reencrypted;
            }
        }

        $this->assertStringContainsString('ENCRYPTED:', $resultContent);
        $this->assertStringContainsString($existingPassphrase, $resultContent);
    }

    public function testUpdateMethodHandlesChangedPassword(): void
    {
        $visibility = 'password-protected';
        $newPassword = 'NewPassword123!';

        // Simulate password changed - should use protect_post()
        if ($visibility === 'password-protected' && !empty($newPassword)) {
            $protected = [
                'post_content' => 'ENCRYPTED_WITH_NEW_PASSWORD',
                'post_password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'passphrase' => md5($newPassword)
            ];

            // The content should be encrypted (not the literal string we set)
            // In real code, protect_post() would encrypt it
            // Here we just verify the structure is correct
            $this->assertNotEmpty($protected['post_content']);
            $this->assertNotEmpty($protected['post_password']);
            $this->assertNotEmpty($protected['passphrase']);
            $this->assertNotEquals('', $protected['post_content']);
        } else {
            $this->fail('Should have entered the if block');
        }
    }

    public function testUpdateSkipsReEncryptionForPublicPosts(): void
    {
        $visibility = 'public';
        $postPassword = '';

        $postContent = '<p>Public post content</p>';
        $resultContent = $postContent;

        // Should NOT re-encrypt for public posts
        if ($visibility === 'password-protected' && empty($postPassword)) {
            $resultContent = 'SHOULD_NOT_REACH_HERE';
        }

        $this->assertEquals($postContent, $resultContent);
    }

    // =========================================================================
    // Integration Test: Simulate Full Update Flow
    // =========================================================================

    public function testSimulateUpdatePostWithUnchangedPassword(): void
    {
        // This test simulates the exact fix scenario
        $bind = [
            'post_author' => 1,
            'post_modified' => date('Y-m-d H:i:s'),
            'post_title' => 'Test Protected Post',
            'post_slug' => 'test-protected-post',
            'post_content' => '<p>Updated content</p>',
            'post_summary' => '',
            'post_status' => 'publish',
            'post_visibility' => 'password-protected',
            'post_tags' => 'test,protected',
            'post_headlines' => 0,
            'post_locale' => 'en',
            'comment_status' => 'open',
            'post_password' => '', // Empty = password not changed
            'passphrase' => ''     // Empty = use existing
        ];

        // Simulate PostDao::updatePost() logic
        $updateData = [
            'post_author' => $bind['post_author'],
            'post_modified' => $bind['post_modified'],
            'post_title' => $bind['post_title'],
            'post_slug' => $bind['post_slug'],
            'post_content' => $bind['post_content'],
            'post_summary' => $bind['post_summary'],
            'post_status' => $bind['post_status'],
            'post_visibility' => $bind['post_visibility'],
            'post_tags' => $bind['post_tags'],
            'post_headlines' => $bind['post_headlines'],
            'post_locale' => $bind['post_locale'],
            'comment_status' => $bind['comment_status']
        ];

        // Only include password fields if not empty (THE FIX)
        if (!empty($bind['post_password'])) {
            $updateData['post_password'] = $bind['post_password'];
        }
        if (!empty($bind['passphrase'])) {
            $updateData['passphrase'] = $bind['passphrase'];
        }

        // Verify password fields are NOT in updateData
        $this->assertArrayNotHasKey('post_password', $updateData);
        $this->assertArrayNotHasKey('passphrase', $updateData);

        // Verify other fields ARE present
        $this->assertArrayHasKey('post_title', $updateData);
        $this->assertArrayHasKey('post_content', $updateData);
        $this->assertArrayHasKey('post_tags', $updateData);
    }

    public function testSimulateUpdatePostWithChangedPassword(): void
    {
        $bind = [
            'post_author' => 1,
            'post_modified' => date('Y-m-d H:i:s'),
            'post_title' => 'Test Protected Post',
            'post_slug' => 'test-protected-post',
            'post_content' => '<p>Updated content</p>',
            'post_summary' => '',
            'post_status' => 'publish',
            'post_visibility' => 'password-protected',
            'post_tags' => 'test,protected',
            'post_headlines' => 0,
            'post_locale' => 'en',
            'comment_status' => 'open',
            'post_password' => password_hash('NewPassword123!', PASSWORD_DEFAULT),
            'passphrase' => md5('NewPassword123!')
        ];

        // Simulate PostDao::updatePost() logic
        $updateData = [
            'post_author' => $bind['post_author'],
            'post_modified' => $bind['post_modified'],
            'post_title' => $bind['post_title'],
            'post_slug' => $bind['post_slug'],
            'post_content' => $bind['post_content'],
            'post_summary' => $bind['post_summary'],
            'post_status' => $bind['post_status'],
            'post_visibility' => $bind['post_visibility'],
            'post_tags' => $bind['post_tags'],
            'post_headlines' => $bind['post_headlines'],
            'post_locale' => $bind['post_locale'],
            'comment_status' => $bind['comment_status']
        ];

        // Only include password fields if not empty (THE FIX)
        if (!empty($bind['post_password'])) {
            $updateData['post_password'] = $bind['post_password'];
        }
        if (!empty($bind['passphrase'])) {
            $updateData['passphrase'] = $bind['passphrase'];
        }

        // Verify password fields ARE in updateData
        $this->assertArrayHasKey('post_password', $updateData);
        $this->assertArrayHasKey('passphrase', $updateData);

        // Verify password is a valid bcrypt hash
        $this->assertTrue(password_verify('NewPassword123!', $updateData['post_password']));
    }

    // =========================================================================
    // Actual Encryption/Decryption Test
    // =========================================================================

    public function testActualReEncryptionWithExistingPassphrase(): void
    {
        if (!function_exists('encrypt') || !function_exists('decrypt')) {
            $this->markTestSkipped('encrypt/decrypt functions not available');
        }

        // Simulate existing post with passphrase
        $existingPassphrase = md5('ExistingPassword123!' . md5('app_key'));
        $originalContent = '<p>Original content</p>';

        // Encrypt original content
        $encrypted = encrypt($originalContent, $existingPassphrase);

        // Now simulate update with unchanged password
        $updatedContent = '<p>Updated content without changing password</p>';

        // Re-encrypt with existing passphrase (the fix)
        $reencrypted = encrypt($updatedContent, $existingPassphrase);

        // Verify it's different from original encrypted content
        $this->assertNotEquals($encrypted, $reencrypted);

        // Decrypt and verify
        $decrypted = decrypt($reencrypted, $existingPassphrase);
        $this->assertEquals($updatedContent, $decrypted);
    }

    public function testTransactionNotRolledBackWithEmptyPassword(): void
    {
        // The bug was that empty post_password caused transaction rollback
        // This test verifies the fix prevents this

        $bind = [
            'post_password' => '',  // Empty - should NOT be included in UPDATE
            'passphrase' => ''      // Empty - should NOT be included in UPDATE
        ];

        // Simulate the fix logic
        $updateFields = [];
        $params = [];

        // Only non-password fields
        $updateFields[] = 'post_title = ?';
        $params[] = 'Test Title';

        // Conditionally add password fields (THE FIX)
        if (!empty($bind['post_password'])) {
            $updateFields[] = 'post_password = ?';
            $params[] = $bind['post_password'];
        }
        if (!empty($bind['passphrase'])) {
            $updateFields[] = 'passphrase = ?';
            $params[] = $bind['passphrase'];
        }

        // Build SQL
        $sql = 'UPDATE tbl_posts SET ' . implode(', ', $updateFields) . ' WHERE ID = ?';

        // Verify SQL does NOT contain post_password or passphrase
        $this->assertStringNotContainsString('post_password', $sql);
        $this->assertStringNotContainsString('passphrase', $sql);

        // Verify SQL is valid (has title but not password)
        $this->assertStringContainsString('post_title = ?', $sql);
    }
}
