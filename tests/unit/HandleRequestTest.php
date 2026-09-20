<?php

/**
 * Unit tests for the static front-end request dispatcher HandleRequest.
 *
 * Every dispatch branch is exercised through a mocked ThemeRendererInterface
 * installed via HandleRequest::setThemeRenderer(), so no real theme template
 * is included and no process-exiting redirect (direct_page()) is triggered.
 *
 * In-process untestable branches (documented, not asserted):
 *  - deliverQueryPost/Category/Page/Archive/Tag/Download when the query
 *    value is empty: they call direct_page(), which exits the PHP process.
 */

use PHPUnit\Framework\TestCase;

class HandleRequestTest extends TestCase
{
    /** @var array<string, mixed> Snapshot of touched Registry keys. */
    private $registrySnapshot = [];

    protected function setUp(): void
    {
        $this->registrySnapshot = [
            'frontService'      => Registry::get('frontService'),
            'handlerRegistry'   => Registry::get('handlerRegistry'),
            'downloadController' => Registry::get('downloadController'),
            'dbc'               => Registry::get('dbc'),
        ];

        $this->resetEnvironment();
    }

    protected function tearDown(): void
    {
        foreach ($this->registrySnapshot as $key => $value) {
            Registry::set($key, $value);
        }

        foreach (['search_results', 'search_keyword', 'search_pagination', 'download_identifier'] as $global) {
            unset($GLOBALS[$global]);
        }

        $this->resetEnvironment();
    }

    /**
     * Clear request superglobals and static caches so tests are isolated.
     */
    private function resetEnvironment(): void
    {
        unset($_SERVER['QUERY_STRING'], $_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME'], $_SERVER['PATH_INFO']);
        unset($_GET['q']);

        $reflection = new ReflectionClass('HandleRequest');
        foreach (['queryStringParsed', 'requestPathProcessed', 'frontHelper'] as $propertyName) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            $property->setValue(null);
        }

        HandleRequest::setThemeRenderer(null);
    }

    /**
     * Create a default no-op theme renderer mock with optional expectations.
     *
     * @return ThemeRendererInterface
     */
    private function makeRenderer()
    {
        return $this->createMock(ThemeRendererInterface::class);
    }

    // ---- Structural -------------------------------------------------------

    public function testClassIsFinalAndDeclaresExpectedPublicApi(): void
    {
        $reflection = new ReflectionClass('HandleRequest');
        $this->assertTrue($reflection->isFinal());

        $methodNames = array_map(
            function (ReflectionMethod $method) {
                return $method->getName();
            },
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
        );

        $this->assertContains('setThemeRenderer', $methodNames);
        $this->assertContains('handleFrontHelper', $methodNames);
        $this->assertContains('findRequestToRules', $methodNames);
        $this->assertContains('isMatchedUriRequested', $methodNames);
        $this->assertContains('isQueryStringRequested', $methodNames);
        $this->assertContains('checkMatchUriRequested', $methodNames);
        $this->assertContains('allowedPathRequested', $methodNames);
        $this->assertContains('deliverQueryString', $methodNames);
    }

    public function testAllowedPathRequestedAcceptsSingleArgument(): void
    {
        $method = new ReflectionMethod('HandleRequest', 'allowedPathRequested');
        $this->assertCount(1, $method->getParameters());
    }

    // ---- findRequestToRules ----------------------------------------------

    public function testFindRequestToRulesReturnsCapturedMatches(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/post/123';

        $parameters = HandleRequest::findRequestToRules(['/post/([0-9]+)']);

        $this->assertCount(1, $parameters);
        $this->assertSame('/post/123', $parameters[0][0]);
        $this->assertSame('123', $parameters[0][1]);
    }

    public function testFindRequestToRulesReturnsEmptyArrayOnNoMatch(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/post/123';

        $this->assertSame([], HandleRequest::findRequestToRules(['/category/[0-9]+']));
    }

