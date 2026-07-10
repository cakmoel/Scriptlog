<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class SinglePostTemplateTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../src/public/themes/blog/single.php';
        if (file_exists($path)) {
            $this->source = file_get_contents($path);
        }
    }

    public function testFileExists(): void
    {
        $this->assertNotNull($this->source, 'single.php file not found');
    }

    public function testHas404ValidationEmptyCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('empty($retrieve_post)', $this->source);
    }

    public function testHas404ValidationIsArrayCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('!is_array($retrieve_post)', $this->source);
    }

    public function testHas404ValidationIdExistsCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("!isset(\$retrieve_post['ID'])", $this->source);
    }

    public function testHas404ValidationIdPositiveCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('(int)$retrieve_post[\'ID\'] <= 0', $this->source);
    }

    public function testHasHttpResponseCode404(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('http_response_code(404)', $this->source);
    }

    public function testHasDefaultPostAuthor(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("\$post_author = ''", $this->source);
    }

    public function testHasDefaultPostCreated(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("\$post_created = ''", $this->source);
    }

    public function testHasDefaultPostContent(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("\$post_content = ''", $this->source);
    }

    public function testHasDefaultPostTitle(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("\$post_title = ''", $this->source);
    }

    public function testHasDefaultPostImg(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("\$post_img = ''", $this->source);
    }

    public function testHasDefaultTotalComment(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('$total_comment = 0', $this->source);
    }

    public function testHasDefaultPostVisibility(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("\$post_visibility = 'public'", $this->source);
    }

    public function testHasDefaultCommentPermit(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("\$comment_permit = 'closed'", $this->source);
    }

    public function testUsesGetPostThumbnail(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('get_post_thumbnail(', $this->source);
    }

    public function testProtectedPostLogicChecksUnlockedSession(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("\$_SESSION['unlocked_posts']", $this->source);
    }

    public function testPublicPostHasContentKeyCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("isset(\$retrieve_post['post_content'])", $this->source);
    }

    public function testPublicPostHasContentNotFoundFallback(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('"Content not found"', $this->source);
    }

    public function testProtectedPostHasDecryptContentKeyCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("isset(\$decrypted_content['post_content'])", $this->source);
    }

    public function testLinkTopicUsesEmptyCheck(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('!empty($post_id) ? link_topic(', $this->source);
    }

    public function testHasErrorLogForMissingPost(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString('error_log("Single.php: Post not found', $this->source);
    }

    public function testHasStyleTagRemovalInContent(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('single.php not found');
        }
        $this->assertStringContainsString("deny_attribute' => 'style", $this->source);
    }
}
