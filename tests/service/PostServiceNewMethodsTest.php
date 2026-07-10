<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostService New Methods Test
 *
 * Tests for newly added methods: setPostContent with skipPurify,
 * processPostImage, processDefaultImage, processUploadedImage.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostServiceNewMethodsTest extends TestCase
{
    private $postService;
    private $postDaoMock;
    private $validatorMock;
    private $sanitizeMock;

    protected function setUp(): void
    {
        $this->postDaoMock = $this->createMock(\PostDao::class);
        $this->validatorMock = $this->createMock(\FormValidator::class);
        $this->sanitizeMock = $this->createMock(\Sanitize::class);

        $this->postService = new \PostService(
            $this->postDaoMock,
            $this->validatorMock,
            $this->sanitizeMock
        );
    }

    public function testSetPostContentWithoutSkipPurify(): void
    {
        if (!defined('HTMLPURIFIER_PREFIX')) {
            $this->markTestSkipped('HTMLPurifier not available in CLI context');
        }
        $content = '<p>Test content</p>';
        $this->postService->setPostContent($content, false);

        $ref = new ReflectionClass($this->postService);
        $prop = $ref->getProperty('content');
        $prop->setAccessible(true);
        $value = $prop->getValue($this->postService);

        $this->assertNotNull($value);
    }

    public function testSetPostContentWithSkipPurify(): void
    {
        $content = 'encrypted:abc123';
        $this->postService->setPostContent($content, true);

        $ref = new ReflectionClass($this->postService);
        $prop = $ref->getProperty('content');
        $prop->setAccessible(true);
        $value = $prop->getValue($this->postService);

        $this->assertEquals($content, $value);
    }

    public function testSetPostContentDefaultSkipPurify(): void
    {
        if (!defined('HTMLPURIFIER_PREFIX')) {
            $this->markTestSkipped('HTMLPurifier not available in CLI context');
        }
        $content = '<p>Default test</p>';
        $this->postService->setPostContent($content);

        $ref = new ReflectionClass($this->postService);
        $prop = $ref->getProperty('content');
        $prop->setAccessible(true);
        $value = $prop->getValue($this->postService);

        $this->assertNotNull($value);
    }

    public function testProcessPostImageWithEmptyFileLocation(): void
    {
        $_POST['image_id'] = 5;
        $filtered = ['post_title' => 'Test', 'image_id' => 5];

        $this->postService->processPostImage(
            '', '', '', 0, '', '', 800, 600, 'public', 'administrator', $filtered, false, null
        );

        $ref = new ReflectionClass($this->postService);
        $prop = $ref->getProperty('post_image');
        $prop->setAccessible(true);
        $value = $prop->getValue($this->postService);

        $this->assertEquals(5, $value);
        unset($_POST['image_id']);
    }

    public function testProcessPostImageWithImageIdInPost(): void
    {
        $_POST['image_id'] = 3;
        $filtered = ['post_title' => 'Test', 'image_id' => 3];

        $this->postService->processPostImage(
            '', '', '', 0, '', '', 800, 600, 'public', 'administrator', $filtered, false, null
        );

        $ref = new ReflectionClass($this->postService);
        $prop = $ref->getProperty('post_image');
        $prop->setAccessible(true);
        $value = $prop->getValue($this->postService);

        $this->assertEquals(3, $value);
        unset($_POST['image_id']);
    }

    public function testProcessPostImageIsUpdateNoImageId(): void
    {
        $filtered = ['post_title' => 'Test'];

        $this->postService->processPostImage(
            '', '', '', 0, '', '', 800, 600, 'public', 'administrator', $filtered, true, null
        );

        $ref = new ReflectionClass($this->postService);
        $prop = $ref->getProperty('post_image');
        $prop->setAccessible(true);
        $value = $prop->getValue($this->postService);

        $this->assertNull($value);
    }

    public function testSetPostContentWithNullContent(): void
    {
        $this->postService->setPostContent(null, true);

        $ref = new ReflectionClass($this->postService);
        $prop = $ref->getProperty('content');
        $prop->setAccessible(true);
        $value = $prop->getValue($this->postService);

        $this->assertNull($value);
    }

    public function testProcessPostImageWithUploadedFileNoOldMedia(): void
    {
        if (!class_exists('MediaDao')) {
            $this->markTestSkipped('MediaDao requires DB connection');
        }
        $this->markTestSkipped('MediaDao requires DB connection (Registry)');
    }
}
