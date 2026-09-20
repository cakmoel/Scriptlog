<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * TopicDao API Methods Test
 *
 * Tests the new API-focused methods in TopicDao.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class TopicDaoApiMethodsTest extends TestCase
{
    public function testFindActiveTopicsPaginatedExists(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $this->assertTrue((new ReflectionClass('TopicDao'))->hasMethod('findActiveTopicsPaginated'));
    }

    public function testFindActiveTopicsPaginatedParameters(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $method = (new ReflectionClass('TopicDao'))->getMethod('findActiveTopicsPaginated');
        $params = $method->getParameters();
        $this->assertGreaterThanOrEqual(2, count($params));
        $this->assertEquals('limit', $params[0]->getName());
        $this->assertEquals('offset', $params[1]->getName());
    }

    public function testCountActiveTopicsExists(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $this->assertTrue((new ReflectionClass('TopicDao'))->hasMethod('countActiveTopics'));
    }

    public function testFindTopicWithPostCountExists(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $this->assertTrue((new ReflectionClass('TopicDao'))->hasMethod('findTopicWithPostCount'));
    }

    public function testFindPostsByTopicPaginatedExists(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $this->assertTrue((new ReflectionClass('TopicDao'))->hasMethod('findPostsByTopicPaginated'));
    }

    public function testCountPostsByTopicExists(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $this->assertTrue((new ReflectionClass('TopicDao'))->hasMethod('countPostsByTopic'));
    }

    public function testFindActiveTopicsPaginatedHasStatusFilter(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $source = file_get_contents(__DIR__ . '/../../lib/dao/TopicDao.php');
        $this->assertStringContainsString("topic_status = 'Y'", $source);
        $this->assertStringContainsString('LIMIT ? OFFSET ?', $source);
    }

    public function testFindActiveTopicsPaginatedHasSortWhitelist(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $source = file_get_contents(__DIR__ . '/../../lib/dao/TopicDao.php');
        $this->assertStringContainsString("['ID', 'topic_title', 'topic_slug']", $source);
        $this->assertStringContainsString('in_array($sortBy, $allowedColumns)', $source);
    }

    public function testCountActiveTopicsUsesCountStar(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $source = file_get_contents(__DIR__ . '/../../lib/dao/TopicDao.php');
        $this->assertStringContainsString('COUNT(*)', $source);
    }

    public function testFindPostsByTopicPaginatedHasVisibilityFilter(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
            return;
        }
        $source = file_get_contents(__DIR__ . '/../../lib/dao/TopicDao.php');
        $this->assertStringContainsString("p.post_status = 'publish'", $source);
        $this->assertStringContainsString("p.post_visibility = 'public'", $source);
    }
}
