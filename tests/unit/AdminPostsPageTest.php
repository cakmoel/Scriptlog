<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class AdminPostsPageTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../src/admin/posts.php';
        if (file_exists($path)) {
            $this->source = file_get_contents($path);
        }
    }

    public function testFileExists(): void
    {
        $this->assertNotNull($this->source, 'admin/posts.php file not found');
    }

    public function testPostDaoInstantiation(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('PostDao', $this->source);
    }

    public function testTopicDaoInstantiation(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('TopicDao', $this->source);
    }

    public function testMediaDaoInstantiation(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('MediaDao', $this->source);
    }

    public function testPostServiceInstantiation(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('PostService', $this->source);
    }

    public function testPostControllerInstantiationWithFourParams(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('new PostController($postService, $topicDao, $mediaDao, $postAppService)', $this->source);
    }

    public function testHasActionSwitch(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('switch ($action)', $this->source);
    }

    public function testHasNewPostAction(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('ActionConst::NEWPOST', $this->source);
    }

    public function testHasEditPostAction(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('ActionConst::EDITPOST', $this->source);
    }

    public function testHasDeletePostAction(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('ActionConst::DELETEPOST', $this->source);
    }

    public function testHasAccessControlCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('userAccessControl', $this->source);
    }

    public function testPostIdIntvalSanitization(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('intval($_GET[\'Id\'])', $this->source);
    }

    public function testActionHtmlEntitiesStripTags(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('admin/posts.php not found');
        }
        $this->assertStringContainsString('htmlentities(strip_tags(', $this->source);
    }
}
