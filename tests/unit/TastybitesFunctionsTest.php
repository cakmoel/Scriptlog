<?php
/**
 * TastybitesFunctionsTest
 *
 * Unit tests for get_download_page_data() in public/themes/tastybites/functions.php.
 *
 * Validates: Requirements 4.1, 4.2, 4.3, 4.5, 10.3
 *
 * @category   UnitTests
 * @version    1.0.0
 * @since      2025
 */

use PHPUnit\Framework\TestCase;

// Define SCRIPTLOG so functions.php passes its security guard
if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', hash_hmac('sha256', 'test', 'test'));
}

// Define DS if not already defined
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

require_once __DIR__ . '/../../public/themes/tastybites/functions.php';

/**
 * Unit tests for the get_download_page_data() helper in tastybites/functions.php.
 */
class TastybitesFunctionsTest extends TestCase
{
    // -----------------------------------------------------------------------
    // 4.1 — Function existence
    // -----------------------------------------------------------------------

    /**
     * Req 4.1 — function_exists() guard ensures the function is defined after
     * including functions.php.
     */
    public function testFunctionExists(): void
    {
        $this->assertTrue(
            function_exists('get_download_page_data'),
            'get_download_page_data() must be defined after including tastybites/functions.php'
        );
    }

    // -----------------------------------------------------------------------
    // 4.2 — Empty / null identifier returns error immediately
    // -----------------------------------------------------------------------

    /**
     * Req 4.2 — empty string should return the "Invalid download identifier" error.
     */
    public function testEmptyStringReturnsError(): void
    {
        $result = get_download_page_data('');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Invalid download identifier', $result['error']);
    }

    /**
     * Req 4.2 — null should also return the "Invalid download identifier" error
     * because empty(null) is true.
     */
    public function testNullReturnsError(): void
    {
        $result = get_download_page_data(null);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
        $this->assertSame('Invalid download identifier', $result['error']);
    }

    // -----------------------------------------------------------------------
    // 4.3 — Missing required classes returns "Download system not available"
    // -----------------------------------------------------------------------

