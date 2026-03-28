<?php
/**
 * Import Utilities Test
 * 
 * Unit tests for WordPressImporter, GhostImporter, and BlogspotImporter
 * 
 * @category Tests
 * @version 1.0
 */

use PHPUnit\Framework\TestCase;

class ImportUtilitiesTest extends TestCase
{
    private $wxrSample;
    private $ghostSample;
    private $blogspotSample;

    protected function setUp(): void
    {
        $this->wxrSample = '<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:wfw="http://wellformedweb.org/CommentAPI/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:wp="http://wordpress.org/export/1.2/">
  <channel>
    <title>Test Blog</title>
    <link>http://example.com</link>
    <description>Test Description</description>
    <pubDate>Mon, 01 Jan 2024 12:00:00 +0000</pubDate>
    <language>en-US</language>
    <wp:wxr_version>1.2</wp:wxr_version>
    <wp:base_site_url>http://example.com</wp:base_site_url>
    <wp:base_blog_url>http://example.com</wp:base_blog_url>
    <item>
      <title>Test Post</title>
      <link>http://example.com/test-post/</link>
      <pubDate>Mon, 01 Jan 2024 12:00:00 +0000</pubDate>
      <dc:creator>admin</dc:creator>
      <guid isPermaLink="false">http://example.com/?p=1</guid>
      <description></description>
      <content:encoded><![CDATA[<p>Test content</p>]]></content:encoded>
      <excerpt:encoded><![CDATA[<p>Test excerpt</p>]]></excerpt:encoded>
      <wp:post_id>1</wp:post_id>
      <wp:post_date>2024-01-01 12:00:00</wp:post_date>
      <wp:post_date_gmt>2024-01-01 12:00:00</wp:post_date_gmt>
      <wp:comment_status>open</wp:comment_status>
      <wp:ping_status>open</wp:ping_status>
      <wp:post_name>test-post</wp:post_name>
      <wp:status>publish</wp:status>
      <wp:post_parent>0</wp:post_parent>
      <wp:menu_order>0</wp:menu_order>
      <wp:post_type>post</wp:post_type>
      <wp:post_password></wp:post_password>
      <category domain="category" nicename="test-category">Test Category</category>
      <category domain="post_tag" nicename="tag1">tag1</category>
    </item>
  </channel>
</rss>';

        $this->ghostSample = json_encode([
            'db' => [
                'meta' => [
                    ['key' => 'title', 'value' => 'Ghost Blog'],
                    ['key' => 'url', 'value' => 'http://ghost.example.com']
                ],
                'posts' => [
                    [
                        'id' => '1',
                        'title' => 'Ghost Post',
                        'slug' => 'ghost-post',
                        'html' => '<p>Ghost content</p>',
                        'plaintext' => 'Ghost content',
                        'status' => 'published',
                        'published_at' => '2024-01-01T12:00:00.000Z',
                        'created_at' => '2024-01-01T12:00:00.000Z',
                        'updated_at' => '2024-01-01T12:00:00.000Z',
                        'feature_image' => '',
                        'tags' => [
                            ['name' => 'Ghost Tag', 'slug' => 'ghost-tag', 'visibility' => 'public']
                        ],
                        'comment_id' => true
                    ]
                ],
                'tags' => [
                    ['name' => 'Ghost Tag', 'slug' => 'ghost-tag', 'visibility' => 'public', 'description' => '']
                ]
            ]
        ]);

        $this->blogspotSample = '<?xml version="1.0" encoding="UTF-8"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:app="http://purl.org/atom/app#" xmlns:thr="http://purl.org/syndication/thread/1.0">
  <id>tag:blogger.com,1999:blog-123456</id>
  <title type="text">Blogger Blog</title>
  <subtitle type="text">Blogger Description</subtitle>
  <link rel="alternate" href="http://blogger.example.com/"/>
  <entry>
    <id>post-1</id>
    <title type="text">Blogspot Post</title>
    <published>2024-01-01T12:00:00.000Z</published>
    <updated>2024-01-01T12:00:00.000Z</updated>
    <content type="html"><![CDATA[<p>Blogspot content</p>]]></content>
    <summary type="text">Blogspot excerpt</summary>
    <link rel="alternate" href="http://blogger.example.com/post-1"/>
    <author><name>Admin</name></author>
    <category term="kind#post" scheme="http://schemas.google.com/g/2005#kind"/>
    <category term="Blogspot Category" scheme="http://schemas.google.com/blogger/2008/kind#category"/>
    <thr:total>5</thr:total>
  </entry>
</feed>';
    }