    public function testFindRequestToRulesAcceptsNonArraySilently(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/post/123';

        $this->assertSame([], HandleRequest::findRequestToRules('post'));
    }

    public function testFindRequestToRulesToleratesMissingRequestUri(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        unset($_SERVER['REQUEST_URI']);

        $this->assertSame([], HandleRequest::findRequestToRules(['/post/[0-9]+']));
    }

    // ---- isMatchedUriRequested -------------------------------------------

    public function testIsMatchedUriRequestedReturnsFirstSegment(): void
    {
        $_SERVER['REQUEST_URI'] = '/blog/page/3';
        $this->assertSame('blog', HandleRequest::isMatchedUriRequested());
    }

    public function testIsMatchedUriRequestedReturnsEmptyWithoutRequestUri(): void
    {
        unset($_SERVER['REQUEST_URI']);
        $this->assertSame('', HandleRequest::isMatchedUriRequested());
    }

    // ---- isQueryStringRequested ------------------------------------------

    public function testIsQueryStringRequestedParsesKeyAndValue(): void
    {
        $_SERVER['QUERY_STRING'] = 'p=123';
        $this->assertSame(['key' => 'p', 'value' => '123'], HandleRequest::isQueryStringRequested());
    }

    public function testIsQueryStringRequestedUrlDecodesValue(): void
    {
        $_SERVER['QUERY_STRING'] = 'tag=php%20workshop';
        $this->assertSame(['key' => 'tag', 'value' => 'php workshop'], HandleRequest::isQueryStringRequested());
    }

    public function testIsQueryStringRequestedReturnsEmptyPairWithoutQueryString(): void
    {
        unset($_SERVER['QUERY_STRING']);
        $this->assertSame(['key' => '', 'value' => ''], HandleRequest::isQueryStringRequested());
    }

    public function testIsQueryStringRequestedIsMemoizedPerRequest(): void
    {
        $_SERVER['QUERY_STRING'] = 'p=1';
        $first = HandleRequest::isQueryStringRequested();

        $_SERVER['QUERY_STRING'] = 'q=2';

        $this->assertSame(['key' => 'p', 'value' => '1'], HandleRequest::isQueryStringRequested());
        $this->assertSame($first, HandleRequest::isQueryStringRequested());
    }

    // ---- allowedPathRequested --------------------------------------------

    public function testAllowedPathRequestedAllowsWhitelistedFirstSegment(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/post/42';

        $this->assertTrue(HandleRequest::allowedPathRequested(['post', 'blog']));
    }

    public function testAllowedPathRequestedRejectsUnknownFirstSegment(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/admin/help';

        $this->assertFalse(HandleRequest::allowedPathRequested(['post', 'blog']));
    }

    public function testAllowedPathRequestedAllowsRootPath(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';

        $this->assertTrue(HandleRequest::allowedPathRequested(['post', 'blog']));
    }

    public function testFindRequestToPathIsMemoizedPerRequest(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/post/42';
        $this->assertTrue(HandleRequest::allowedPathRequested(['post']));

        $_SERVER['REQUEST_URI'] = '/admin/evil';

        $this->assertTrue(
            HandleRequest::allowedPathRequested(['post']),
            'Path parsing must be cached for the request lifecycle'
        );
    }

    // ---- handleFrontHelper -----------------------------------------------

    public function testHandleFrontHelperReturnsNullWithoutRegisteredService(): void
    {
        Registry::set('frontService', null);
        $this->assertNull(HandleRequest::handleFrontHelper());
    }

    public function testHandleFrontHelperReturnsRegisteredService(): void
    {
        $service = $this->createMock(FrontService::class);
        Registry::set('frontService', $service);
        $this->assertSame($service, HandleRequest::handleFrontHelper());
    }

    // ---- deliverQueryString: registry handler ----------------------------

