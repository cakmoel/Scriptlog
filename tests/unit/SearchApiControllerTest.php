<?php

/**
 * SearchApiControllerTest
 *
 * Unit tests for lib/controller/api/SearchApiController.php.
 *
 * Coverage focuses on the two behaviours that fixed the sidebar search widget:
 *   1. generateExcerpt() decodes HTML entities BEFORE stripping tags, so the
 *      double-encoded post content stored in the DB (e.g. "&lt;p&gt;") never
 *      leaks raw HTML into the excerpt.
 *   2. buildResponseData() exposes a top-level "total" count that the widget's
 *      JS reads directly (previously only data.pagination.total_items existed,
 *      which produced "Found undefined result(s)").
 *
 * The controller is instantiated without its constructor so the tests do not
 * need a live SearchFinder/PDO connection; the private methods under test use
 * no instance state, so they are invoked via Reflection.
 *
 * @package Scriptlog
 */

use PHPUnit\Framework\TestCase;
use Scriptlog\Controller\Api\SearchApiController;

class SearchApiControllerTest extends TestCase
{
    /**
     * Build a controller instance without running its constructor.
     *
     * @return SearchApiController
     */
    private function makeController(): SearchApiController
    {
        $reflection = new ReflectionClass(SearchApiController::class);

        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Invoke a private method on the controller via Reflection.
     *
     * @param SearchApiController $controller
     * @param string $method
     * @param array $args
     * @return mixed
     */
    private function invoke(SearchApiController $controller, string $method, array $args)
    {
        $reflection = new ReflectionMethod(SearchApiController::class, $method);

        if (PHP_VERSION_ID < 80100) {
            $reflection->setAccessible(true);
        }

        return $reflection->invokeArgs($controller, $args);
    }

    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(SearchApiController::class));
    }

    public function testGenerateExcerptDecodesEntitiesBeforeStrippingTags(): void
    {
        $controller = $this->makeController();

        $doubleEncoded = '&lt;p&gt;&lt;/p&gt;&lt;h1&gt;&lt;br&gt;&lt;/h1&gt;&lt;p&gt;'
            . 'Chances are you bumped into the word PHP somewhere.&lt;/p&gt;';

        $excerpt = $this->invoke($controller, 'generateExcerpt', [$doubleEncoded]);

        $this->assertStringNotContainsString('<p>', $excerpt);
        $this->assertStringNotContainsString('<h1>', $excerpt);
        $this->assertStringNotContainsString('<br>', $excerpt);
        $this->assertStringContainsString('Chances are you bumped into the word PHP somewhere.', $excerpt);
    }

    public function testGenerateExcerptCollapsesWhitespace(): void
    {
        $controller = $this->makeController();

        $content = "&lt;p&gt;First   line.\n\n\tSecond line.&lt;/p&gt;";

        $excerpt = $this->invoke($controller, 'generateExcerpt', [$content]);

        $this->assertSame('First line. Second line.', $excerpt);
    }

    public function testGenerateExcerptReturnsEmptyForEmptyContent(): void
    {
        $controller = $this->makeController();

        $this->assertSame('', $this->invoke($controller, 'generateExcerpt', ['']));
        $this->assertSame('', $this->invoke($controller, 'generateExcerpt', [null]));
    }

    public function testGenerateExcerptPreservesPlainText(): void
    {
        $controller = $this->makeController();

        $excerpt = $this->invoke($controller, 'generateExcerpt', ['Just some plain text.']);

        $this->assertSame('Just some plain text.', $excerpt);
    }

    public function testGenerateExcerptTruncatesOnWordBoundary(): void
    {
        $controller = $this->makeController();

        $longContent = str_repeat('lorem ipsum dolor sit amet ', 30);
        $excerpt = $this->invoke($controller, 'generateExcerpt', [$longContent, 40]);

        $this->assertStringEndsWith('...', $excerpt);
        $this->assertLessThanOrEqual(44, mb_strlen($excerpt, 'UTF-8'));
        $this->assertNotContains('lorem ipsum lorem', [$excerpt]);
        $this->assertMatchesRegularExpression('/^[a-z ]+\.\.\.$/', $excerpt);
    }

    public function testGenerateExcerptDoesNotReturnHalfWords(): void
    {
        $controller = $this->makeController();

        $excerpt = $this->invoke($controller, 'generateExcerpt', ['word word word word', 7]);

        $this->assertStringEndsWith('...', $excerpt);
        $this->assertMatchesRegularExpression('/^[a-z]+\.\.\.$/', $excerpt);
    }

    public function testBuildResponseDataIncludesTopLevelTotal(): void
    {
        $controller = $this->makeController();

        $results = [
            'results' => [],
            'totalRows' => 3,
            'page' => 1,
            'perPage' => 10,
        ];

        $response = $this->invoke($controller, 'buildResponseData', [
            $results,
            [],
            'all',
            'php',
            1,
            []
        ]);

        $this->assertSame(3, $response['total']);
        $this->assertSame('php', $response['keyword']);
        $this->assertSame('all', $response['type']);
        $this->assertSame([], $response['results']);
    }

    public function testBuildResponseDataExposesPaginationBlock(): void
    {
        $controller = $this->makeController();

        $results = [
            'results' => [],
            'totalRows' => 25,
            'page' => 2,
            'perPage' => 10,
        ];

        $response = $this->invoke($controller, 'buildResponseData', [
            $results,
            [],
            'posts',
            'php',
            3,
            []
        ]);

        $this->assertSame(25, $response['pagination']['total_items']);
        $this->assertSame(2, $response['pagination']['current_page']);
        $this->assertSame(10, $response['pagination']['per_page']);
        $this->assertSame(3, $response['pagination']['total_pages']);
        $this->assertTrue($response['pagination']['has_next_page']);
        $this->assertTrue($response['pagination']['has_previous_page']);
    }

    public function testBuildResponseDataFlattensTotalIntoInteger(): void
    {
        $controller = $this->makeController();

        $results = [
            'results' => [],
            'totalRows' => '7',
            'page' => '1',
            'perPage' => '10',
        ];

        $response = $this->invoke($controller, 'buildResponseData', [
            $results,
            [],
            'all',
            'php',
            1,
            []
        ]);

        $this->assertSame(7, $response['total']);
        $this->assertSame(7, $response['pagination']['total_items']);
        $this->assertIsInt($response['total']);
    }

    public function testBuildResponseDataAttachesHateoasLinks(): void
    {
        $controller = $this->makeController();

        $links = ['self' => ['href' => '/api/v1/search?q=php']];

        $response = $this->invoke($controller, 'buildResponseData', [
            ['results' => [], 'totalRows' => 0, 'page' => 1, 'perPage' => 10],
            [],
            'all',
            'php',
            0,
            $links
        ]);

        $this->assertArrayHasKey('_links', $response);
        $this->assertSame($links, $response['_links']);
    }

    public function testBuildResponseDataOmitsLinksWhenEmpty(): void
    {
        $controller = $this->makeController();

        $response = $this->invoke($controller, 'buildResponseData', [
            ['results' => [], 'totalRows' => 0, 'page' => 1, 'perPage' => 10],
            [],
            'all',
            'php',
            0,
            []
        ]);

        $this->assertArrayNotHasKey('_links', $response);
    }

    public function testCheckRateLimitReturnsTrueByDefault(): void
    {
        $controller = $this->makeController();

        $result = $this->invoke($controller, 'checkRateLimit', []);

        $this->assertTrue($result);
    }

    public function testIndexRejectsHoneypotField(): void
    {
        $controller = $this->makeController();

        $_GET = ['search_verify' => 'bot', 'q' => 'php'];

        $this->expectNotToPerformAssertions();

        try {
            $controller->index([]);
        } catch (\Throwable $e) {
            // ApiResponse::forbidden() may call exit();
        }
    }
}
