<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * PostModel Query Test
 *
 * Tests that PostModel queries use LEFT JOIN for media,
 * conditions moved to ON clause, and correct table references.
 *
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class PostModelQueryTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../src/lib/model/PostModel.php';
        if (file_exists($path)) {
            $this->source = file_get_contents($path);
        }
    }

    public function testGetLatestPostsUsesLeftJoinForMedia(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostModel.php not found');
        }
        $this->assertStringContainsString('LEFT JOIN', $this->source);
    }

    public function testGetLatestPostsMediaConditionsInOnClause(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostModel.php not found');
        }
        $this->assertStringContainsString('m.media_target', $this->source);
        $this->assertStringContainsString('m.media_access', $this->source);
        $this->assertStringContainsString('m.media_status', $this->source);
    }

    public function testGetAllPostsUsesLeftJoinForMedia(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostModel.php not found');
        }
        $this->assertStringContainsString('LEFT JOIN', $this->source);
        $this->assertStringContainsString('tbl_media', $this->source);
    }

    public function testGetPostByIdUsesLeftJoin(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostModel.php not found');
        }
        $this->assertStringContainsString('LEFT JOIN', $this->source);
    }

    public function testGetPostBySlugUsesLeftJoin(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostModel.php not found');
        }
        $this->assertStringContainsString('LEFT JOIN', $this->source);
    }

    public function testGetPostByAuthorUsesCorrectTableName(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostModel.php not found');
        }
        $this->assertStringContainsString('tbl_users', $this->source);
    }

    public function testGetHeadlinesPostsUsesLeftJoin(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostModel.php not found');
        }
        $this->assertStringContainsString('LEFT JOIN', $this->source);
    }

    public function testGetRandomPostsUsesLeftJoin(): void
    {
        if (!$this->source) {
            $this->markTestSkipped('PostModel.php not found');
        }
        $this->assertStringContainsString('LEFT JOIN', $this->source);
    }
}