    public function testDeliverQueryStringDelegatesToRegistryHandler(): void
    {
        $_SERVER['QUERY_STRING'] = 'h=custom';

        $handler = $this->createMock(FrontRequestHandler::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with(['key' => 'h', 'value' => 'custom']);

        $registry = new HandlerRegistry();
        $registry->register('h', $handler);
        Registry::set('handlerRegistry', $registry);

        HandleRequest::setThemeRenderer($this->makeRenderer());

        HandleRequest::deliverQueryString();
    }

    // ---- deliverQueryString: built-in branches ---------------------------

    public function testDeliverQueryStringRendersBlogTemplate(): void
    {
        $_SERVER['QUERY_STRING'] = 'blog=';

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('blog');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryStringRendersPrivacyTemplate(): void
    {
        $_SERVER['QUERY_STRING'] = 'privacy=';

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('privacy');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryStringRendersSinglePost(): void
    {
        $_SERVER['QUERY_STRING'] = 'p=42';

        $service = $this->createMock(FrontService::class);
        $service->expects($this->once())->method('getSimplePost')->with('42')->willReturn(['ID' => 42]);
        Registry::set('frontService', $service);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('single');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryStringRenders404ForMissingPost(): void
    {
        $_SERVER['QUERY_STRING'] = 'p=42';

        $service = $this->createMock(FrontService::class);
        $service->expects($this->once())->method('getSimplePost')->with('42')->willReturn(null);
        Registry::set('frontService', $service);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render404');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryStringRendersCategoryArchive(): void
    {
        $_SERVER['QUERY_STRING'] = 'cat=5';

        $service = $this->createMock(FrontService::class);
        $service->expects($this->once())->method('getSimpleTopic')->with('5')->willReturn(['ID' => 5]);
        Registry::set('frontService', $service);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('category');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryStringRenders404ForMissingCategory(): void
    {
        $_SERVER['QUERY_STRING'] = 'cat=5';

        $service = $this->createMock(FrontService::class);
        $service->expects($this->once())->method('getSimpleTopic')->with('5')->willReturn([]);
        Registry::set('frontService', $service);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render404');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryStringRendersStaticPage(): void
    {
        $_SERVER['QUERY_STRING'] = 'pg=7';

        $service = $this->createMock(FrontService::class);
        $service->expects($this->once())->method('getSimplePage')->with('7')->willReturn(['ID' => 7]);
        Registry::set('frontService', $service);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('page');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryStringRendersArchive(): void
    {
        $_SERVER['QUERY_STRING'] = 'a=2026-01';

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('archive');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryStringRendersTagArchive(): void
    {
        $_SERVER['QUERY_STRING'] = 'tag=php';

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('tag');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    // ---- deliverQueryString: search --------------------------------------

    public function testDeliverQuerySearchRendersWithEmptyKeyword(): void
    {
        $_SERVER['QUERY_STRING'] = 'q=';
        $_GET['q'] = '';

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('search');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();

        $this->assertSame([], $GLOBALS['search_results']);
        $this->assertSame('', $GLOBALS['search_keyword']);
    }

    public function testDeliverQuerySearchRendersWithTooShortKeyword(): void
    {
        $_SERVER['QUERY_STRING'] = 'q=ok';
        $_GET['q'] = 'a';

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('search');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();

        $this->assertSame([], $GLOBALS['search_results']);
    }

    public function testDeliverQuerySearchRunsFinderForLongKeyword(): void
    {
        $_SERVER['QUERY_STRING'] = 'q=php';
        $_GET['q'] = 'php';

        $db = $this->createMock(Db::class);
        $db->expects($this->exactly(2))
            ->method('dbSelect')
            ->willReturnOnConsecutiveCalls(
                [(object)['ID' => 1, 'post_title' => 'PHP basics']],
                [(object)['total' => 2]]
            );
        Registry::set('dbc', $db);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('search');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();

        $this->assertSame('php', $GLOBALS['search_keyword']);
        $this->assertSame(2, $GLOBALS['search_pagination']['totalRows']);
        $this->assertSame(1, $GLOBALS['search_pagination']['totalPages']);
    }

    public function testDeliverQuerySearchFallsBackOnFinderFailure(): void
    {
        $_SERVER['QUERY_STRING'] = 'q=php';
        $_GET['q'] = 'php';

        $db = $this->createMock(Db::class);
        $db->expects($this->once())
            ->method('dbSelect')
            ->willThrowException(new RuntimeException('DB failure'));
        Registry::set('dbc', $db);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('search');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();

        $this->assertArrayNotHasKey('error', $GLOBALS['search_results']);
        $this->assertSame(0, $GLOBALS['search_results']['totalRows']);
        $this->assertSame('php', $GLOBALS['search_keyword']);
    }

    // ---- deliverQueryString: default/home dispatch -----------------------

    public function testDeliverDefaultQueryRendersHomeForRoot(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/';
        unset($_SERVER['QUERY_STRING']);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('home');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverDefaultQueryRendersHomeForWhitelistedSegment(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/blog';
        unset($_SERVER['QUERY_STRING']);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('home');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverDefaultQueryRenders404ForUnknownSegment(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/admin/help';
        unset($_SERVER['QUERY_STRING']);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render404');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverDefaultQueryRendersHomeWhenUriMatchesPath(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/foo';
        $_SERVER['QUERY_STRING'] = 'foo=bar';

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('home');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverDefaultQueryRenders404WhenUriMismatch(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/blog?foo=1';
        $_SERVER['QUERY_STRING'] = 'foo=1';

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render404');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    // ---- deliverQueryString: download ------------------------------------

    public function testDeliverQueryDownloadRendersDownloadPage(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $_SERVER['QUERY_STRING'] = 'download=' . $uuid;
        $_SERVER['REQUEST_URI'] = '/download/' . $uuid;

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('download');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    public function testDeliverQueryDownloadStreamsFile(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $_SERVER['QUERY_STRING'] = 'download=' . $uuid;
        $_SERVER['REQUEST_URI'] = '/download/' . $uuid . '/file';

        $controller = $this->createMock(DownloadController::class);
        $controller->expects($this->once())->method('download')->with($uuid);
        Registry::set('downloadController', $controller);

        HandleRequest::setThemeRenderer($this->makeRenderer());

        HandleRequest::deliverQueryString();
    }

    // ---- path-based download ---------------------------------------------

    public function testPathBasedDownloadRendersLandingPage(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/download/' . $uuid;
        unset($_SERVER['QUERY_STRING']);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('download');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();

        $this->assertSame($uuid, $GLOBALS['download_identifier']);
    }

    public function testPathBasedDownloadStreamsFile(): void
    {
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/download/' . $uuid . '/file';
        unset($_SERVER['QUERY_STRING']);

        $controller = $this->createMock(DownloadController::class);
        $controller->expects($this->once())->method('download')->with($uuid);
        Registry::set('downloadController', $controller);

        HandleRequest::setThemeRenderer($this->makeRenderer());

        HandleRequest::deliverQueryString();
    }

    public function testPathBasedDownloadInvalidIdentifierFallsThroughToHome(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/index.php';
        $_SERVER['REQUEST_URI'] = '/download/not-a-uuid';
        unset($_SERVER['QUERY_STRING']);

        $renderer = $this->makeRenderer();
        $renderer->expects($this->once())->method('render')->with('home');
        HandleRequest::setThemeRenderer($renderer);

        HandleRequest::deliverQueryString();
    }

    // ---- setThemeRenderer ------------------------------------------------

    public function testSetThemeRendererCanBeResetToNull(): void
    {
        HandleRequest::setThemeRenderer($this->makeRenderer());
        HandleRequest::setThemeRenderer(null);

        $reflection = new ReflectionProperty('HandleRequest', 'themeRenderer');
        $reflection->setAccessible(true);
        $this->assertNull($reflection->getValue());
    }
}