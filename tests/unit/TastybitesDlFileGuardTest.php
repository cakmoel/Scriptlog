<?php
/**
 * TastybitesDlFileGuardTest
 *
 * Guard and 400 tests for public/themes/tastybites/download_file.php
 *
 * Validates: Requirements 1.2, 3.4
 *
 * @category UnitTests
 * @version  1.0
 * @since    1.0
 */

use PHPUnit\Framework\TestCase;

class TastybitesDlFileGuardTest extends TestCase
{
    /** Absolute path to the file under test */
    private string $filePath;

    /** Raw source code of the file under test */
    private string $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filePath = realpath(__DIR__ . '/../../public/themes/tastybites/download_file.php');

        $this->assertNotFalse(
            $this->filePath,
            'download_file.php must exist at public/themes/tastybites/download_file.php'
        );

        $this->source = file_get_contents($this->filePath);

        $this->assertNotFalse(
            $this->source,
            'Unable to read download_file.php source'
        );
    }

    /**
     * Requirement 1.2 — the handler must open with the SCRIPTLOG guard.
     *
     * Reads download_file.php source and asserts it contains the canonical
     * direct-access guard pattern used throughout this project.
     *
     * Validates: Requirements 1.2
     */
    public function testScriptlogGuardPresent(): void
    {
        $this->assertStringContainsString(
            "defined('SCRIPTLOG') || die",
            $this->source,
            "download_file.php must contain the SCRIPTLOG direct-access guard: defined('SCRIPTLOG') || die"
        );
    }

    /**
     * Requirement 3.4 — when the resolved identifier is empty the handler must
     * respond with HTTP 400 and stop execution.
     *
     * Inspects the source to confirm:
     *  1. http_response_code(400) is called, AND
     *  2. the call is preceded by an empty-identifier check (empty($identifier)),
     *     ensuring the 400 response is tied to the identifier guard.
     *
     * Validates: Requirements 3.4
     */
    public function testEmptyIdentifierReturns400(): void
    {
        // Assert that the source contains the 400 response call
        $this->assertStringContainsString(
            'http_response_code(400)',
            $this->source,
            "download_file.php must call http_response_code(400) when the identifier is empty"
        );

        // Assert the empty-identifier check is present
        $this->assertStringContainsString(
            'empty($identifier)',
            $this->source,
            "download_file.php must check empty(\$identifier) before sending the 400 response"
        );

        // Assert execution is halted after the 400 response (exit is present)
        $this->assertStringContainsString(
            'exit',
            $this->source,
            "download_file.php must call exit after http_response_code(400) to stop further execution"
        );

        // Assert the empty-identifier check appears before http_response_code(400) in the source
        $posEmpty = strpos($this->source, 'empty($identifier)');
        $pos400   = strpos($this->source, 'http_response_code(400)');

        $this->assertLessThan(
            $pos400,
            $posEmpty,
            "The empty(\$identifier) check must appear before http_response_code(400) in the source"
        );
    }
}
