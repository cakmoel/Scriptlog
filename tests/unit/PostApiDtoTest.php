<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class PostApiDtoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->ensureMockDb();
    }

    private function ensureMockDb(): void
    {
        if (class_exists('\\Scriptlog\\Core\\Registry')) {
            $dbc = \Scriptlog\Core\Registry::get('dbc');
            if (!is_object($dbc) || !method_exists($dbc, 'select')) {
                $mock = new class() {
                    public function select() { return []; }
                    public function get() { return null; }
                    public function dbSelect() { return []; }
                    public function dbInsert($t, $p) { return true; }
                    public function dbUpdate($t, $p, $w) { return 1; }
                    public function dbDelete($t, $w, $l = null) { return 1; }
                    public function dbQuery($s, $a = []) {
                        $st = new class() {
                            public function fetch() { return null; }
                            public function fetchAll() { return []; }
                            public function fetchColumn() { return null; }
                            public function rowCount() { return 0; }
                            public function closeCursor() { return true; }
                        };
                        return $st;
                    }
                    public function dbLastInsertId() { return '1'; }
                    public function dbTransaction() { return true; }
                    public function dbCommit() { return true; }
                    public function dbRollBack() { return true; }
                    public function dbReplace($t, $p, $up) { return true; }
                };
                \Scriptlog\Core\Registry::set('dbc', $mock);
            }
        }
    }
    public function testTransformReturnsExpectedStructure(): void
    {
        $post = [
            'ID' => 1,
            'post_title' => 'Test Post',
            'post_slug' => 'test-post',
            'post_content' => '<p>Hello</p>',
            'post_summary' => 'A summary',
            'post_status' => 'publish',
            'post_visibility' => 'public',
            'post_tags' => 'php,testing',
            'comment_status' => 'open',
            'post_type' => 'blog',
            'post_author' => 1,
            'author_login' => 'admin',
            'author_name' => 'Admin',
            'post_date' => '2026-07-30 10:00:00',
            'post_modified' => '2026-07-30 12:00:00',
        ];

        $result = \Scriptlog\Dto\Api\PostApiDto::transform($post, 'http://localhost');

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test Post', $result['title']);
        $this->assertEquals('test-post', $result['slug']);
        $this->assertEquals('A summary', $result['excerpt']);
        $this->assertEquals('publish', $result['status']);
        $this->assertEquals(['php', 'testing'], $result['tags']);
        $this->assertEquals(1, $result['author']['id']);
        $this->assertEquals('admin', $result['author']['login']);
    }

    public function testTransformWithMinimalData(): void
    {
        $post = [
            'ID' => 2,
            'post_title' => 'Minimal',
            'post_slug' => 'minimal',
            'post_content' => '',
            'post_status' => 'draft',
            'post_visibility' => 'public',
            'post_type' => 'blog',
            'post_author' => 0,
        ];

        $result = \Scriptlog\Dto\Api\PostApiDto::transform($post, 'http://localhost');

        $this->assertEquals(2, $result['id']);
        $this->assertEquals('Minimal', $result['title']);
        $this->assertEquals([], $result['tags']);
        $this->assertEquals('draft', $result['status']);
    }

    public function testTransformCollection(): void
    {
        $posts = [
            [
                'ID' => 1,
                'post_title' => 'Post 1',
                'post_slug' => 'post-1',
                'post_content' => 'Content 1',
                'post_status' => 'publish',
                'post_visibility' => 'public',
                'post_type' => 'blog',
                'post_author' => 1,
            ],
            [
                'ID' => 2,
                'post_title' => 'Post 2',
                'post_slug' => 'post-2',
                'post_content' => 'Content 2',
                'post_status' => 'draft',
                'post_visibility' => 'public',
                'post_type' => 'blog',
                'post_author' => 1,
            ],
        ];

        $results = \Scriptlog\Dto\Api\PostApiDto::transformCollection($posts, 'http://localhost');

        $this->assertCount(2, $results);
        $this->assertEquals('Post 1', $results[0]['title']);
        $this->assertEquals('Post 2', $results[1]['title']);
    }

    public function testExcerptFromContentWhenNoSummary(): void
    {
        $post = [
            'ID' => 3,
            'post_title' => 'Long Content',
            'post_slug' => 'long-content',
            'post_content' => str_repeat('word ', 100),
            'post_status' => 'publish',
            'post_visibility' => 'public',
            'post_type' => 'blog',
            'post_author' => 1,
        ];

        $result = \Scriptlog\Dto\Api\PostApiDto::transform($post, 'http://localhost');

        $this->assertStringEndsWith('...', $result['excerpt']);
        $this->assertStringContainsString('word', $result['excerpt']);
    }
}
