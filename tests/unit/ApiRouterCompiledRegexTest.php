<?php

/**
 * ApiRouter Compiled Regex Unit Tests
 *
 * Tests the GCB-10C combined-regex dispatch implementation:
 * - compileRoutes() builds correct (?|...) branch-reset regex
 * - matchRoute() resolves URIs to correct handlers and params
 * - Chunking works when exceeding CHUNK_SIZE
 * - All HTTP methods dispatch correctly
 */
class ApiRouterCompiledRegexTest extends \PHPUnit\Framework\TestCase
{
    private $router;

    protected function setUp(): void
    {
        $this->router = new ApiRouter();

        // Register all routes matching api/index.php
        // GET routes (21 total)
        $this->router->get('posts', 'PostsApiController@index');
        $this->router->get('posts/(?P<id>[0-9]+)', 'PostsApiController@show');
        $this->router->get('posts/(?P<id>[0-9]+)/comments', 'PostsApiController@comments');
        $this->router->get('categories', 'CategoriesApiController@index');
        $this->router->get('categories/(?P<id>[0-9]+)', 'CategoriesApiController@show');
        $this->router->get('categories/(?P<id>[0-9]+)/posts', 'CategoriesApiController@posts');
        $this->router->get('comments', 'CommentsApiController@index');
        $this->router->get('comments/(?P<id>[0-9]+)', 'CommentsApiController@show');
        $this->router->get('archives', 'ArchivesApiController@index');
        $this->router->get('archives/(?P<year>[0-9]{4})', 'ArchivesApiController@year');
        $this->router->get('archives/(?P<year>[0-9]{4})/(?P<month>[0-9]{2})', 'ArchivesApiController@month');
        $this->router->get('search', 'SearchApiController@index');
        $this->router->get('search/posts', 'SearchApiController@posts');
        $this->router->get('search/pages', 'SearchApiController@pages');
        $this->router->get('languages', 'LanguagesApiController@index');
        $this->router->get('languages/active', 'LanguagesApiController@index');
        $this->router->get('languages/default', 'LanguagesApiController@default');
        $this->router->get('languages/(?P<code>[a-z]{2})', 'LanguagesApiController@show');
        $this->router->get('translations/(?P<code>[a-z]{2})', 'TranslationsApiController@index');
        // Literal /export route MUST be registered before the generic /{key} route
        $this->router->get('translations/(?P<code>[a-z]{2})/export', 'TranslationsApiController@export');
        $this->router->get('translations/(?P<code>[a-z]{2})/(?P<key>[a-zA-Z0-9._-]+)', 'TranslationsApiController@show');
        $this->router->get('openapi.json', 'closure');
        $this->router->get('', 'ApiController@info');
        $this->router->get('/', 'ApiController@info');
        $this->router->get('csrf-token', 'closure');

        // POST routes (10)
        $this->router->post('posts', 'PostsApiController@store');
        $this->router->post('posts/(?P<id>[0-9]+)/unlock', 'ProtectedPostApiController@unlock');
        $this->router->post('posts/(?P<id>[0-9]+)/verify', 'ProtectedPostApiController@verify');
        $this->router->post('media/upload', 'MediaApiController@upload');
        $this->router->post('categories', 'CategoriesApiController@store');
        $this->router->post('comments', 'CommentsApiController@store');
        $this->router->post('gdpr/consent', 'GdprApiController@consent');
        $this->router->post('languages', 'LanguagesApiController@store');
        $this->router->post('translations/(?P<code>[a-z]{2})', 'TranslationsApiController@store');
        $this->router->post('translations/(?P<code>[a-z]{2})/import', 'TranslationsApiController@import');
        $this->router->post('translations/([a-z]{2})/cache', 'TranslationsApiController@cache');

        // PUT routes (6)
        $this->router->put('posts/(?P<id>[0-9]+)', 'PostsApiController@update');
        $this->router->put('categories/(?P<id>[0-9]+)', 'CategoriesApiController@update');
        $this->router->put('comments/(?P<id>[0-9]+)', 'CommentsApiController@update');
        $this->router->put('languages/(?P<code>[a-z]{2})', 'LanguagesApiController@update');
        $this->router->put('languages/(?P<code>[a-z]{2})/default', 'LanguagesApiController@setDefault');
        $this->router->put('translations/(?P<id>[0-9]+)', 'TranslationsApiController@update');

        // PATCH routes (3)
        $this->router->patch('posts/(?P<id>[0-9]+)', 'PostsApiController@update');
        $this->router->patch('categories/(?P<id>[0-9]+)', 'CategoriesApiController@update');
        $this->router->patch('comments/(?P<id>[0-9]+)', 'CommentsApiController@update');

        // DELETE routes (5)
        $this->router->delete('posts/(?P<id>[0-9]+)', 'PostsApiController@destroy');
        $this->router->delete('categories/(?P<id>[0-9]+)', 'CategoriesApiController@destroy');
        $this->router->delete('comments/(?P<id>[0-9]+)', 'CommentsApiController@destroy');
        $this->router->delete('languages/(?P<code>[a-z]{2})', 'LanguagesApiController@destroy');
        $this->router->delete('translations/(?P<id>[0-9]+)', 'TranslationsApiController@destroy');

        // QUERY routes (3 — RFC 10008)
        $this->router->query('query', 'QueryApiController@index');
        $this->router->query('query/posts', 'QueryApiController@posts');
        $this->router->query('query/pages', 'QueryApiController@pages');
    }

