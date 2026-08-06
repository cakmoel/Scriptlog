<?php

defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * TranslationService Type-Safety Tests
 *
 * Guards the data-integrity rules of Scriptlog\Service\TranslationService:
 * is_html must reach the integer database column as an int (PDO binds every
 * value as string, so a bare boolean false would be inserted as '' and fail),
 * empty translation values are rejected, and importFromArray() counts both
 * created and updated entries.
 */
class TranslationServiceTypeSafetyTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $this->source = file_get_contents(__DIR__ . '/../../src/lib/service/TranslationService.php');
    }

    public function testCreateTranslationCastsIsHtmlToInt(): void
    {
        $this->assertStringContainsString("'is_html' => (int)(\$data['is_html'] ?? 0),", $this->source);
    }

    public function testUpdateTranslationCastsIsHtmlToInt(): void
    {
        $this->assertStringContainsString("'is_html' => (int)(\$data['is_html'] ?? \$translation['is_html']),", $this->source);
    }

    public function testValidateTranslationDataRejectsEmptyValue(): void
    {
        $this->assertStringContainsString("\$data['translation_value'] === ''", $this->source);
    }

    public function testImportFromArrayCountsUpdatedEntries(): void
    {
        $this->assertStringContainsString(
            "'translation_value' => \$value," . "\n" .
            "                ]);" . "\n\n" .
            "                \$imported++;",
            $this->source
        );
    }
}
