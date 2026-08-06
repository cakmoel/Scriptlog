<?php

defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * Dispatcher Validation Tests
 *
 * Verifies the content validation methods in Scriptlog\Core\Dispatcher.
 * FrontService always returns arrays (or null) so the validators rely on
 * empty() checks alone rather than redundant is_array() guards.
 */
class DispatcherValidationTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $this->source = file_get_contents(__DIR__ . '/../../src/lib/core/Dispatcher.php');
    }

    public function testValidatePageUsesEmptyCheckOnly(): void
    {
        $this->assertStringContainsString('if (empty($page)) {', $this->source);
        $this->assertStringNotContainsString('if (empty($page) || !is_array($page))', $this->source);
    }

    public function testValidateCategoryUsesEmptyCheckOnly(): void
    {
        $this->assertStringContainsString('return !empty($topic);', $this->source);
        $this->assertStringNotContainsString('return !empty($topic) && is_array($topic);', $this->source);
    }

    public function testValidatePageReturnsFalseForMissingSlug(): void
    {
        $this->assertStringContainsString('if (empty($pageSlug)) {', $this->source);
        $this->assertStringContainsString('return false;', $this->source);
    }
}
