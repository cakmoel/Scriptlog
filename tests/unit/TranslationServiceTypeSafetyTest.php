<?php

defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

/**
 * TranslationService Data-Handling Tests
 *
 * Guards the data-handling rules of Scriptlog\Service\TranslationService:
 * is_html defaults to boolean false when omitted (the API controllers pass
 * an explicit 1/0), an empty translation value is accepted as long as the
 * key is present, and importFromArray() counts only newly created entries
 * (updates are skipped via continue).
 */
class TranslationServiceTypeSafetyTest extends TestCase
{
    private $source;

    protected function setUp(): void
    {
        $this->source = file_get_contents(__DIR__ . '/../../src/lib/service/TranslationService.php');
    }

    public function testCreateTranslationDefaultsIsHtmlToBooleanFalse(): void
    {
        $this->assertStringContainsString("'is_html' => \$data['is_html'] ?? false,", $this->source);
        $this->assertStringNotContainsString("'is_html' => (int)(\$data['is_html'] ?? 0),", $this->source);
    }

    public function testUpdateTranslationDefaultsIsHtmlFromExisting(): void
    {
        $this->assertStringContainsString("'is_html' => \$data['is_html'] ?? \$translation['is_html'],", $this->source);
        $this->assertStringNotContainsString("'is_html' => (int)(\$data['is_html'] ?? \$translation['is_html']),", $this->source);
    }

    public function testValidateTranslationDataRejectsEmptyStringValue(): void
    {
        $this->assertStringContainsString("if (!isset(\$data['translation_value']) || trim((string) \$data['translation_value']) === '') {", $this->source);
    }

    public function testImportFromArrayCountsCreatedAndUpdatedEntries(): void
    {
        $this->assertStringContainsString(
            "'translation_value' => \$value," . "\n" .
            "                ]);",
            $this->source
        );
        $this->assertStringContainsString("            \$imported++;", $this->source);
        $this->assertStringContainsString("                continue;", $this->source);
    }
}
