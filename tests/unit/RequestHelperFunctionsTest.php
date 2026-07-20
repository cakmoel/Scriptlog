<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class RequestHelperFunctionsTest extends TestCase
{
    private $originalServer;

    protected function setUp(): void
    {
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    public function testIsHtmxRequestReturnsTrueWhenHeaderSet(): void
    {
        $_SERVER['HTTP_HX_REQUEST'] = 'true';
        $this->assertTrue(is_htmx_request());
    }

    public function testIsHtmxRequestReturnsFalseWhenHeaderMissing(): void
    {
        unset($_SERVER['HTTP_HX_REQUEST']);
        $this->assertFalse(is_htmx_request());
    }

    public function testIsHtmxRequestReturnsFalseWhenHeaderNotTrue(): void
    {
        $_SERVER['HTTP_HX_REQUEST'] = 'false';
        $this->assertFalse(is_htmx_request());
    }

    public function testHtmxTargetReturnsValue(): void
    {
        $_SERVER['HTTP_HX_TARGET'] = 'content-area';
        $this->assertEquals('content-area', htmx_target());
    }

    public function testHtmxTargetReturnsNullWhenNotSet(): void
    {
        unset($_SERVER['HTTP_HX_TARGET']);
        $this->assertNull(htmx_target());
    }

    public function testHtmxTriggerReturnsValue(): void
    {
        $_SERVER['HTTP_HX_TRIGGER'] = 'search-btn';
        $this->assertEquals('search-btn', htmx_trigger());
    }

    public function testHtmxTriggerReturnsNullWhenNotSet(): void
    {
        unset($_SERVER['HTTP_HX_TRIGGER']);
        $this->assertNull(htmx_trigger());
    }

    public function testRenderHtmxFragmentSetsContentTypeHeader(): void
    {
        $this->markTestSkipped('render_htmx_fragment requires database connection for theme_identifier()');
    }

    public function testRenderHtmxFragmentReturns500ForMissingFragment(): void
    {
        $this->markTestSkipped('render_htmx_fragment requires database connection for theme_identifier()');
    }
}
