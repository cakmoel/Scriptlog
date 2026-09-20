<?php

if (!function_exists('t')) {
    function t(string $key, array $params = []): string
    {
        return $key;
    }
}

use PHPUnit\Framework\TestCase;

class SearchControllerTest extends TestCase
{
    private $originalGet;

    protected function setUp(): void
    {
        $this->originalGet = $_GET;

        // Reset rate limiter state to avoid cross-test accumulation
        if (class_exists('Scriptlog\Core\RateLimiter')) {
            try {
                $limiter = new \Scriptlog\Core\RateLimiter();
                $limiter->reset();
            } catch (\Throwable $e) {
                // Ignore rate limiter setup failures in test env
            }
        }

        $prevHandler = set_error_handler(function ($severity, $message, $file, $line) use (&$prevHandler) {
            if (stripos($message, 'http_response_code') !== false) {
                return true;
            }
            if ($prevHandler) {
                return call_user_func($prevHandler, $severity, $message, $file, $line);
            }
            return false;
        });

        require_once __DIR__ . '/../../lib/core/Registry.php';
        require_once __DIR__ . '/../../lib/core/SearchFinder.php';
        require_once __DIR__ . '/../../lib/controller/SearchController.php';
    }

    protected function tearDown(): void
    {
        $_GET = $this->originalGet;
        restore_error_handler();
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists('Scriptlog\Controller\SearchController'));
    }

    public function testConstructorAcceptsNoArguments(): void
    {
        $controller = new Scriptlog\Controller\SearchController();
        $this->assertInstanceOf(Scriptlog\Controller\SearchController::class, $controller);
    }

    public function testConstructorAcceptsThemeRendererMock(): void
    {
        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer);
        $this->assertInstanceOf(Scriptlog\Controller\SearchController::class, $controller);
    }

    public function testConstructorAcceptsSearchFinderMock(): void
    {
        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $finder = $this->createMock('Scriptlog\Core\SearchFinder');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $this->assertInstanceOf(Scriptlog\Controller\SearchController::class, $controller);
    }

    public function testSearchWithEmptyKeywordDoesNotThrow(): void
    {
        $_GET = ['q' => ''];

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchWithShortKeywordDoesNotThrow(): void
    {
        $_GET = ['q' => 'x'];

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchWithKeywordParamDoesNotThrow(): void
    {
        $_GET = ['keyword' => 'test'];

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchReadsPageParam(): void
    {
        $_GET = ['q' => 'php', 'page' => '3'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchAll'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchAll')
            ->with($this->anything(), 3, $this->anything())
            ->willReturn([
                'results' => [],
                'totalRows' => 0,
                'page' => 3,
                'perPage' => 10,
                'totalPages' => 0,
                'keyword' => 'php'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchDefaultsPageToOne(): void
    {
        $_GET = ['q' => 'php'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchAll'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchAll')
            ->with($this->anything(), 1, $this->anything())
            ->willReturn([
                'results' => [],
                'totalRows' => 0,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => 0,
                'keyword' => 'php'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchClampsNegativePageToOne(): void
    {
        $_GET = ['q' => 'php', 'page' => '-5'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchAll'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchAll')
            ->with($this->anything(), 1, $this->anything())
            ->willReturn([
                'results' => [],
                'totalRows' => 0,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => 0,
                'keyword' => 'php'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchCachesSearchFinderForPostsType(): void
    {
        $_GET = ['q' => 'php', 'type' => 'posts', 'page' => '2'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchPost'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchPost')
            ->with($this->anything(), 2, $this->anything())
            ->willReturn([
                'results' => [],
                'totalRows' => 0,
                'page' => 2,
                'perPage' => 10,
                'totalPages' => 0,
                'keyword' => 'php'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchCachesSearchFinderForPagesType(): void
    {
        $_GET = ['q' => 'php', 'type' => 'pages', 'page' => '1'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchPage'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchPage')
            ->with($this->anything(), 1, $this->anything())
            ->willReturn([
                'results' => [],
                'totalRows' => 0,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => 0,
                'keyword' => 'php'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchIncludesPaginationInGlobals(): void
    {
        $_GET = ['q' => 'php'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchAll'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchAll')
            ->willReturn([
                'results' => [],
                'totalRows' => 0,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => 0,
                'keyword' => 'php'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertArrayHasKey('search_results', $GLOBALS);
        $this->assertArrayHasKey('search_keyword', $GLOBALS);
        $this->assertArrayHasKey('search_pagination', $GLOBALS);
    }

    public function testSearchHandlesErrorFromFinder(): void
    {
        $_GET = ['q' => 'php'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchAll'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchAll')
            ->willReturn([
                'results' => [],
                'totalRows' => 0,
                'page' => 1,
                'perPage' => 10,
                'totalPages' => 0,
                'keyword' => 'php',
                'error' => 'FULLTEXT index not found'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertArrayHasKey('search_pagination', $GLOBALS);
        $this->assertArrayHasKey('search_results', $GLOBALS);
        $this->assertArrayNotHasKey('error', $GLOBALS['search_results']);
    }

    public function testSearchClampsPageBeyondTotalToTotalPages(): void
    {
        $_GET = ['q' => 'php', 'page' => '999'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchAll'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchAll')
            ->willReturn([
                'results' => [],
                'totalRows' => 50,
                'page' => 999,
                'perPage' => 10,
                'totalPages' => 5,
                'keyword' => 'php'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertArrayHasKey('search_pagination', $GLOBALS);
        $this->assertEquals(5, $GLOBALS['search_pagination']['page']);
    }

    public function testSearchPassesPerPageFromConstant(): void
    {
        $_GET = ['q' => 'php', 'page' => '2'];

        $finder = $this->getMockBuilder('Scriptlog\Core\SearchFinder')
            ->onlyMethods(['searchAll'])
            ->getMock();

        $finder->expects($this->once())
            ->method('searchAll')
            ->with($this->anything(), 2, 10)
            ->willReturn([
                'results' => [],
                'totalRows' => 0,
                'page' => 2,
                'perPage' => 10,
                'totalPages' => 0,
                'keyword' => 'php'
            ]);

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $controller = new Scriptlog\Controller\SearchController($renderer, $finder);
        $controller->search();

        $this->assertTrue(true);
    }

    public function testSearchHoneypotReturnsEmptyResults(): void
    {
        $_GET = ['q' => 'php', 'search_verify' => 'bot'];

        $renderer = $this->createMock('Scriptlog\Core\ThemeRendererInterface');
        $renderer->expects($this->once())
            ->method('render')
            ->with('search');

        $controller = new Scriptlog\Controller\SearchController($renderer);
        $controller->search();

        $this->assertSame('', $GLOBALS['search_keyword']);
        $this->assertArrayHasKey('results', $GLOBALS['search_results']);
        $this->assertSame([], $GLOBALS['search_results']['results']);
        unset($GLOBALS['search_results'], $GLOBALS['search_keyword'], $GLOBALS['search_pagination'], $GLOBALS['search_rate_limited']);
    }
}