    // ========================================
    // GET Route Tests
    // ========================================

    public function testGetPosts()
    {
        $result = $this->invokeMatchRoute('GET', 'posts');
        $this->assertNotFalse($result);
        $this->assertEquals('PostsApiController@index', $result['handler']);
        $this->assertEmpty($result['params']);
    }

    public function testGetPostById()
    {
        $result = $this->invokeMatchRoute('GET', 'posts/42');
        $this->assertNotFalse($result);
        $this->assertEquals('PostsApiController@show', $result['handler']);
        $this->assertEquals(['id' => '42'], $result['params']);
    }

    public function testGetPostComments()
    {
        $result = $this->invokeMatchRoute('GET', 'posts/42/comments');
        $this->assertNotFalse($result);
        $this->assertEquals('PostsApiController@comments', $result['handler']);
        $this->assertEquals(['id' => '42'], $result['params']);
    }

    public function testGetCategories()
    {
        $result = $this->invokeMatchRoute('GET', 'categories');
        $this->assertEquals('CategoriesApiController@index', $result['handler']);
    }

    public function testGetCategoryById()
    {
        $result = $this->invokeMatchRoute('GET', 'categories/5');
        $this->assertEquals('CategoriesApiController@show', $result['handler']);
        $this->assertEquals(['id' => '5'], $result['params']);
    }

    public function testGetCategoryPosts()
    {
        $result = $this->invokeMatchRoute('GET', 'categories/5/posts');
        $this->assertEquals('CategoriesApiController@posts', $result['handler']);
        $this->assertEquals(['id' => '5'], $result['params']);
    }

    public function testGetComments()
    {
        $result = $this->invokeMatchRoute('GET', 'comments');
        $this->assertEquals('CommentsApiController@index', $result['handler']);
    }

    public function testGetCommentById()
    {
        $result = $this->invokeMatchRoute('GET', 'comments/99');
        $this->assertEquals('CommentsApiController@show', $result['handler']);
        $this->assertEquals(['id' => '99'], $result['params']);
    }

    public function testGetArchives()
    {
        $result = $this->invokeMatchRoute('GET', 'archives');
        $this->assertEquals('ArchivesApiController@index', $result['handler']);
    }

    public function testGetArchivesByYear()
    {
        $result = $this->invokeMatchRoute('GET', 'archives/2026');
        $this->assertEquals('ArchivesApiController@year', $result['handler']);
        $this->assertEquals(['year' => '2026'], $result['params']);
    }

    public function testGetArchivesByYearAndMonth()
    {
        $result = $this->invokeMatchRoute('GET', 'archives/2026/03');
        $this->assertEquals('ArchivesApiController@month', $result['handler']);
        $this->assertEquals(['year' => '2026', 'month' => '03'], $result['params']);
    }

    public function testGetSearch()
    {
        $result = $this->invokeMatchRoute('GET', 'search');
        $this->assertEquals('SearchApiController@index', $result['handler']);
    }

    public function testGetSearchPosts()
    {
        $result = $this->invokeMatchRoute('GET', 'search/posts');
        $this->assertEquals('SearchApiController@posts', $result['handler']);
    }

    public function testGetSearchPages()
    {
        $result = $this->invokeMatchRoute('GET', 'search/pages');
        $this->assertEquals('SearchApiController@pages', $result['handler']);
    }

    public function testGetLanguages()
    {
        $result = $this->invokeMatchRoute('GET', 'languages');
        $this->assertEquals('LanguagesApiController@index', $result['handler']);
    }

