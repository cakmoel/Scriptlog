<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class TopicApiDtoTest extends TestCase
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
        $topic = [
            'ID' => 1,
            'topic_title' => 'PHP',
            'topic_slug' => 'php',
            'topic_status' => 'Y',
            'post_count' => 5,
        ];

        $result = \Scriptlog\Dto\Api\TopicApiDto::transform($topic, 'http://localhost');

        $this->assertIsArray($result);
        $this->assertEquals(1, $result['id']);
        $this->assertEquals('PHP', $result['title']);
        $this->assertEquals('php', $result['slug']);
        $this->assertEquals('Y', $result['status']);
        $this->assertEquals(5, $result['post_count']);
    }

    public function testTransformWithMissingPostCount(): void
    {
        $topic = [
            'ID' => 2,
            'topic_title' => 'JavaScript',
            'topic_slug' => 'javascript',
        ];

        $result = \Scriptlog\Dto\Api\TopicApiDto::transform($topic, 'http://localhost');

        $this->assertEquals(0, $result['post_count']);
    }

    public function testTransformCollection(): void
    {
        $topics = [
            ['ID' => 1, 'topic_title' => 'PHP', 'topic_slug' => 'php'],
            ['ID' => 2, 'topic_title' => 'JS', 'topic_slug' => 'js'],
        ];

        $results = \Scriptlog\Dto\Api\TopicApiDto::transformCollection($topics, 'http://localhost');

        $this->assertCount(2, $results);
        $this->assertEquals('PHP', $results[0]['title']);
        $this->assertEquals('JS', $results[1]['title']);
    }
}
