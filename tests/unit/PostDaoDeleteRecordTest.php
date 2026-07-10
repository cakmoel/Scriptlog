<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostDao DeleteRecord Test
 *
 * Tests for deleteRecord third parameter addition in PostDao::updatePost().
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostDaoDeleteRecordTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../src/lib/dao/PostDao.php';
        if (file_exists($path)) {
            $this->source = file_get_contents($path);
        }
    }

    public function testDeleteRecordCalledInUpdatePost(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostDao.php not found');
        }
        $this->assertStringContainsString('deleteRecord', $this->source);
    }

    public function testDeleteRecordWithNullThirdParam(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostDao.php not found');
        }
        $this->assertStringContainsString('deleteRecord("tbl_post_topic",', $this->source);
    }

    public function testPostDaoUpdatePostMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        $this->assertTrue(method_exists(PostDao::class, 'updatePost'));
    }

    public function testPostDaoDeletePostMethodExists(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        $this->assertTrue(method_exists(PostDao::class, 'deletePost'));
    }

    public function testPostDaoFindPostMethodHasFourParameters(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        $reflection = new ReflectionMethod(PostDao::class, 'findPost');
        $this->assertEquals(4, $reflection->getNumberOfParameters());
    }

    public function testPostDaoFindPostsMethodHasThreeParameters(): void
    {
        if (!class_exists('PostDao')) {
            $this->markTestSkipped('PostDao class not found');
        }
        $reflection = new ReflectionMethod(PostDao::class, 'findPosts');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
    }
}