    public function testGetLanguagesActive()
    {
        $result = $this->invokeMatchRoute('GET', 'languages/active');
        $this->assertEquals('LanguagesApiController@index', $result['handler']);
    }

    public function testGetLanguagesDefault()
    {
        $result = $this->invokeMatchRoute('GET', 'languages/default');
        $this->assertEquals('LanguagesApiController@default', $result['handler']);
    }

    public function testGetLanguageByCode()
    {
        $result = $this->invokeMatchRoute('GET', 'languages/en');
        $this->assertEquals('LanguagesApiController@show', $result['handler']);
        $this->assertEquals(['code' => 'en'], $result['params']);
    }

    public function testGetTranslations()
    {
        $result = $this->invokeMatchRoute('GET', 'translations/en');
        $this->assertEquals('TranslationsApiController@index', $result['handler']);
        $this->assertEquals(['code' => 'en'], $result['params']);
    }

    /**
     * Regression test: the literal /export route must win over the generic
     * /{key} route for GET /translations/{code}/export (api/index.php now
     * registers /export before /{key}).
     */
    public function testGetTranslationsExportRoutesToExportHandler()
    {
        $result = $this->invokeMatchRoute('GET', 'translations/en/export');
        $this->assertEquals('TranslationsApiController@export', $result['handler']);
        $this->assertEquals(['code' => 'en'], $result['params']);
    }

    public function testGetTranslationsKeyStillMatchesGenericKey()
    {
        $result = $this->invokeMatchRoute('GET', 'translations/en/nav.dashboard');
        $this->assertEquals('TranslationsApiController@show', $result['handler']);
        $this->assertEquals(['code' => 'en', 'key' => 'nav.dashboard'], $result['params']);
    }

    public function testGetOpenApiSpec()
    {
        $result = $this->invokeMatchRoute('GET', 'openapi.json');
        $this->assertNotFalse($result);
    }

    public function testGetApiInfo()
    {
        $result = $this->invokeMatchRoute('GET', '');
        $this->assertEquals('ApiController@info', $result['handler']);
    }

    public function testGetApiInfoWithSlash()
    {
        $result = $this->invokeMatchRoute('GET', '/');
        $this->assertEquals('ApiController@info', $result['handler']);
    }

    public function testGetCsrfToken()
    {
        $result = $this->invokeMatchRoute('GET', 'csrf-token');
        $this->assertNotFalse($result);
    }

    // ========================================
    // POST Route Tests
    // ========================================

    public function testPostPosts()
    {
        $result = $this->invokeMatchRoute('POST', 'posts');
        $this->assertEquals('PostsApiController@store', $result['handler']);
    }

    public function testPostUnlock()
    {
        $result = $this->invokeMatchRoute('POST', 'posts/5/unlock');
        $this->assertEquals('ProtectedPostApiController@unlock', $result['handler']);
        $this->assertEquals(['id' => '5'], $result['params']);
    }

    public function testPostVerify()
    {
        $result = $this->invokeMatchRoute('POST', 'posts/5/verify');
        $this->assertEquals('ProtectedPostApiController@verify', $result['handler']);
        $this->assertEquals(['id' => '5'], $result['params']);
    }

    public function testPostMediaUpload()
    {
        $result = $this->invokeMatchRoute('POST', 'media/upload');
        $this->assertEquals('MediaApiController@upload', $result['handler']);
    }

    public function testPostCategories()
    {
        $result = $this->invokeMatchRoute('POST', 'categories');
        $this->assertEquals('CategoriesApiController@store', $result['handler']);
    }

    public function testPostComments()
    {
        $result = $this->invokeMatchRoute('POST', 'comments');
        $this->assertEquals('CommentsApiController@store', $result['handler']);
    }

    public function testPostGdprConsent()
    {
        $result = $this->invokeMatchRoute('POST', 'gdpr/consent');
        $this->assertEquals('GdprApiController@consent', $result['handler']);
    }

    public function testPostLanguages()
    {
        $result = $this->invokeMatchRoute('POST', 'languages');
        $this->assertEquals('LanguagesApiController@store', $result['handler']);
    }

    public function testPostTranslations()
    {
        $result = $this->invokeMatchRoute('POST', 'translations/en');
        $this->assertEquals('TranslationsApiController@store', $result['handler']);
        $this->assertEquals(['code' => 'en'], $result['params']);
    }

    public function testPostTranslationsImport()
    {
        $result = $this->invokeMatchRoute('POST', 'translations/en/import');
        $this->assertEquals('TranslationsApiController@import', $result['handler']);
        $this->assertEquals(['code' => 'en'], $result['params']);
    }

