<?php
/**
 * Options Handler Test
 *
 * Tests the OPTIONS preflight handler in api/index.php and the 404
 * behaviour of ApiRouter::dispatch for unmatched routes.
 *
 * The OPTIONS handler always answers 204 with an Allow header computed from
 * the route table. For an unknown path it falls back to the full method
 * list plus OPTIONS. Only non-OPTIONS dispatch of an unknown path returns
 * 404.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';

class OptionsHandlerTest extends ApiTestCase
{
    /**
     * OPTIONS on /posts advertises GET and POST.
     *
     * @return void
     */
    public function testOptionsOnPostsReturnsAllowHeader()
    {
        $response = $this->httpRequest('OPTIONS', '/posts');

        $this->assertSame(204, $response['code']);

        $allow = $response['headers']['allow'] ?? '';
        $this->assertNotSame('', $allow, 'Allow header missing');
        $this->assertMatchesRegularExpression('/GET/i', $allow);
        $this->assertMatchesRegularExpression('/POST/i', $allow);
    }

    /**
     * OPTIONS on /posts/{id} advertises the single-resource methods.
     *
     * @return void
     */
    public function testOptionsOnPostsIdReturnsAllowHeader()
    {
        $response = $this->httpRequest('OPTIONS', '/posts/1');

        $this->assertSame(204, $response['code']);

        $allow = $response['headers']['allow'] ?? '';
        $this->assertMatchesRegularExpression('/GET/i', $allow);
        $this->assertMatchesRegularExpression('/PUT/i', $allow);
        $this->assertMatchesRegularExpression('/PATCH/i', $allow);
        $this->assertMatchesRegularExpression('/DELETE/i', $allow);
    }

    /**
     * OPTIONS on /languages/{code} advertises the language methods.
     *
     * @return void
     */
    public function testOptionsOnLanguagesCodeReturnsAllow()
    {
        $response = $this->httpRequest('OPTIONS', '/languages/en');

        $this->assertSame(204, $response['code']);

        $allow = $response['headers']['allow'] ?? '';
        $this->assertMatchesRegularExpression('/GET/i', $allow);
        $this->assertMatchesRegularExpression('/PUT/i', $allow);
        $this->assertMatchesRegularExpression('/PATCH/i', $allow);
        $this->assertMatchesRegularExpression('/DELETE/i', $allow);
    }

    /**
     * OPTIONS on an unknown path still answers 204 with the full method list.
     *
     * @return void
     */
    public function testOptionsOnUnknownPathReturns204()
    {
        $response = $this->httpRequest('OPTIONS', '/does-not-exist');

        $this->assertSame(204, $response['code']);

        $allow = $response['headers']['allow'] ?? '';
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'QUERY', 'OPTIONS'] as $method) {
            $this->assertMatchesRegularExpression('/' . preg_quote($method, '/') . '/i', $allow, 'Allow header missing ' . $method);
        }
    }

    /**
     * A non-OPTIONS dispatch of an unknown route returns 404.
     *
     * @return void
     */
    public function testUnknownRouteViaDispatchReturns404()
    {
        $result = $this->runRouter(
            [['method' => 'get', 'pattern' => 'posts', 'handler' => 'Scriptlog\Controller\Api\PostsApiController@index']],
            'GET',
            'does-not-exist'
        );

        $this->assertSame(404, (int)$result['code']);
    }
}