    public function testWordPressImporterParsesValidWxr(): void
    {
        $importer = new WordPressImporter();
        $result = $importer->parse($this->wxrSample);
        
        $this->assertTrue($result);
    }

    public function testWordPressImporterGetSiteInfo(): void
    {
        $importer = new WordPressImporter();
        $importer->parse($this->wxrSample);
        
        $siteInfo = $importer->getSiteInfo();
        
        $this->assertIsArray($siteInfo);
        $this->assertArrayHasKey('title', $siteInfo);
        $this->assertArrayHasKey('site_url', $siteInfo);
        $this->assertArrayHasKey('wxr_version', $siteInfo);
    }

    public function testWordPressImporterGetCategories(): void
    {
        $importer = new WordPressImporter();
        $importer->parse($this->wxrSample);
        
        $categories = $importer->getCategories();
        
        $this->assertIsArray($categories);
    }

    public function testWordPressImporterGetTags(): void
    {
        $importer = new WordPressImporter();
        $importer->parse($this->wxrSample);
        
        $tags = $importer->getTags();
        
        $this->assertIsArray($tags);
    }

    public function testWordPressImporterGetPosts(): void
    {
        $importer = new WordPressImporter();
        $importer->parse($this->wxrSample);
        
        $posts = $importer->getPosts();
        
        $this->assertIsArray($posts);
        $this->assertNotEmpty($posts);
        
        $this->assertArrayHasKey('title', $posts[0]);
        $this->assertArrayHasKey('type', $posts[0]);
        $this->assertArrayHasKey('status', $posts[0]);
    }

    public function testWordPressImporterThrowsExceptionOnInvalidXml(): void
    {
        $this->expectException(ImportException::class);
        
        $importer = new WordPressImporter();
        $importer->parse('not valid xml');
    }

    public function testGhostImporterParsesValidJson(): void
    {
        $importer = new GhostImporter();
        $result = $importer->parse($this->ghostSample);
        
        $this->assertTrue($result);
    }

    public function testGhostImporterGetSiteInfo(): void
    {
        $importer = new GhostImporter();
        $importer->parse($this->ghostSample);
        
        $siteInfo = $importer->getSiteInfo();
        
        $this->assertIsArray($siteInfo);
        $this->assertArrayHasKey('title', $siteInfo);
        $this->assertArrayHasKey('url', $siteInfo);
        $this->assertArrayHasKey('schema', $siteInfo);
    }

    public function testGhostImporterGetSchema(): void
    {
        $importer = new GhostImporter();
        $importer->parse($this->ghostSample);
        
        $schema = $importer->getSchema();
        
        $this->assertIsString($schema);
    }