    public function testPostTranslationsCache()
    {
        $result = $this->invokeMatchRoute('POST', 'translations/en/cache');
        $this->assertEquals('TranslationsApiController@cache', $result['handler']);
    }

    // ========================================
    // PUT Route Tests
    // ========================================

    public function testPutPosts()
    {
        $result = $this->invokeMatchRoute('PUT', 'posts/42');
        $this->assertEquals('PostsApiController@update', $result['handler']);
        $this->assertEquals(['id' => '42'], $result['params']);
    }

    public function testPutCategories()
    {
        $result = $this->invokeMatchRoute('PUT', 'categories/5');
        $this->assertEquals('CategoriesApiController@update', $result['handler']);
        $this->assertEquals(['id' => '5'], $result['params']);
    }

    public function testPutComments()
    {
        $result = $this->invokeMatchRoute('PUT', 'comments/99');
        $this->assertEquals('CommentsApiController@update', $result['handler']);
        $this->assertEquals(['id' => '99'], $result['params']);
    }

    public function testPutLanguages()
    {
        $result = $this->invokeMatchRoute('PUT', 'languages/fr');
        $this->assertEquals('LanguagesApiController@update', $result['handler']);
        $this->assertEquals(['code' => 'fr'], $result['params']);
    }

    public function testPutLanguagesDefault()
    {
        $result = $this->invokeMatchRoute('PUT', 'languages/fr/default');
        $this->assertEquals('LanguagesApiController@setDefault', $result['handler']);
        $this->assertEquals(['code' => 'fr'], $result['params']);
    }

    public function testPutTranslations()
    {
        $result = $this->invokeMatchRoute('PUT', 'translations/42');
        $this->assertEquals('TranslationsApiController@update', $result['handler']);
        $this->assertEquals(['id' => '42'], $result['params']);
    }

    // ========================================
    // PATCH Route Tests
    // ========================================

    public function testPatchPosts()
    {
        $result = $this->invokeMatchRoute('PATCH', 'posts/42');
        $this->assertEquals('PostsApiController@update', $result['handler']);
        $this->assertEquals(['id' => '42'], $result['params']);
    }

    public function testPatchCategories()
    {
        $result = $this->invokeMatchRoute('PATCH', 'categories/5');
        $this->assertEquals('CategoriesApiController@update', $result['handler']);
        $this->assertEquals(['id' => '5'], $result['params']);
    }

    public function testPatchComments()
    {
        $result = $this->invokeMatchRoute('PATCH', 'comments/99');
        $this->assertEquals('CommentsApiController@update', $result['handler']);
        $this->assertEquals(['id' => '99'], $result['params']);
    }

    // ========================================
    // DELETE Route Tests
    // ========================================

    public function testDeletePosts()
    {
        $result = $this->invokeMatchRoute('DELETE', 'posts/42');
        $this->assertEquals('PostsApiController@destroy', $result['handler']);
        $this->assertEquals(['id' => '42'], $result['params']);
    }

    public function testDeleteCategories()
    {
        $result = $this->invokeMatchRoute('DELETE', 'categories/5');
        $this->assertEquals('CategoriesApiController@destroy', $result['handler']);
        $this->assertEquals(['id' => '5'], $result['params']);
    }

    public function testDeleteComments()
    {
        $result = $this->invokeMatchRoute('DELETE', 'comments/99');
        $this->assertEquals('CommentsApiController@destroy', $result['handler']);
        $this->assertEquals(['id' => '99'], $result['params']);
    }

    public function testDeleteLanguages()
    {
        $result = $this->invokeMatchRoute('DELETE', 'languages/fr');
        $this->assertEquals('LanguagesApiController@destroy', $result['handler']);
        $this->assertEquals(['code' => 'fr'], $result['params']);
    }

    public function testDeleteTranslations()
    {
        $result = $this->invokeMatchRoute('DELETE', 'translations/42');
        $this->assertEquals('TranslationsApiController@destroy', $result['handler']);
        $this->assertEquals(['id' => '42'], $result['params']);
    }

    // ========================================
    // QUERY Route Tests (RFC 10008)
    // ========================================

    public function testQueryIndex()
    {
        $result = $this->invokeMatchRoute('QUERY', 'query');
        $this->assertNotFalse($result);
        $this->assertEquals('QueryApiController@index', $result['handler']);
        $this->assertEmpty($result['params']);
    }

