<?php

use PHPUnit\Framework\TestCase;
use Scriptlog\Core\Theme\ArchiveViewModel;
use Scriptlog\Core\Theme\MenuViewModel;
use Scriptlog\Core\Theme\PageViewModel;
use Scriptlog\Core\Theme\PostViewModel;
use Scriptlog\Core\Theme\SidebarViewModel;
use Scriptlog\Core\Theme\ThemeHelper;
use Scriptlog\Core\Theme\ThemeViewModelFactory;

/**
 * Theme View Model Tests
 *
 * Covers the new Scriptlog\Core\Theme value objects, the factory and the
 * ThemeHelper facade introduced for the theme refactoring.
 */
class ThemeViewModelTest extends TestCase
{
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/lib/core/Theme/ThemeHelper.php';
    }

    private function escape(): callable
    {
        return function (string $value): string {
            return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        };
    }

    public function testPostViewModelFromRowEscapesValues(): void
    {
        $row = [
            'ID' => 1,
            'post_title' => '<b>Title</b>',
            'post_slug' => 'title',
            'post_content' => '<p>Hi</p>',
            'post_summary' => '',
            'url' => '/post/1/title',
        ];

        $vm = PostViewModel::fromRow($row, $this->escape());

        $this->assertSame('1', $vm->id());
        $this->assertSame('&lt;b&gt;Title&lt;/b&gt;', $vm->title());
        $this->assertSame('/post/1/title', $vm->url());
        $this->assertSame('title', $vm->slug());
        $this->assertNull($vm->excerpt());
    }

    public function testPostViewModelDefaultsVisibilityToPublic(): void
    {
        $vm = PostViewModel::fromRow(['ID' => 2, 'post_title' => 'T'], $this->escape());
        $this->assertSame('public', $vm->visibility());
        $this->assertSame('blog', $vm->type());
    }

    public function testPostViewModelFromPreparedStoresValuesVerbatim(): void
    {
        $prepared = ['id' => '1', 'title' => '&amp; Title', 'content' => '<p>safe</p>', 'url' => '/x'];
        $vm = PostViewModel::fromPrepared($prepared);

        $this->assertSame('&amp; Title', $vm->title());
        $this->assertSame('<p>safe</p>', $vm->content());
        $this->assertSame(['id' => '1', 'title' => '&amp; Title', 'content' => '<p>safe</p>', 'url' => '/x'], $vm->toArray());
    }

    public function testPageViewModelFromRowEscapesValues(): void
    {
        $row = [
            'ID' => 7,
            'post_title' => '<script>alert(1)</script>',
            'post_slug' => 'about-us',
            'post_content' => '<p>About</p>',
            'url' => '/page/about-us',
        ];

        $vm = PageViewModel::fromRow($row, $this->escape());

        $this->assertSame('7', $vm->id());
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', $vm->title());
        $this->assertSame('/page/about-us', $vm->url());
        $this->assertSame('&lt;p&gt;About&lt;/p&gt;', $vm->content());
    }

    public function testArchiveViewModelFromRowCombinesRawFields(): void
    {
        $row = ['year' => '2025', 'month' => '03', 'count' => '4', 'url' => '/archive/03/2025'];
        $vm = ArchiveViewModel::fromRow($row, $this->escape());

        $this->assertSame('2025', $vm->year());
        $this->assertSame('03', $vm->month());
        $this->assertSame('4', $vm->count());
        $this->assertSame('/archive/03/2025', $vm->url());
    }

    public function testMenuViewModelBuildsTreeWithChildren(): void
    {
        $child = MenuViewModel::fromRow(['ID' => 2, 'menu_label' => 'Child', 'menu_link' => '/child'], $this->escape());
        $parent = MenuViewModel::fromRow(
            ['ID' => 1, 'menu_label' => 'Parent', 'menu_link' => '/parent'],
            $this->escape()
        );

        $parent->setChildren([$child]);

        $this->assertTrue($parent->hasChildren());
        $this->assertCount(1, $parent->children());
        $this->assertSame('Child', $parent->children()[0]->label());
    }

    public function testMenuViewModelDefaultsUrlToHash(): void
    {
        $vm = MenuViewModel::fromPrepared(['id' => '1', 'label' => 'No link']);
        $this->assertSame('#', $vm->url());
    }

    public function testSidebarViewModelFromRowBuildsNestedAggregates(): void
    {
        $row = [
            'latest_posts' => [
                ['ID' => 1, 'post_title' => 'Post <b>One</b>', 'post_slug' => 'post-one'],
            ],
            'categories' => [
                ['topic_title' => 'PHP', 'url' => '/category/php', 'count' => '5'],
            ],
            'archives' => [
                ['label' => 'March 2025', 'url' => '/archive/03/2025', 'count' => '3'],
            ],
            'tags' => [
                ['label' => 'cicero', 'url' => '/tag/cicero'],
            ],
            'search_action' => '/search',
        ];

        $vm = SidebarViewModel::fromRow($row, $this->escape());

        $posts = $vm->latestPosts();
        $this->assertCount(1, $posts);
        $this->assertInstanceOf(PostViewModel::class, $posts[0]);
        $this->assertSame('Post &lt;b&gt;One&lt;/b&gt;', $posts[0]->title());

        $categories = $vm->categories();
        $this->assertSame('PHP', $categories[0]['title']);
        $this->assertSame('5', $categories[0]['count']);

        $archives = $vm->archives();
        $this->assertSame('March 2025', $archives[0]['label']);

        $tags = $vm->tags();
        $this->assertSame('cicero', $tags[0]['label']);

        $this->assertSame('/search', $vm->searchAction());
    }

    public function testFactoryCreatesTypedViewModels(): void
    {
        $factory = new ThemeViewModelFactory($this->escape());

        $this->assertInstanceOf(PostViewModel::class, $factory->makePost(['ID' => 1, 'post_title' => 'T']));
        $this->assertInstanceOf(PostViewModel::class, $factory->makePostFromPrepared(['title' => 'T']));
        $this->assertInstanceOf(PageViewModel::class, $factory->makePage(['ID' => 1, 'post_title' => 'P']));
        $this->assertInstanceOf(PageViewModel::class, $factory->makePageFromPrepared(['title' => 'P']));
        $this->assertInstanceOf(ArchiveViewModel::class, $factory->makeArchive(['year' => '2025']));
        $this->assertInstanceOf(ArchiveViewModel::class, $factory->makeArchiveFromPrepared(['year' => '2025']));
        $this->assertInstanceOf(MenuViewModel::class, $factory->makeMenu(['ID' => 1]));
        $this->assertInstanceOf(MenuViewModel::class, $factory->makeMenuFromPrepared(['id' => '1']));
        $this->assertInstanceOf(SidebarViewModel::class, $factory->makeSidebar([]));
        $this->assertInstanceOf(SidebarViewModel::class, $factory->makeSidebarFromPrepared([]));
    }

    public function testFactoryMakePostsReturnsCollection(): void
    {
        $factory = new ThemeViewModelFactory($this->escape());
        $posts = $factory->makePosts([
            ['ID' => 1, 'post_title' => 'One'],
            ['ID' => 2, 'post_title' => 'Two'],
        ]);

        $this->assertCount(2, $posts);
        $this->assertInstanceOf(PostViewModel::class, $posts[0]);
        $this->assertSame('Two', $posts[1]->title());
    }

    public function testThemeHelperLoadsSharedClassesOnce(): void
    {
        $ref = new ReflectionClass(ThemeHelper::class);
        $loaded = $ref->getProperty('loaded');
        $loaded->setAccessible(true);
        $loaded->setValue(null, false);

        ThemeHelper::loadShared();
        ThemeHelper::loadShared();

        $this->assertTrue($loaded->getValue(null));
        $this->assertInstanceOf(ThemeViewModelFactory::class, ThemeHelper::factory());
        $this->assertSame(ThemeHelper::factory(), ThemeHelper::factory());
    }

    public function testAbstractViewModelGetReturnsStoredValue(): void
    {
        $vm = PostViewModel::fromRow(['ID' => 42, 'post_title' => 'Hello'], $this->escape());
        $this->assertSame('Hello', $vm->get('title'));
        $this->assertNull($vm->get('nonexistent'));
    }
}
