<?php
/**
 * ThemeViewModelPreparedTest
 *
 * Unit tests for the Phase 2 theme remediation "prepared values" pipeline:
 * the fromPrepared() builders on Page/Archive/Menu/SidebarViewModel, the
 * matching make*FromPrepared() factory methods, and the MenuViewModel tree
 * builders/renderers used by front_navigation().
 *
 * DB-free by construction: prepared arrays already carry escaped values, so
 * no database-backed helpers (app_url(), permalinks(), ...) are invoked.
 *
 * @category Tests
 * @package  Scriptlog
 * @version  1.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('HTMLPURIFIER_PREFIX')) {
    define('HTMLPURIFIER_PREFIX', dirname(__DIR__, 2) . '/lib/core');
}

require_once __DIR__ . '/../../lib/utility/theme-escape.php';
require_once __DIR__ . '/../../lib/core/Theme/ThemeHelper.php';
require_once __DIR__ . '/../../public/themes/blog/functions-nav.php';

class ThemeViewModelPreparedTest extends TestCase
{
    /**
     * @return ThemeViewModelFactory
     */
    private function factory()
    {
        return Scriptlog\Core\Theme\ThemeHelper::factory();
    }

    public function testPageFromPreparedStoresVerbatimAndCoercesToNullable(): void
    {
        $vm = Scriptlog\Core\Theme\PageViewModel::fromPrepared([
            'id'            => '7',
            'title'         => 'About &lt;Us&gt;',
            'url'           => '/page/about-us',
            'author'        => 'admin',
            'date'          => 'March 02, 2024',
            'content'       => '<p>Trusted sanitized <em>HTML</em></p>',
            'media'         => 'about.jpg',
            'media_caption' => 'Our office',
        ]);

        $this->assertSame('About &lt;Us&gt;', $vm->title());
        $this->assertSame('/page/about-us', $vm->url());
        $this->assertSame('<p>Trusted sanitized <em>HTML</em></p>', $vm->content());
        $this->assertSame('about.jpg', $vm->media());
        $this->assertSame(7, (int)$vm->id());
        $this->assertNull($vm->slug());
        $this->assertNull($vm->tags());
    }

    public function testArchiveFromPreparedStoresVerbatim(): void
    {
        $vm = Scriptlog\Core\Theme\ArchiveViewModel::fromPrepared([
            'url'   => '/archive/03/2024',
            'label' => 'March',
            'year'  => '2024',
            'month' => '03',
            'count' => '12',
        ]);

        $this->assertSame('/archive/03/2024', $vm->url());
        $this->assertSame('March', $vm->label());
        $this->assertSame('2024', $vm->year());
        $this->assertSame('03', $vm->month());
        $this->assertSame('12', $vm->count());
    }

    public function testMenuFromPreparedDefaultsUrlToHashWhenEmpty(): void
    {
        $vm = Scriptlog\Core\Theme\MenuViewModel::fromPrepared([
            'id'     => '3',
            'label'  => 'Home',
            'parent' => '0',
        ]);

        $this->assertSame('Home', $vm->label());
        $this->assertSame('#', $vm->url());
        $this->assertFalse($vm->hasChildren());
    }

    public function testSidebarFromPreparedBuildsTypedAggregates(): void
    {
        $post = Scriptlog\Core\Theme\PostViewModel::fromPrepared([
            'id'       => '4',
            'title'    => 'Hello &lt;World&gt;',
            'url'      => '/post/4/hello-world',
            'author'   => 'admin',
            'comments' => '7',
        ]);

        $vm = Scriptlog\Core\Theme\SidebarViewModel::fromPrepared([
            'latest_posts'  => [$post],
            'categories'    => [['title' => 'News', 'url' => '/category/news', 'count' => '5']],
            'archives'      => [['label' => 'March 2024', 'url' => '/archive/03/2024', 'count' => '12']],
            'tags'          => [['label' => 'php', 'url' => '/tag/php']],
            'search_action' => '/search',
        ]);

        $posts = $vm->latestPosts();
        $this->assertCount(1, $posts);
        $this->assertInstanceOf(Scriptlog\Core\Theme\PostViewModel::class, $posts[0]);
        $this->assertSame('Hello &lt;World&gt;', $posts[0]->title());
        $this->assertSame('News', $vm->categories()[0]['title']);
        $this->assertSame('March 2024', $vm->archives()[0]['label']);
        $this->assertSame('php', $vm->tags()[0]['label']);
        $this->assertSame('/search', $vm->searchAction());
    }

    public function testFactoryFromPreparedMethodsDelegateToBuilders(): void
    {
        $factory = $this->factory();

        $page = $factory->makePageFromPrepared(['id' => '1', 'title' => 'P']);
        $this->assertInstanceOf(Scriptlog\Core\Theme\PageViewModel::class, $page);
        $this->assertSame('P', $page->title());

        $archive = $factory->makeArchiveFromPrepared(['url' => '/a', 'label' => 'L']);
        $this->assertInstanceOf(Scriptlog\Core\Theme\ArchiveViewModel::class, $archive);
        $this->assertSame('L', $archive->label());

        $menu = $factory->makeMenuFromPrepared(['id' => '2', 'label' => 'M']);
        $this->assertInstanceOf(Scriptlog\Core\Theme\MenuViewModel::class, $menu);
        $this->assertSame('#', $menu->url());

        $sidebar = $factory->makeSidebarFromPrepared(['search_action' => '/search']);
        $this->assertInstanceOf(Scriptlog\Core\Theme\SidebarViewModel::class, $sidebar);
        $this->assertSame('/search', $sidebar->searchAction());
    }

    public function testBuildMenuTreeAttachesChildrenRecursively(): void
    {
        $factory = $this->factory();

        $items = [
            1 => $factory->makeMenuFromPrepared(['id' => '1', 'label' => 'Root', 'url' => '/root', 'parent' => '0']),
            2 => $factory->makeMenuFromPrepared(['id' => '2', 'label' => 'Child', 'url' => '/child', 'parent' => '1']),
            3 => $factory->makeMenuFromPrepared(['id' => '3', 'label' => 'Sibling', 'url' => '/sibling', 'parent' => '0']),
        ];

        $parents = [0 => [1, 3], 1 => [2]];

        $tree = build_menu_tree($items, $parents, 0);

        $this->assertCount(2, $tree);
        $this->assertSame('Root', $tree[0]->label());
        $this->assertTrue($tree[0]->hasChildren());
        $this->assertCount(1, $tree[0]->children());
        $this->assertSame('Child', $tree[0]->children()[0]->label());
        $this->assertFalse($tree[0]->children()[0]->hasChildren());
        $this->assertSame('Sibling', $tree[1]->label());
        $this->assertFalse($tree[1]->hasChildren());
    }

    public function testRenderMenuTreeEmitsDropdownMarkup(): void
    {
        $factory = $this->factory();

        $items = [
            1 => $factory->makeMenuFromPrepared(['id' => '1', 'label' => 'Root', 'url' => '/root', 'parent' => '0']),
            2 => $factory->makeMenuFromPrepared(['id' => '2', 'label' => 'Child', 'url' => '/child', 'parent' => '1']),
        ];

        $parents = [0 => [1], 1 => [2]];

        $html = render_menu_tree(build_menu_tree($items, $parents, 0));

        $this->assertStringContainsString("<li class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='/root'>Root</a>", $html);
        $this->assertStringContainsString('<ul class="dropdown-menu">', $html);
        $this->assertStringContainsString("<li><a href='/child'>Child</a></li>", $html);
    }
}
