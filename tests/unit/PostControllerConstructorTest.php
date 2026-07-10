<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostController Constructor Test
 *
 * Tests for the refactored PostController constructor with 3 params.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostControllerConstructorTest extends TestCase
{
    public function testPostControllerConstructorAcceptsThreeArguments(): void
    {
        if (!class_exists('PostController')) {
            $this->markTestSkipped('PostController class not found');
        }
        $reflection = new ReflectionMethod(PostController::class, '__construct');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
    }

    public function testPostControllerConstructorParameterTypes(): void
    {
        if (!class_exists('PostController')) {
            $this->markTestSkipped('PostController class not found');
        }
        $reflection = new ReflectionMethod(PostController::class, '__construct');
        $params = $reflection->getParameters();
        $this->assertCount(3, $params);
        $this->assertEquals('postService', $params[0]->getName());
        $this->assertEquals('topicDao', $params[1]->getName());
        $this->assertEquals('mediaDao', $params[2]->getName());
    }

    public function testPostControllerInsertMethodExists(): void
    {
        if (!class_exists('PostController')) {
            $this->markTestSkipped('PostController class not found');
        }
        $this->assertTrue(method_exists(PostController::class, 'insert'));
    }

    public function testPostControllerUpdateMethodExists(): void
    {
        if (!class_exists('PostController')) {
            $this->markTestSkipped('PostController class not found');
        }
        $this->assertTrue(method_exists(PostController::class, 'update'));
    }

    public function testPostControllerCheckPostPayloadHasPasswordField(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/controller/PostController.php');
        if (!$source) {
            $this->markTestSkipped('PostController.php not found');
        }
        $this->assertStringContainsString("'post_password'", $source);
    }

    public function testPostControllerCheckPostPayloadHasLocaleField(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/controller/PostController.php');
        if (!$source) {
            $this->markTestSkipped('PostController.php not found');
        }
        $this->assertStringContainsString("'post_locale'", $source);
    }

    public function testPostControllerCheckPostUpdatePayloadHasTags(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/controller/PostController.php');
        if (!$source) {
            $this->markTestSkipped('PostController.php not found');
        }
        $this->assertStringContainsString("'post_tags'", $source);
    }

    public function testPostControllerSetProtectedPostContentPassesTrueToSetPostContent(): void
    {
        $source = @file_get_contents(__DIR__ . '/../../src/lib/controller/PostController.php');
        if (!$source) {
            $this->markTestSkipped('PostController.php not found');
        }
        $this->assertStringContainsString('setPostContent($protected[\'post_content\'], true)', $source);
    }

    public function testPostControllerProcessPostUpdateMethodExists(): void
    {
        if (!class_exists('PostController')) {
            $this->markTestSkipped('PostController class not found');
        }
        $this->assertTrue(method_exists(PostController::class, 'processPostUpdate'));
    }
}