    public function testQueryPosts()
    {
        $result = $this->invokeMatchRoute('QUERY', 'query/posts');
        $this->assertNotFalse($result);
        $this->assertEquals('QueryApiController@posts', $result['handler']);
        $this->assertEmpty($result['params']);
    }

    public function testQueryPages()
    {
        $result = $this->invokeMatchRoute('QUERY', 'query/pages');
        $this->assertNotFalse($result);
        $this->assertEquals('QueryApiController@pages', $result['handler']);
        $this->assertEmpty($result['params']);
    }

    public function testQueryNonexistentRoute()
    {
        $result = $this->invokeMatchRoute('QUERY', 'nonexistent');
        $this->assertFalse($result);
    }

    public function testQueryOnGetRouteFails()
    {
        $result = $this->invokeMatchRoute('QUERY', 'posts');
        $this->assertFalse($result);
    }

    public function testGetOnQueryRouteFails()
    {
        $result = $this->invokeMatchRoute('GET', 'query');
        $this->assertFalse($result);
    }

    // ========================================
    // Negative Tests (404 / wrong method)
    // ========================================

    public function testGetNonexistentRoute()
    {
        $result = $this->invokeMatchRoute('GET', 'nonexistent');
        $this->assertFalse($result);
    }

    public function testPostNonexistentRoute()
    {
        $result = $this->invokeMatchRoute('POST', 'nonexistent');
        $this->assertFalse($result);
    }

    public function testMethodNotAllowed()
    {
        $result = $this->invokeMatchRoute('GET', 'media/upload');
        $this->assertFalse($result);
    }

    public function testInvalidParamFormat()
    {
        $result = $this->invokeMatchRoute('GET', 'posts/abc');
        $this->assertFalse($result);
    }

    public function testUnknownMethod()
    {
        $result = $this->invokeMatchRoute('UNKNOWN', 'posts');
        $this->assertFalse($result);
    }

    // ========================================
    // Compilation & Chunking Tests
    // ========================================

    public function testCompiledRegexIsCached()
    {
        $ref = new ReflectionMethod($this->router, 'compileRoutes');
        $ref->setAccessible(true);

        $compiled = $ref->invoke($this->router, 'GET');
        $this->assertIsArray($compiled);
        $this->assertNotEmpty($compiled);

        // Each chunk has regex and map
        foreach ($compiled as $chunk) {
            $this->assertArrayHasKey('regex', $chunk);
            $this->assertArrayHasKey('map', $chunk);
            $this->assertNotEmpty($chunk['map']);
        }

        // GET has 26 routes, should create 3 chunks of 10
        $this->assertCount(3, $compiled);
    }

    public function testCompiledRegexForGetUsesBranchReset()
    {
        $ref = new ReflectionMethod($this->router, 'compileRoutes');
        $ref->setAccessible(true);

        $compiled = $ref->invoke($this->router, 'GET');
        $combined = $compiled[0]['regex'];

        $this->assertStringContainsString('(?|', $combined);
    }

    public function testCompiledRegexChunking()
    {
        $ref = new ReflectionMethod($this->router, 'compileRoutes');
        $ref->setAccessible(true);

        // POST has 11 routes → 2 chunks
        $postCompiled = $ref->invoke($this->router, 'POST');
        $this->assertCount(2, $postCompiled);
        $this->assertLessThanOrEqual(10, count($postCompiled[0]['map']));

        // PUT has 6 routes → 1 chunk
        $putCompiled = $ref->invoke($this->router, 'PUT');
        $this->assertCount(1, $putCompiled);

        // PATCH has 3 routes → 1 chunk
        $patchCompiled = $ref->invoke($this->router, 'PATCH');
        $this->assertCount(1, $patchCompiled);

        // DELETE has 5 routes → 1 chunk
        $deleteCompiled = $ref->invoke($this->router, 'DELETE');
        $this->assertCount(1, $deleteCompiled);

        // QUERY has 3 routes → 1 chunk
        $queryCompiled = $ref->invoke($this->router, 'QUERY');
        $this->assertCount(1, $queryCompiled);
    }

    public function testRoutePriorityPreserved()
    {
        // First registered route should match before similar ones
        $result = $this->invokeMatchRoute('GET', 'languages');
        $this->assertEquals('LanguagesApiController@index', $result['handler']);
    }

    // ========================================
    // Helper
    // ========================================

    private function invokeMatchRoute($method, $uri)
    {
        $ref = new ReflectionMethod($this->router, 'matchRoute');
        $ref->setAccessible(true);
        return $ref->invoke($this->router, $method, $uri);
    }
}