    public function testGhostImporterGetPosts(): void
    {
        // Use v3 format for simplicity
        $v3Json = json_encode([
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

        $importer = new GhostImporter();
        $importer->parse($v3Json);
        
        $posts = $importer->getPosts();
        
        $this->assertIsArray($posts);
        $this->assertNotEmpty($posts);
        
        $this->assertArrayHasKey('title', $posts[0]);
        $this->assertArrayHasKey('slug', $posts[0]);
        $this->assertArrayHasKey('status', $posts[0]);
    }

    public function testGhostImporterGetTags(): void
    {
        $importer = new GhostImporter();
        $importer->parse($this->ghostSample);
        
        $tags = $importer->getTags();
        
        $this->assertIsArray($tags);
    }

    public function testGhostImporterGetCategories(): void
    {
        $importer = new GhostImporter();
        $importer->parse($this->ghostSample);
        
        $categories = $importer->getCategories();
        
        $this->assertIsArray($categories);
    }

    public function testGhostImporterThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(ImportException::class);
        
        $importer = new GhostImporter();
        $importer->parse('not valid json');
    }

    public function testBlogspotImporterParsesValidXml(): void
    {
        $importer = new BlogspotImporter();
        $result = $importer->parse($this->blogspotSample);
        
        $this->assertTrue($result);
    }

    public function testBlogspotImporterGetSiteInfo(): void
    {
        $importer = new BlogspotImporter();
        $importer->parse($this->blogspotSample);
        
        $siteInfo = $importer->getSiteInfo();
        
        $this->assertIsArray($siteInfo);
        $this->assertArrayHasKey('title', $siteInfo);
        $this->assertArrayHasKey('feed_id', $siteInfo);
        $this->assertArrayHasKey('url', $siteInfo);
    }

    public function testBlogspotImporterGetPosts(): void
    {
        $importer = new BlogspotImporter();
        $importer->parse($this->blogspotSample);
        
        $posts = $importer->getPosts();
        
        $this->assertIsArray($posts);
        $this->assertNotEmpty($posts);
        
        $this->assertArrayHasKey('title', $posts[0]);
        $this->assertArrayHasKey('status', $posts[0]);
    }

    public function testBlogspotImporterGetPages(): void
    {
        $importer = new BlogspotImporter();
        $importer->parse($this->blogspotSample);
        
        $pages = $importer->getPages();
        
        $this->assertIsArray($pages);
    }

    public function testBlogspotImporterGetCategories(): void
    {
        $importer = new BlogspotImporter();
        $importer->parse($this->blogspotSample);
        
        $categories = $importer->getCategories();
        
        $this->assertIsArray($categories);
    }

    public function testBlogspotImporterThrowsExceptionOnInvalidXml(): void
    {
        $this->expectException(ImportException::class);
        
        $importer = new BlogspotImporter();
        $importer->parse('not valid xml');
    }

    public function testWordPressImporterEmptyContent(): void
    {
        $importer = new WordPressImporter();
        
        $this->expectException(ImportException::class);
        $importer->parse('');
    }

    public function testGhostImporterEmptyContent(): void
    {
        $importer = new GhostImporter();
        
        $this->expectException(ImportException::class);
        $importer->parse('');
    }

    public function testBlogspotImporterEmptyContent(): void
    {
        $importer = new BlogspotImporter();
        
        $this->expectException(ImportException::class);
        $importer->parse('');
    }

    public function testWordPressImporterReturnsArrayForPosts(): void
    {
        $importer = new WordPressImporter();
        $importer->parse($this->wxrSample);
        
        $posts = $importer->getPosts();
        
        $this->assertIsArray($posts);
        foreach ($posts as $post) {
            $this->assertArrayHasKey('title', $post);
            $this->assertArrayHasKey('slug', $post);
            $this->assertArrayHasKey('content', $post);
            $this->assertArrayHasKey('status', $post);
            $this->assertArrayHasKey('date', $post);
            $this->assertArrayHasKey('type', $post);
        }
    }

    public function testGhostImporterV3Format(): void
    {
        $v3Json = json_encode([
            'title' => 'V3 Ghost Blog',
            'url' => 'http://ghost-v3.example.com',
            'posts' => [
                [
                    'id' => '1',
                    'title' => 'V3 Post',
                    'slug' => 'v3-post',
                    'html' => '<p>V3 content</p>',
                    'status' => 'published',
                    'published_at' => '2024-01-01T12:00:00.000Z'
                ]
            ]
        ]);

        $importer = new GhostImporter();
        $result = $importer->parse($v3Json);
        
        $this->assertTrue($result);
        $this->assertEquals('v3', $importer->getSchema());
        
        $posts = $importer->getPosts();
        $this->assertNotEmpty($posts);
    }

    public function testBlogspotImporterReturnsArrayForPosts(): void
    {
        $importer = new BlogspotImporter();
        $importer->parse($this->blogspotSample);
        
        $posts = $importer->getPosts();
        
        $this->assertIsArray($posts);
        foreach ($posts as $post) {
            $this->assertArrayHasKey('title', $post);
            $this->assertArrayHasKey('slug', $post);
            $this->assertArrayHasKey('content', $post);
            $this->assertArrayHasKey('status', $post);
            $this->assertArrayHasKey('date', $post);
        }
    }
}