<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);
/**
 * MigrationService Integration Test
 * 
 * Tests for MigrationService database operations with database.
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class MigrationServiceIntegrationTest extends TestCase
{
    private static $pdo;
    private static $authorId = 1;
    private static $createdPostIds = [];
    private static $createdTopicIds = [];
    private static $createdCommentIds = [];

    private $wxrSample = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:wp="http://wordpress.org/export/1.2/">
  <channel>
    <title>Test Blog</title>
    <link>http://example.com</link>
    <wp:wxr_version>1.2</wp:wxr_version>
    <item>
      <title>Test Import Post 1</title>
      <link>http://example.com/test-import-post-1/</link>
      <pubDate>Mon, 01 Jan 2024 12:00:00 +0000</pubDate>
      <dc:creator>admin</dc:creator>
      <content:encoded><![CDATA[<p>Test import content 1</p>]]></content:encoded>
      <wp:post_id>1001</wp:post_id>
      <wp:post_name>test-import-post-1</wp:post_name>
      <wp:status>publish</wp:status>
      <wp:post_type>post</wp:post_type>
      <wp:comment_status>open</wp:comment_status>
    </item>
  </channel>
</rss>';

    public static function setUpBeforeClass(): void
    {
        self::$pdo = new PDO(
            'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
            'blogwareuser',
            'userblogware',
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        // Check if required tables exist
        $requiredTables = ['tbl_posts', 'tbl_topics', 'tbl_post_topic', 'tbl_comments'];
        foreach ($requiredTables as $table) {
            $tables = self::$pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
            if (empty($tables)) {
                self::markTestSkipped("Table $table does not exist");
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        // Clean up created data
        if (self::$pdo) {
            // Delete comments
            if (!empty(self::$createdCommentIds)) {
                $ids = implode(',', array_map('intval', self::$createdCommentIds));
                self::$pdo->exec("DELETE FROM tbl_comments WHERE ID IN ($ids)");
            }

            // Delete post-topic associations
            if (!empty(self::$createdPostIds)) {
                $ids = implode(',', array_map('intval', self::$createdPostIds));
                self::$pdo->exec("DELETE FROM tbl_post_topic WHERE post_id IN ($ids)");
            }

            // Delete posts
            if (!empty(self::$createdPostIds)) {
                $ids = implode(',', array_map('intval', self::$createdPostIds));
                self::$pdo->exec("DELETE FROM tbl_posts WHERE ID IN ($ids)");
            }

            // Delete topics
            if (!empty(self::$createdTopicIds)) {
                $ids = implode(',', array_map('intval', self::$createdTopicIds));
                self::$pdo->exec("DELETE FROM tbl_topics WHERE ID IN ($ids)");
            }

            self::$pdo = null;
        }
    }

    public function testMigrationServiceStatsReset(): void
    {
        if (!class_exists('Sanitize')) {
            $this->markTestSkipped('Sanitize class not available');
        }

        $sanitizer = new Sanitize();
        $service = new MigrationService($sanitizer);

        // Check initial stats
        $stats = $service->getStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('posts_created', $stats);
        $this->assertArrayHasKey('categories_created', $stats);
        $this->assertArrayHasKey('posts_skipped', $stats);
        $this->assertArrayHasKey('comments_created', $stats);
    }

    public function testMigrationServiceInvalidSource(): void
    {
        if (!class_exists('Sanitize')) {
            $this->markTestSkipped('Sanitize class not available');
        }

        $sanitizer = new Sanitize();
        $service = new MigrationService($sanitizer);

        // Try preview with invalid source
        $result = $service->previewImport($this->wxrSample, 'invalid_source');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testMigrationServicePreviewImportWordPress(): void
    {
        if (!class_exists('Registry')) {
            $this->markTestSkipped('Registry class not available');
        }

        try {
            $dbc = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            Registry::set('dbc', $dbc);
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection not available: ' . $e->getMessage());
        }

        if (!class_exists('Sanitize')) {
            $this->markTestSkipped('Sanitize class not available');
        }

        $sanitizer = new Sanitize();
        $service = new MigrationService($sanitizer);

        // Preview WordPress import
        $result = $service->previewImport($this->wxrSample, 'wordpress');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('posts_count', $result);
        $this->assertArrayHasKey('site_info', $result);
    }

    public function testMigrationServicePreviewImportGhost(): void
    {
        if (!class_exists('Registry')) {
            $this->markTestSkipped('Registry class not available');
        }

        try {
            $dbc = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            Registry::set('dbc', $dbc);
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection not available: ' . $e->getMessage());
        }

        if (!class_exists('Sanitize')) {
            $this->markTestSkipped('Sanitize class not available');
        }

        $ghostJson = json_encode([
            'title' => 'Ghost Blog',
            'url' => 'http://ghost.example.com',
            'posts' => [
                [
                    'id' => '1',
                    'title' => 'Ghost Post',
                    'slug' => 'ghost-post',
                    'html' => '<p>Ghost content</p>',
                    'status' => 'published',
                    'published_at' => '2024-01-01T12:00:00.000Z'
                ]
            ]
        ]);

        $sanitizer = new Sanitize();
        $service = new MigrationService($sanitizer);

        $result = $service->previewImport($ghostJson, 'ghost');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('posts_count', $result);
        $this->assertGreaterThan(0, $result['posts_count']);
    }

    public function testMigrationServicePreviewImportBlogspot(): void
    {
        if (!class_exists('Registry')) {
            $this->markTestSkipped('Registry class not available');
        }

        try {
            $dbc = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            Registry::set('dbc', $dbc);
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection not available: ' . $e->getMessage());
        }

        if (!class_exists('Sanitize')) {
            $this->markTestSkipped('Sanitize class not available');
        }

        $blogspotXml = '<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom">
  <id>tag:blogger.com,1999:blog-123456</id>
  <title type="text">Blogger Blog</title>
  <link rel="alternate" href="http://blogger.example.com/"/>
  <entry>
    <id>post-1</id>
    <title type="text">Blogspot Post</title>
    <published>2024-01-01T12:00:00.000Z</published>
    <content type="html"><![CDATA[<p>Blogspot content</p>]]></content>
    <category term="kind#post" scheme="http://schemas.google.com/g/2005#kind"/>
    <link rel="alternate" href="http://blogger.example.com/post-1"/>
  </entry>
</feed>';

        $sanitizer = new Sanitize();
        $service = new MigrationService($sanitizer);

        $result = $service->previewImport($blogspotXml, 'blogspot');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('posts_count', $result);
    }

    public function testMigrationServiceSetAuthorId(): void
    {
        if (!class_exists('Sanitize')) {
            $this->markTestSkipped('Sanitize class not available');
        }

        if (!class_exists('Registry')) {
            $this->markTestSkipped('Registry class not available');
        }

        try {
            $dbc = new PDO(
                'mysql:host=localhost;dbname=blogware_test;charset=utf8mb4',
                'blogwareuser',
                'userblogware',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
            Registry::set('dbc', $dbc);
        } catch (PDOException $e) {
            $this->markTestSkipped('Database connection not available: ' . $e->getMessage());
        }

        $sanitizer = new Sanitize();
        $service = new MigrationService($sanitizer);

        // Set author ID
        $service->setAuthorId(5);

        // Verify service was created and can be used
        $stats = $service->getStats();
        $this->assertIsArray($stats);
    }
}