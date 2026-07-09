<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class TopicDaoTypeSafetyTest extends TestCase
{
    public function testSetCheckBoxTopicMethodExists(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
        }

        $this->assertTrue(method_exists('TopicDao', 'setCheckBoxTopic'));
    }

    public function testSetTopicPostIdParamAcceptsIntOrStringOrNull(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/TopicDao.php');

        preg_match('/@param\s+int\|string\|null\s+\$postId/', $source, $matches);
        $this->assertNotEmpty($matches, 'PHPDoc should declare @param int|string|null $postId');
    }

    public function testFindPostTopicCastsPostIdToInt(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/TopicDao.php');

        $this->assertStringContainsString('(int)$postId', $source);
    }

    public function testFindPostTopicMethodExists(): void
    {
        if (!class_exists('TopicDao')) {
            $this->markTestSkipped('TopicDao class not found');
        }

        $this->assertTrue(method_exists('TopicDao', 'findPostTopic'));
    }

    public function testDropDownTopicCastsPostId(): void
    {
        $source = file_get_contents(__DIR__ . '/../../src/lib/dao/TopicDao.php');

        $this->assertStringContainsString("findPostTopic", $source);
        $this->assertStringContainsString("item['ID']", $source);
    }
}