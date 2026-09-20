<?php

use PHPUnit\Framework\TestCase;

class ProtectedPostServiceTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../lib/service/ProtectedPostService.php';
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('ProtectedPostService'));
    }

    public function testConstructorAcceptsNoArguments(): void
    {
        $service = new ProtectedPostService();
        $this->assertInstanceOf(ProtectedPostService::class, $service);
    }

    public function testPublicPostResolvesSanitizedContent(): void
    {
        $service = new ProtectedPostService();

        $result = $service->resolve([
            'ID' => 7,
            'post_visibility' => 'public',
            'post_content' => 'Hello &amp; goodbye <strong onclick="evil()">world</strong>'
        ]);

        $this->assertSame(7, $result['id']);
        $this->assertFalse($result['is_protected']);
        $this->assertFalse($result['is_unlocked']);
        $this->assertFalse($result['show_password_form']);
        $this->assertStringContainsString('Hello &amp; goodbye', $result['content']);
        $this->assertStringContainsString('<strong>world</strong>', $result['content']);
        $this->assertStringNotContainsString('onclick', $result['content']);
    }

    public function testPublicPostWithoutContentFallsBackToNotice(): void
    {
        $service = new ProtectedPostService();

        $result = $service->resolve([
            'ID' => 8,
            'post_visibility' => 'public'
        ]);

        $this->assertSame('Content not found', $result['content']);
    }

    public function testProtectedPostRequiresSessionKeyToRender(): void
    {
        $service = new ProtectedPostService();

        $result = $service->resolve([
            'ID' => 9,
            'post_visibility' => 'protected',
            'post_content' => 'secret'
        ], []);

        $this->assertTrue($result['is_protected']);
        $this->assertFalse($result['is_unlocked']);
        $this->assertTrue($result['show_password_form']);
        $this->assertSame('', $result['content']);
    }

    public function testProtectedPostUnlockedRendersDecryptedContent(): void
    {
        $decrypt = function ($id, $password) {
            return ['post_content' => 'Unlocked <p style="color:red">secret</p> <i onclick="x()">text</i>'];
        };

        $service = new ProtectedPostService($decrypt);

        $result = $service->resolve([
            'ID' => 10,
            'post_visibility' => 'protected',
            'post_content' => 'encrypted blob'
        ], [10 => 'correct-password']);

        $this->assertTrue($result['is_protected']);
        $this->assertTrue($result['is_unlocked']);
        $this->assertFalse($result['show_password_form']);
        $this->assertStringContainsString('secret', $result['content']);
        $this->assertStringNotContainsString('style=', $result['content']);
        $this->assertStringNotContainsString('onclick', $result['content']);
    }

    public function testDeniedOrInvalidIdNeverUnlocks(): void
    {
        $decrypt = function () {
            return ['post_content' => 'should never render'];
        };

        $service = new ProtectedPostService($decrypt);

        $result = $service->resolve([
            'ID' => 0,
            'post_visibility' => 'protected',
            'post_content' => 'encrypted blob'
        ], [999 => 'password']);

        $this->assertFalse($result['is_unlocked']);
        $this->assertTrue($result['show_password_form']);
        $this->assertSame('', $result['content']);
    }

    public function testSanitizeContentStripsStyleAndEvents(): void
    {
        $service = new ProtectedPostService();

        $html = '<p style="font-size:10px" onmouseover="steal()">Body</p>'
            . '<a href="javascript:void(0)" onclick="go()">Link</a>';

        $cleaned = $service->sanitizeContent($html);

        $this->assertStringContainsString('Body', $cleaned);
        $this->assertStringNotContainsString('style', $cleaned);
        $this->assertStringNotContainsString('onmouseover', $cleaned);
        $this->assertStringNotContainsString('onclick', $cleaned);
    }
}
