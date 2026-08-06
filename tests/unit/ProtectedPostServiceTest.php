<?php

use PHPUnit\Framework\TestCase;

/**
 * ProtectedPostService Tests
 *
 * Covers the new Scriptlog\Service\ProtectedPostService which owns the
 * protected-vs-public render decision and the sanitization pipeline that
 * previously lived inline in the single.php template.
 */
class ProtectedPostServiceTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/service/ProtectedPostService.php';
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('Scriptlog\Service\ProtectedPostService'));
    }

    public function testPublicPostReturnsContent(): void
    {
        $service = new \Scriptlog\Service\ProtectedPostService();
        $post = ['ID' => 1, 'post_visibility' => 'public', 'post_content' => '<p>Hello</p>'];

        $result = $service->resolve($post);

        $this->assertFalse($result['is_protected']);
        $this->assertFalse($result['is_unlocked']);
        $this->assertFalse($result['show_password_form']);
        $this->assertSame('<p>Hello</p>', $result['content']);
        $this->assertSame(1, $result['id']);
    }

    public function testPublicPostWithoutContentReturnsNotFound(): void
    {
        $service = new \Scriptlog\Service\ProtectedPostService();
        $post = ['ID' => 1, 'post_visibility' => 'public'];

        $result = $service->resolve($post);

        $this->assertSame('Content not found', $result['content']);
    }

    public function testProtectedPostNotUnlockedShowsPasswordForm(): void
    {
        $service = new \Scriptlog\Service\ProtectedPostService();
        $post = ['ID' => 2, 'post_visibility' => 'protected', 'post_content' => 'secret'];

        $result = $service->resolve($post);

        $this->assertTrue($result['is_protected']);
        $this->assertFalse($result['is_unlocked']);
        $this->assertTrue($result['show_password_form']);
        $this->assertSame('', $result['content']);
    }

    public function testProtectedPostUnlockedDecryptsContent(): void
    {
        $decrypt = function (int $id, string $password): array {
            return ['post_content' => '<p>decrypted ' . $password . '</p>'];
        };

        $service = new \Scriptlog\Service\ProtectedPostService($decrypt);
        $post = ['ID' => 3, 'post_visibility' => 'protected', 'post_content' => 'ignored'];

        $result = $service->resolve($post, [3 => 'secret']);

        $this->assertTrue($result['is_protected']);
        $this->assertTrue($result['is_unlocked']);
        $this->assertFalse($result['show_password_form']);
        $this->assertStringContainsString('decrypted secret', $result['content']);
    }

    public function testProtectedPostUnlockedWhenDecryptReturnsNullProducesEmptyContent(): void
    {
        $service = new \Scriptlog\Service\ProtectedPostService(function () {
            return null;
        });
        $post = ['ID' => 4, 'post_visibility' => 'protected', 'post_content' => 'secret'];

        $result = $service->resolve($post, [4 => 'secret']);

        $this->assertTrue($result['is_unlocked']);
        $this->assertSame('', $result['content']);
    }

    public function testProtectedPostDecryptWithoutContentKeyReturnsEmpty(): void
    {
        $decrypt = function (): array {
            return [];
        };

        $service = new \Scriptlog\Service\ProtectedPostService($decrypt);
        $post = ['ID' => 5, 'post_visibility' => 'protected'];

        $result = $service->resolve($post, [5 => 'secret']);

        $this->assertSame('', $result['content']);
    }

    public function testSanitizeContentStripsStyleAttributes(): void
    {
        $service = new \Scriptlog\Service\ProtectedPostService();
        $content = '<p style="color:red">Hello</p>';

        $cleaned = $service->sanitizeContent($content);

        $this->assertStringNotContainsString('style=', $cleaned);
        $this->assertStringContainsString('Hello', $cleaned);
    }

    public function testSanitizeContentDecodesHtmlEntities(): void
    {
        $service = new \Scriptlog\Service\ProtectedPostService();
        $content = '&lt;strong&gt;bold&lt;/strong&gt;';

        $cleaned = $service->sanitizeContent($content);

        $this->assertStringContainsString('<strong>bold</strong>', $cleaned);
    }

    public function testSanitizeContentRemovesEventHandlersWhenHtmLawedAvailable(): void
    {
        $service = new \Scriptlog\Service\ProtectedPostService();
        $content = '<div onclick="alert(1)" style="x:y">content</div>';

        $cleaned = $service->sanitizeContent($content);

        $this->assertStringNotContainsString('style=', $cleaned);
        $this->assertStringContainsString('content', $cleaned);
        if (function_exists('htmLawed')) {
            $this->assertStringNotContainsString('onclick', $cleaned);
        }
    }
}
