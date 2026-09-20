<?php

use PHPUnit\Framework\TestCase;

/**
 * PostViewModelTest
 *
 * Locks the exact escaped output and null-safe defaults of the shared
 * PostViewModel so the escaping boundary never drifts (plan §7.2).
 */
class PostViewModelTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../lib/utility/theme-escape.php';
        require_once __DIR__ . '/../../lib/core/Theme/ThemeViewModelInterface.php';
        require_once __DIR__ . '/../../lib/core/Theme/AbstractThemeViewModel.php';
        require_once __DIR__ . '/../../lib/core/Theme/PostViewModel.php';
        require_once __DIR__ . '/../../lib/core/Theme/ThemeViewModelFactory.php';
    }

    public function testEscapesTitleAndUrlExactly(): void
    {
        $vm = PostViewModel::fromRow([
            'ID'          => 5,
            'post_title'  => 'Hello <script>alert(1)</script> & "quoted"',
            'post_slug'   => 'hello',
            'user_fullname' => 'Jane & Doe',
        ], 'theme_escape_html');

        $this->assertSame(
            'Hello &lt;script&gt;alert(1)&lt;/script&gt; &amp; &quot;quoted&quot;',
            $vm->title()
        );
        $this->assertSame('Jane &amp; Doe', $vm->author());
        $this->assertSame('5', $vm->id());
    }

    public function testNullSafeDefaults(): void
    {
        $vm = PostViewModel::fromRow([], 'theme_escape_html');

        $this->assertNull($vm->title());
        $this->assertSame('', $vm->url());
        $this->assertNull($vm->author());
        $this->assertNull($vm->date());
        $this->assertNull($vm->comments());
        $this->assertArrayHasKey('title', $vm->toArray());
        $this->assertNull($vm->toArray()['title']);
    }

    public function testVisibilityDefaultsToPublic(): void
    {
        $vm = PostViewModel::fromRow([], 'theme_escape_html');
        $this->assertSame('public', $vm->visibility());
    }

    public function testFactoryUsesSingleEscapeBoundary(): void
    {
        $factory = new ThemeViewModelFactory();
        $vm = $factory->makePost([
            'ID'         => 1,
            'post_title' => 'A & B',
            'post_slug'  => 'a-b',
        ]);

        $this->assertSame('A &amp; B', $vm->title());
        $this->assertNull($vm->comments());
    }

    public function testMakePostsCollection(): void
    {
        $factory = new ThemeViewModelFactory();
        $posts = $factory->makePosts([
            ['ID' => 1, 'post_title' => 'One', 'post_slug' => 'one'],
            ['ID' => 2, 'post_title' => 'Two', 'post_slug' => 'two'],
        ]);

        $this->assertCount(2, $posts);
        $this->assertSame('One', $posts[0]->title());
        $this->assertSame('Two', $posts[1]->title());
    }

    public function testFromPreparedStoresAlreadySafeValuesVerbatim(): void
    {
        $vm = PostViewModel::fromPrepared([
            'id'            => '5',
            'title'         => 'Hello &lt;World&gt;',
            'url'           => '/post/5/hello-world',
            'content'       => '<p>Trusted <em>sanitized</em> HTML</p>',
            'media'         => 'hero.jpg',
            'media_caption' => 'The hero',
            'date'          => 'March 02, 2024',
            'author'        => 'admin',
            'comments'      => '7',
            'topics'        => '<a href="/category/news">News</a>',
        ]);

        $this->assertSame('Hello &lt;World&gt;', $vm->title());
        $this->assertSame('<p>Trusted <em>sanitized</em> HTML</p>', $vm->content());
        $this->assertSame('hero.jpg', $vm->media());
        $this->assertSame('<a href="/category/news">News</a>', $vm->topics());
        $this->assertSame(5, (int)$vm->id());
        $this->assertSame(7, (int)$vm->comments());
    }

    public function testFromPreparedCoercesMissingKeysToNull(): void
    {
        $vm = PostViewModel::fromPrepared(['title' => 'Only title']);

        $this->assertSame('Only title', $vm->title());
        $this->assertNull($vm->topics());
        $this->assertSame('', $vm->url());
    }
}
