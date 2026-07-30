<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class CommentApiDtoTest extends TestCase
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
        $comment = [
            'ID' => 1,
            'comment_post_id' => 10,
            'comment_parent_id' => 0,
            'comment_author_name' => 'John',
            'comment_author_email' => 'john@example.com',
            'comment_content' => 'Great post!',
            'comment_status' => 'approved',
            'comment_date' => '2026-07-30 10:00:00',
        ];

        $result = \Scriptlog\Dto\Api\CommentApiDto::transform($comment, 'http://localhost');

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals(10, $result['post_id']);
        $this->assertEquals(0, $result['parent_id']);
        $this->assertEquals('John', $result['author']['name']);
        $this->assertEquals('john@example.com', $result['author']['email']);
        $this->assertEquals('Great post!', $result['content']);
        $this->assertEquals('approved', $result['status']);
    }

    public function testTransformWithPostInfo(): void
    {
        $comment = [
            'ID' => 2,
            'comment_post_id' => 5,
            'comment_author_name' => 'Jane',
            'comment_content' => 'Nice article',
            'comment_status' => 'pending',
            'comment_date' => '2026-07-30 11:00:00',
            'post_title' => 'My Post',
            'post_slug' => 'my-post',
        ];

        $result = \Scriptlog\Dto\Api\CommentApiDto::transform($comment, 'http://localhost');

        $this->assertArrayHasKey('post', $result);
        $this->assertEquals('My Post', $result['post']['title']);
        $this->assertEquals('my-post', $result['post']['slug']);
    }

    public function testTransformWithoutPostInfo(): void
    {
        $comment = [
            'ID' => 3,
            'comment_post_id' => 5,
            'comment_author_name' => 'Bob',
            'comment_content' => 'Okay',
            'comment_status' => 'spam',
            'comment_date' => '2026-07-30 12:00:00',
        ];

        $result = \Scriptlog\Dto\Api\CommentApiDto::transform($comment, 'http://localhost');

        $this->assertArrayNotHasKey('post', $result);
    }

    public function testTransformWithMissingParentId(): void
    {
        $comment = [
            'ID' => 4,
            'comment_post_id' => 5,
            'comment_author_name' => 'Alice',
            'comment_content' => 'Reply',
            'comment_status' => 'approved',
            'comment_date' => '2026-07-30 13:00:00',
        ];

        $result = \Scriptlog\Dto\Api\CommentApiDto::transform($comment, 'http://localhost');

        $this->assertEquals(0, $result['parent_id']);
    }

    public function testTransformCollection(): void
    {
        $comments = [
            [
                'ID' => 1,
                'comment_post_id' => 5,
                'comment_author_name' => 'A',
                'comment_content' => 'C1',
                'comment_status' => 'approved',
                'comment_date' => '2026-07-30 10:00:00',
            ],
            [
                'ID' => 2,
                'comment_post_id' => 5,
                'comment_author_name' => 'B',
                'comment_content' => 'C2',
                'comment_status' => 'pending',
                'comment_date' => '2026-07-30 11:00:00',
            ],
        ];

        $results = \Scriptlog\Dto\Api\CommentApiDto::transformCollection($comments, 'http://localhost');

        $this->assertCount(2, $results);
        $this->assertEquals('C1', $results[0]['content']);
        $this->assertEquals('C2', $results[1]['content']);
    }
}