    /**
     * Req 4.3 — when the DownloadController class (or any sibling dependency)
     * does not exist the function must return ['error' => 'Download system not available'].
     *
     * Because PHP does not allow undefining already-loaded classes, we test this
     * behaviour by spawning a fresh PHP child process that includes only the
     * functions file (without any application bootstrap) and calls
     * get_download_page_data() with a non-empty identifier.  In that process
     * DownloadController, DownloadService, DownloadModel, and MediaDao are all
     * absent, so the class_exists() guard in the function fires.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testMissingClassesReturnsError(): void
    {
        // Build the PHP snippet to run in isolation
        $functionsFile = realpath(__DIR__ . '/../../public/themes/tastybites/functions.php');
        $snippet = <<<'PHP'
<?php
define('SCRIPTLOG', 'test');
define('DS', DIRECTORY_SEPARATOR);
// Deliberately do NOT load any application classes so that
// DownloadController, DownloadService, DownloadModel, MediaDao are absent.
require_once %s;
$result = get_download_page_data('some-non-empty-identifier');
echo json_encode($result);
PHP;

        $snippet = sprintf($snippet, var_export($functionsFile, true));

        // Write to a temp file and execute it
        $tmpFile = tempnam(sys_get_temp_dir(), 'tb_test_') . '.php';
        file_put_contents($tmpFile, $snippet);

        $output = shell_exec('php ' . escapeshellarg($tmpFile) . ' 2>/dev/null');
        @unlink($tmpFile);

        $this->assertNotNull($output, 'PHP subprocess produced no output');

        $decoded = json_decode(trim($output), true);

        $this->assertIsArray($decoded, 'Subprocess must return a JSON-encoded array; got: ' . $output);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertSame('Download system not available', $decoded['error']);
    }

    // -----------------------------------------------------------------------
    // 4.5 — Exception from controller returns "Unable to retrieve download information"
    // -----------------------------------------------------------------------

    /**
     * Req 4.5 — when DownloadController::getDownloadPage() throws an exception,
     * the function must catch it, call error_log(), and return
     * ['error' => 'Unable to retrieve download information'].
     *
     * We define stub classes in this process so that class_exists() passes,
     * then make the controller throw to exercise the try/catch block.
     *
     * NOTE: Because get_download_page_data() is already loaded and uses
     * new DownloadController(...) directly, we define the stub classes before
     * the function is called. The function_exists() guard prevents re-declaration
     * of the helper; the class stubs are new (they did not exist at include time).
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExceptionReturnsError(): void
    {
        // In the separate process, define stub classes then call the function.
        $functionsFile = realpath(__DIR__ . '/../../public/themes/tastybites/functions.php');
        $snippet = <<<'PHP'
<?php
define('SCRIPTLOG', 'test');
define('DS', DIRECTORY_SEPARATOR);

// Stub DownloadModel — does nothing
class DownloadModel {}
// Stub MediaDao — does nothing
class MediaDao {}
// Stub DownloadService — does nothing
class DownloadService {
    public function __construct(DownloadModel $m, MediaDao $d) {}
}
// Stub DownloadController — always throws
class DownloadController {
    public function __construct(DownloadService $s) {}
    public function getDownloadPage($identifier) {
        throw new \Exception('Stub exception for testing');
    }
}

require_once %s;

$result = get_download_page_data('some-valid-identifier');
echo json_encode($result);
PHP;

        $snippet = sprintf($snippet, var_export($functionsFile, true));

        $tmpFile = tempnam(sys_get_temp_dir(), 'tb_test_') . '.php';
        file_put_contents($tmpFile, $snippet);

        $output = shell_exec('php ' . escapeshellarg($tmpFile) . ' 2>/dev/null');
        @unlink($tmpFile);

        $this->assertNotNull($output, 'PHP subprocess produced no output');

        $decoded = json_decode(trim($output), true);

        $this->assertIsArray($decoded, 'Subprocess must return a JSON-encoded array; got: ' . $output);
        $this->assertArrayHasKey('error', $decoded);
        $this->assertSame('Unable to retrieve download information', $decoded['error']);
    }

    // -----------------------------------------------------------------------
    // 10.3 — expired flag causes hint paragraph to appear in rendered template
    // -----------------------------------------------------------------------

    /**
     * Req 10.3 — when $downloadPageData['expired'] is true the download.php
     * template must render the "download link has expired" hint paragraph.
     *
     * The download.php template's PHP preamble calls is_permalink_enabled() and
     * get_download_page_data(), both of which require an application bootstrap.
     * To avoid a database connection we run the assertion in a subprocess that
     * stubs all required utility functions before including the template.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testExpiredFlagRendered(): void
    {
        $downloadPhpFile  = realpath(__DIR__ . '/../../public/themes/tastybites/download.php');
        $functionsFile    = realpath(__DIR__ . '/../../public/themes/tastybites/functions.php');

        $snippet = <<<'PHP'
<?php
// Security constant required by both files
define('SCRIPTLOG', 'test');
define('DS', DIRECTORY_SEPARATOR);

// Stub utility functions that the template's PHP preamble calls so that
// no database connection or application bootstrap is needed.
function is_permalink_enabled() { return 'no'; }
function safe_html($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function get_download_page_data($id) {
    return [
        'error'   => 'Download link has expired',
        'expired' => true,
    ];
}

// Stub the functions.php guard — functions are already defined above
define('_FUNCTIONS_STUBBED', true);

// Simulate an empty GET / GLOBALS so the identifier resolves to ''
$_GET = [];
$GLOBALS['download_identifier'] = '';

// Capture template output
ob_start();
require %s;
$html = ob_get_clean();

echo $html;
PHP;

        $snippet = sprintf($snippet, var_export($downloadPhpFile, true));

        $tmpFile = tempnam(sys_get_temp_dir(), 'tb_tmpl_') . '.php';
        file_put_contents($tmpFile, $snippet);

        $html = shell_exec('php ' . escapeshellarg($tmpFile) . ' 2>/dev/null');
        @unlink($tmpFile);

        $this->assertNotNull($html, 'PHP subprocess produced no output when rendering download.php');
        $this->assertNotEmpty(trim($html), 'Rendered template output must not be empty');

        // The expired hint paragraph must be present
        $this->assertStringContainsString(
            'expired',
            strtolower($html),
            'Rendered HTML must contain the expired hint paragraph when expired === true'
        );

        // The design doc specifies this exact wording
        $this->assertStringContainsString(
            'download link has expired',
            strtolower($html),
            'Rendered HTML must contain "download link has expired" wording'
        );

        // The success block (panel with download button) must NOT appear
        $this->assertStringNotContainsString(
            'btn btn-lg btn-primary',
            $html,
            'The success block must not be rendered when an error is present'
        );
    }
}
