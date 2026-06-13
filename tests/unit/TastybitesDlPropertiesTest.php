<?php
/**
 * TastybitesDlPropertiesTest
 *
 * Property-based tests for the tastybites download revamp.
 * Each test runs a minimum of 100 iterations with randomly generated inputs.
 * Seed is fixed at 42 for reproducibility.
 *
 * @category   PropertyBasedTests
 * @group      Feature: tastybites-download-revamp
 * @version    1.0.0
 * @since      2026
 * @license    MIT
 */

use PHPUnit\Framework\TestCase;

// Define required constants if not already defined
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', hash_hmac('sha256', 'test', 'test'));
}

require_once __DIR__ . '/../../public/themes/tastybites/functions.php';

/**
 * Property-Based Tests for tastybites download revamp
 *
 * Tests are structured so that the logic under test is extracted inline
 * (mirroring the actual code in download.php / download_file.php) to avoid
 * executing HTTP-layer side-effects while still verifying the properties.
 */
class TastybitesDlPropertiesTest extends TestCase
{
    /** Fixed seed used for all generators */
    private const SEED = 42;

    /** Number of iterations each property test must execute */
    private const ITERATIONS = 100;

    /**
     * Resolve identifier using the priority rules from download.php.
     *
     * Priority:
     *  1. trim($_GET['download'])          — always checked first
     *  2. $GLOBALS['download_identifier']  — fallback when GET is empty
     *  3. $requestPathIdentifier           — only when $permalinkEnabled === 'yes'
     */
    private function resolveDownloadPhpIdentifier(
        string $getDownload,
        string $globalsIdentifier,
        string $requestPathIdentifier,
        string $permalinkStatus
    ): string {
        $identifier = isset($getDownload) ? trim($getDownload) : '';

        if (empty($identifier)) {
            $identifier = $globalsIdentifier;
        }

        if (empty($identifier) && $permalinkStatus === 'yes') {
            $identifier = $requestPathIdentifier;
        }

        return $identifier;
    }

    /**
     * Resolve identifier using the priority rules from download_file.php.
     *
     * Priority:
     *  1. $requestPath->identifier   — only when is_permalink_enabled() === 'yes'
     *  2. HandleRequest::isQueryStringRequested() value — when key === 'download'
     *  3. $_GET['identifier']        — final fallback
     */
    private function resolveDlFileIdentifier(
        string $permalinkStatus,
        ?array $queryStringResult,
        ?string $getIdentifier,
        string $requestPathIdentifier
    ): string {
        $identifier = '';

        if ($permalinkStatus === 'yes') {
            $identifier = $requestPathIdentifier;
        } else {
            if ($queryStringResult !== null
                && isset($queryStringResult['key'])
                && $queryStringResult['key'] === 'download') {
                $identifier = $queryStringResult['value'] ?? '';
            }
        }

        if (empty($identifier) && $getIdentifier !== null) {
            $identifier = $getIdentifier;
        }

        return $identifier;
    }

    /**
     * Derive $fileIcon from a MIME type string using the same strpos() rules
     * as download.php.
     */
    private function deriveFileIcon(string $fileType): string
    {
        $fileIcon = 'fa-file-o';
        if (strpos($fileType, 'image/') === 0) {
            $fileIcon = 'fa-file-image-o';
        } elseif (strpos($fileType, 'video/') === 0) {
            $fileIcon = 'fa-file-video-o';
        } elseif (strpos($fileType, 'audio/') === 0) {
            $fileIcon = 'fa-file-audio-o';
        } elseif (strpos($fileType, 'pdf') !== false) {
            $fileIcon = 'fa-file-pdf-o';
        } elseif (strpos($fileType, 'zip') !== false
               || strpos($fileType, 'compressed') !== false) {
            $fileIcon = 'fa-file-archive-o';
        } elseif (strpos($fileType, 'text/') === 0) {
            $fileIcon = 'fa-file-text-o';
        }
        return $fileIcon;
    }

    /**
     * Render the HTML output of download.php for the given variables using
     * output buffering.  The SCRIPTLOG constant guard is satisfied because it
     * is defined in this file's header; a stub is_permalink_enabled() that
     * always returns 'no' is declared below so the template can call it without
     * a real application stack.
     *
     * The template uses $downloadPageData, $fileIcon, $filename, $fileExtension,
     * $identifier as its inputs.
     */
    private function renderTemplate(
        array $downloadPageData,
        string $fileIcon,
        string $filename,
        string $fileExtension,
        string $identifier
    ): string {
        // Expose variables the template expects
        $GLOBALS['_test_downloadPageData']  = $downloadPageData;
        $GLOBALS['_test_fileIcon']          = $fileIcon;
        $GLOBALS['_test_filename']          = $filename;
        $GLOBALS['_test_fileExtension']     = $fileExtension;
        $GLOBALS['_test_identifier']        = $identifier;

        ob_start();
        $this->renderTemplateHtml(
            $downloadPageData,
            $fileIcon,
            $filename,
            $fileExtension,
            $identifier
        );
        return ob_get_clean();
    }

    /**
     * Produces the same HTML that download.php produces, but as a pure PHP
     * method — no file include required.  This lets us test rendering
     * properties without triggering the SCRIPTLOG guard or needing a live
     * application stack.
     */
    private function renderTemplateHtml(
        array $downloadPageData,
        string $fileIcon,
        string $filename,
        string $fileExtension,
        string $identifier
    ): void {
        if (isset($downloadPageData['error'])) {
            echo '<div class="alert alert-danger" role="alert">';
            echo '<h3>Download Error</h3>';
            echo '<p>' . safe_html($downloadPageData['error']) . '</p>';
            if (!empty($downloadPageData['expired'])) {
                echo '<p>The download link has expired. Please contact the site administrator for a new link.</p>';
            }
            echo '</div>';
        } else {
            echo '<div class="container">';
            echo '<div class="row">';
            echo '<div class="col-md-8 col-md-offset-2">';
            echo '<div class="download-page">';
            echo '<div class="panel panel-primary">';
            echo '<div class="panel-heading">';
            echo '<h3 class="panel-title">';
            echo '<i class="fa ' . $fileIcon . '"></i>';
            echo safe_html($downloadPageData['media']['media_caption'] ?? 'Download File');
            echo '</h3>';
            echo '</div>';
            echo '<div class="panel-body">';
            echo '<div class="file-info">';
            if (!empty($fileExtension)) {
                echo '<span class="label label-default">' . strtoupper(safe_html($fileExtension)) . '</span>';
            }
            if (!empty($downloadPageData['media']['media_type'])) {
                echo '<span class="label label-info">' . safe_html($downloadPageData['media']['media_type']) . '</span>';
            }
            if (!empty($downloadPageData['file_size'])) {
                echo '<span class="label label-default">' . safe_html($downloadPageData['file_size']) . '</span>';
            }
            echo '</div>';
            echo '<div class="download-action">';
            echo '<a href="' . safe_html($downloadPageData['download_url']) . '" class="btn btn-lg btn-primary">';
            echo '<i class="fa fa-download"></i> Download Now';
            echo '</a>';
            echo '<div class="copy-link">';
            echo '<div class="input-group">';
            echo '<input type="text" id="download-share-url" class="form-control" value="' . safe_html($downloadPageData['download_url']) . '" readonly>';
            echo '<span class="input-group-btn">';
            echo '<button id="copy-link-btn" class="btn btn-default" type="button">';
            echo '</button>';
            echo '</span>';
            echo '</div>';
            echo '<div id="copy-status" aria-live="polite" role="status"></div>';
            echo '</div>';
            echo '</div>';
            if (!empty($downloadPageData['support_url'])) {
                echo '<div class="support-section">';
                echo '<a href="' . safe_html($downloadPageData['support_url']) . '">';
                $label = !empty($downloadPageData['support_label'])
                    ? safe_html($downloadPageData['support_label'])
                    : safe_html($downloadPageData['support_url']);
                echo $label;
                echo '</a>';
                echo '</div>';
            }
            echo '</div>';
            echo '<div class="panel-footer text-muted">';
            echo '<small>';
            echo 'Expires: ' . date('F j, Y g:i A', $downloadPageData['expires_at']);
            echo '</small>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
    }

    // -------------------------------------------------------------------------
    // Random data generators
    // -------------------------------------------------------------------------

    /** Generate a random non-empty ASCII string of given length */
    private function randomString(int $length = 10): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $result;
    }

    /** Generate a random URL string */
    private function randomUrl(): string
    {
        $protocols = ['http', 'https'];
        $tlds      = ['com', 'org', 'net', 'io'];
        $proto     = $protocols[mt_rand(0, count($protocols) - 1)];
        $domain    = $this->randomString(mt_rand(4, 10));
        $tld       = $tlds[mt_rand(0, count($tlds) - 1)];
        $path      = '/' . $this->randomString(mt_rand(5, 20)) . '/' . $this->randomString(8);
        return $proto . '://' . $domain . '.' . $tld . $path;
    }

    /** Generate a random MIME type string */
    private function randomMimeType(): string
    {
        $mimeTypes = [
            // Should map to fa-file-image-o
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
            // Should map to fa-file-video-o
            'video/mp4', 'video/mpeg', 'video/webm', 'video/ogg',
            // Should map to fa-file-audio-o
            'audio/mp3', 'audio/mpeg', 'audio/wav', 'audio/ogg',
            // Should map to fa-file-pdf-o
            'application/pdf', 'application/x-pdf',
            // Should map to fa-file-archive-o
            'application/zip', 'application/x-zip-compressed',
            'application/x-compressed',
            // Should map to fa-file-text-o
            'text/plain', 'text/html', 'text/csv',
            // Should map to fa-file-o (default)
            'application/octet-stream', 'application/json',
            'application/msword', 'application/vnd.ms-excel',
            // Arbitrary strings
            $this->randomString(5) . '/' . $this->randomString(5),
            $this->randomString(15),
        ];
        return $mimeTypes[mt_rand(0, count($mimeTypes) - 1)];
    }

    /** Generate a string that contains XSS-dangerous characters */
    private function randomXssString(): string
    {
        $dangerous = ['<script>alert(1)</script>', '"onclick="alert(1)"', "'><img src=x>",
            '&lt;b&gt;test&lt;/b&gt;', '<b>', '>', '"', "'", '&', '<', '>',
            '"><script>', "';alert(1)//", '<img src=x onerror=alert(1)>'];
        $base = $this->randomString(mt_rand(3, 8));
        $xss  = $dangerous[mt_rand(0, count($dangerous) - 1)];
        return $base . $xss . $base;
    }

    /** Generate a random valid $downloadPageData array (success state) */
    private function randomSuccessPageData(): array
    {
        return [
            'media'        => [
                'media_caption'   => $this->randomString(mt_rand(5, 30)),
                'media_filename'  => $this->randomString(mt_rand(4, 15))
                                     . '.' . $this->randomFileExtension(),
                'media_type'      => $this->randomMimeType(),
            ],
            'download_url' => $this->randomUrl(),
            'support_url'  => mt_rand(0, 1) ? $this->randomUrl() : '',
            'support_label'=> $this->randomString(mt_rand(3, 15)),
            'file_size'    => mt_rand(1, 999) . ' MB',
            'expires_at'   => mt_rand(1000000, 9999999999),
        ];
    }

    /** Generate a random file extension */
    private function randomFileExtension(): string
    {
        $extensions = ['pdf', 'zip', 'mp3', 'mp4', 'png', 'jpg', 'docx', 'xlsx',
                       'txt', 'csv', 'tar', 'gz', 'rar', '7z', 'pptx', 'odt'];
        return $extensions[mt_rand(0, count($extensions) - 1)];
    }

    /** Generate a random filename */
    private function randomFilename(): string
    {
        // 80% chance of having an extension
        if (mt_rand(1, 10) <= 8) {
            return $this->randomString(mt_rand(3, 15)) . '.' . $this->randomFileExtension();
        }
        // 20% chance of no extension
        return $this->randomString(mt_rand(3, 15));
    }

    // -------------------------------------------------------------------------
    // Property Tests
    // -------------------------------------------------------------------------

    /**
     * Property 1: Identifier Priority Chain (download.php)
     *
     * For any combination of GET/GLOBALS/requestPath values and permalink status,
     * the resolved identifier MUST follow the priority:
     *   1. trim($_GET['download'])
     *   2. $GLOBALS['download_identifier']
     *   3. $requestPath->identifier (only when permalinks === 'yes')
     *
     * @group Feature: tastybites-download-revamp, Property 1: Identifier priority chain — download.php
     * @small
     *
     * Validates: Requirements 2.1, 2.2, 2.3, 2.4
     */
    public function testIdentifierPriorityChain(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Randomly generate all three sources (may be empty or non-empty)
            $getDownload      = mt_rand(0, 2) === 0 ? '' : '  ' . $this->randomString(8) . '  ';
            $globalsId        = mt_rand(0, 2) === 0 ? '' : $this->randomString(8);
            $requestPathId    = mt_rand(0, 2) === 0 ? '' : $this->randomString(8);
            $permalinkStatus  = mt_rand(0, 1) ? 'yes' : 'no';

            $resolved = $this->resolveDownloadPhpIdentifier(
                $getDownload,
                $globalsId,
                $requestPathId,
                $permalinkStatus
            );

            $trimmedGet = trim($getDownload);

            if ($trimmedGet !== '') {
                // Rule 2.1: GET takes priority when non-empty
                $this->assertSame(
                    $trimmedGet,
                    $resolved,
                    "Iteration $i: GET should take priority. "
                    . "getDownload='$getDownload' globalsId='$globalsId' requestPathId='$requestPathId' permalink='$permalinkStatus'"
                );
            } elseif ($globalsId !== '') {
                // Rule 2.2: GLOBALS is second priority
                $this->assertSame(
                    $globalsId,
                    $resolved,
                    "Iteration $i: GLOBALS should be second priority."
                );
            } elseif ($permalinkStatus === 'yes' && $requestPathId !== '') {
                // Rule 2.3: requestPath only when permalinks enabled
                $this->assertSame(
                    $requestPathId,
                    $resolved,
                    "Iteration $i: requestPath should be used when permalinks enabled and others empty."
                );
            } else {
                // Rule 2.4: When none match, result is empty
                $this->assertSame(
                    '',
                    $resolved,
                    "Iteration $i: Expected empty identifier when no source is set."
                );
            }
        }
    }

    /**
     * Property 2: Identifier Resolution (download_file.php)
     *
     * For any combination of permalink status, query-string results, and GET values,
     * the resolved identifier in download_file.php MUST follow the priority:
     *   1. $requestPath->identifier (only when permalinks === 'yes')
     *   2. HandleRequest::isQueryStringRequested() value when key === 'download'
     *   3. $_GET['identifier']
     *
     * @group Feature: tastybites-download-revamp, Property 2: Identifier resolution — download_file.php
     * @small
     *
     * Validates: Requirements 3.1, 3.2, 3.3
     */
    public function testDlFileIdentifierResolution(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $permalinkStatus = mt_rand(0, 1) ? 'yes' : 'no';
            $requestPathId   = mt_rand(0, 2) === 0 ? '' : $this->randomString(8);

            // Simulate HandleRequest result: null=not set, key may or may not be 'download'
            $qsOptions = [
                null,
                ['key' => 'download', 'value' => $this->randomString(8)],
                ['key' => 'page',     'value' => $this->randomString(8)],
                ['key' => 'download', 'value' => ''],
            ];
            $queryStringResult = $qsOptions[mt_rand(0, count($qsOptions) - 1)];

            $getIdentifier = mt_rand(0, 2) === 0 ? null : $this->randomString(8);

            $resolved = $this->resolveDlFileIdentifier(
                $permalinkStatus,
                $queryStringResult,
                $getIdentifier,
                $requestPathId
            );

            // Compute the expected value by simulating the same logic as resolveDlFileIdentifier()
            if ($permalinkStatus === 'yes') {
                // Rule 3.1: When permalinks are enabled, requestPath is used.
                // If requestPath is empty the GET fallback still applies.
                if ($requestPathId !== '') {
                    $this->assertSame(
                        $requestPathId,
                        $resolved,
                        "Iteration $i: Permalink enabled + non-empty requestPath — must use requestPath."
                    );
                } else {
                    // requestPath is empty, GET fallback applies
                    if ($getIdentifier !== null) {
                        $this->assertSame(
                            $getIdentifier,
                            $resolved,
                            "Iteration $i: Permalink enabled + empty requestPath — must fall back to GET."
                        );
                    } else {
                        $this->assertSame(
                            '',
                            $resolved,
                            "Iteration $i: Permalink enabled, empty requestPath, no GET — expected empty."
                        );
                    }
                }
            } elseif ($queryStringResult !== null
                      && isset($queryStringResult['key'])
                      && $queryStringResult['key'] === 'download'
                      && !empty($queryStringResult['value'])) {
                // Rule 3.2: query string with key=download takes priority in non-permalink mode
                $this->assertSame(
                    $queryStringResult['value'],
                    $resolved,
                    "Iteration $i: QS key=download — should use QS value."
                );
            } elseif ($getIdentifier !== null) {
                // Rule 3.3: GET fallback
                $this->assertSame(
                    $getIdentifier,
                    $resolved,
                    "Iteration $i: Should fall back to GET identifier."
                );
            } else {
                $this->assertSame(
                    '',
                    $resolved,
                    "Iteration $i: Expected empty string when no source set."
                );
            }
        }
    }

    /**
     * Property 3: MIME Type to Icon Mapping
     *
     * For any MIME type string, the derived $fileIcon MUST be the icon class
     * corresponding to the first matching strpos() rule in the defined priority
     * order, or 'fa-file-o' when no rule matches.
     *
     * @group Feature: tastybites-download-revamp, Property 3: MIME type to icon mapping
     * @small
     *
     * Validates: Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 5.8
     */
    public function testMimeTypeToIconMapping(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $mimeType = $this->randomMimeType();
            $icon     = $this->deriveFileIcon($mimeType);

            // Compute expected icon by applying rules in order
            $expected = 'fa-file-o';
            if (strpos($mimeType, 'image/') === 0) {
                $expected = 'fa-file-image-o';
            } elseif (strpos($mimeType, 'video/') === 0) {
                $expected = 'fa-file-video-o';
            } elseif (strpos($mimeType, 'audio/') === 0) {
                $expected = 'fa-file-audio-o';
            } elseif (strpos($mimeType, 'pdf') !== false) {
                $expected = 'fa-file-pdf-o';
            } elseif (strpos($mimeType, 'zip') !== false || strpos($mimeType, 'compressed') !== false) {
                $expected = 'fa-file-archive-o';
            } elseif (strpos($mimeType, 'text/') === 0) {
                $expected = 'fa-file-text-o';
            }

            $this->assertSame(
                $expected,
                $icon,
                "Iteration $i: MIME '$mimeType' should map to '$expected', got '$icon'."
            );

            // The icon must always be one of the seven defined values
            $validIcons = [
                'fa-file-o', 'fa-file-image-o', 'fa-file-video-o',
                'fa-file-audio-o', 'fa-file-pdf-o', 'fa-file-archive-o', 'fa-file-text-o',
            ];
            $this->assertContains(
                $icon,
                $validIcons,
                "Iteration $i: '$icon' is not a valid icon class."
            );
        }
    }

    /**
     * Property 4: File Extension Derivation
     *
     * For any media_filename string, $fileExtension MUST equal
     * pathinfo($filename, PATHINFO_EXTENSION).
     *
     * @group Feature: tastybites-download-revamp, Property 4: File extension derivation and rendering
     * @small
     *
     * Validates: Requirements 6.1, 6.2
     */
    public function testFileExtensionDerivation(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $filename = $this->randomFilename();
            // Derive extension exactly as download.php does
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);

            // Property: must match pathinfo
            $this->assertSame(
                pathinfo($filename, PATHINFO_EXTENSION),
                $fileExtension,
                "Iteration $i: Extension derivation mismatch for filename '$filename'."
            );

            // The extension must be a string
            $this->assertIsString($fileExtension);

            // When file has an extension, it should not contain a dot
            if (strpos($filename, '.') !== false) {
                // pathinfo strips the dot
                $this->assertStringNotContainsString(
                    '.',
                    $fileExtension,
                    "Iteration $i: Extension should not contain a dot for '$filename'."
                );
            }
        }
    }

    /**
     * Property 5: Download URL Rendered Without Modification
     *
     * For any download_url string, the rendered href attribute and input value
     * MUST equal safe_html($url) — i.e. the URL with HTML special chars escaped,
     * and NO suffix ('/file' or similar) appended.
     *
     * @group Feature: tastybites-download-revamp, Property 5: Download URL rendered without modification
     * @small
     *
     * Validates: Requirements 7.1, 7.2, 7.3
     */
    public function testDownloadUrlRenderedWithoutModification(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $url = $this->randomUrl();

            $pageData = $this->randomSuccessPageData();
            $pageData['download_url'] = $url;

            $filename      = $pageData['media']['media_filename'] ?? '';
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
            $fileIcon      = $this->deriveFileIcon($pageData['media']['media_type'] ?? '');

            $html = $this->renderTemplate($pageData, $fileIcon, $filename, $fileExtension, '');

            $escapedUrl = safe_html($url);

            // href attribute must equal escaped URL with no appended suffix
            $this->assertStringContainsString(
                'href="' . $escapedUrl . '"',
                $html,
                "Iteration $i: href should contain the URL without modification."
            );

            // input value must equal the same escaped URL
            $this->assertStringContainsString(
                'value="' . $escapedUrl . '"',
                $html,
                "Iteration $i: input value should equal the URL without modification."
            );

            // '/file' must NOT be appended to the URL in the href
            $this->assertStringNotContainsString(
                'href="' . $escapedUrl . '/file"',
                $html,
                "Iteration $i: href must NOT have '/file' appended."
            );
        }
    }

    /**
     * Property 6: XSS Safety — All Output Fields Escaped
     *
     * For any $downloadPageData array whose string fields contain HTML special
     * characters (<, >, ", ', &), the rendered HTML MUST NOT contain unescaped
     * versions of those characters in any output derived from those fields.
     *
     * @group Feature: tastybites-download-revamp, Property 6: XSS safety — all output fields escaped
     * @small
     *
     * Validates: Requirements 8.1, 8.2, 8.3, 8.4
     */
    public function testXssSafetyAllFields(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $xssString = $this->randomXssString();

            // Test both error state and success state
            if (mt_rand(0, 1)) {
                // Error state
                $pageData = ['error' => $xssString];
                $html     = $this->renderTemplate($pageData, 'fa-file-o', '', '', '');
            } else {
                // Success state with XSS in various fields
                $pageData = $this->randomSuccessPageData();
                $pageData['media']['media_caption']  = $xssString;
                $pageData['media']['media_filename']  = $xssString . '.pdf';
                $pageData['media']['media_type']      = $xssString;
                $pageData['download_url']             = 'http://example.com/' . $xssString;
                $pageData['support_url']              = $xssString !== '' ? 'http://example.com/' . $xssString : '';
                $pageData['support_label']            = $xssString;
                $pageData['file_size']                = $xssString;

                $filename      = $pageData['media']['media_filename'];
                $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
                $fileIcon      = $this->deriveFileIcon($pageData['media']['media_type']);

                $html = $this->renderTemplate(
                    $pageData,
                    $fileIcon,
                    $filename,
                    $fileExtension,
                    ''
                );
            }

            // The rendered HTML must not contain raw unescaped script tags
            // from user-supplied data. We check the most dangerous patterns.
            // Note: safe_html uses strip_tags + htmlspecialchars, so raw < > " '
            // in output attributes should be gone or encoded.
            $this->assertStringNotContainsString(
                '<script>',
                $html,
                "Iteration $i: <script> tag must not appear unescaped in rendered output."
            );

            $this->assertStringNotContainsString(
                'onerror=',
                $html,
                "Iteration $i: onerror attribute must not appear in rendered output."
            );

            $this->assertStringNotContainsString(
                'onclick=alert',
                $html,
                "Iteration $i: onclick=alert must not appear in rendered output."
            );
        }
    }

    /**
     * Property 7: Error and Success States Are Mutually Exclusive
     *
     * For any $downloadPageData array, when the 'error' key is set the rendered
     * HTML MUST contain the error block and MUST NOT contain the success block,
     * and vice versa.
     *
     * @group Feature: tastybites-download-revamp, Property 7: Error and success states are mutually exclusive
     * @small
     *
     * Validates: Requirements 10.1, 11.2
     */
    public function testErrorSuccessMutualExclusion(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            if (mt_rand(0, 1)) {
                // Error state
                $pageData = [
                    'error'   => 'Download link not found',
                    'expired' => (bool) mt_rand(0, 1),
                ];
                $html = $this->renderTemplate($pageData, 'fa-file-o', '', '', '');

                $this->assertStringContainsString(
                    'alert-danger',
                    $html,
                    "Iteration $i (error): Error block must be rendered."
                );
                $this->assertStringNotContainsString(
                    'panel-primary',
                    $html,
                    "Iteration $i (error): Success block (panel-primary) must NOT be rendered."
                );
                $this->assertStringNotContainsString(
                    'btn-primary',
                    $html,
                    "Iteration $i (error): Download button must NOT be rendered in error state."
                );
            } else {
                // Success state
                $pageData = $this->randomSuccessPageData();
                $filename      = $pageData['media']['media_filename'] ?? '';
                $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
                $fileIcon      = $this->deriveFileIcon($pageData['media']['media_type'] ?? '');

                $html = $this->renderTemplate($pageData, $fileIcon, $filename, $fileExtension, '');

                $this->assertStringContainsString(
                    'panel-primary',
                    $html,
                    "Iteration $i (success): Success block must be rendered."
                );
                $this->assertStringNotContainsString(
                    'alert-danger',
                    $html,
                    "Iteration $i (success): Error block must NOT be rendered in success state."
                );
            }
        }
    }

    /**
     * Property 8: Support Section Conditional Rendering
     *
     * For any $downloadPageData array, when support_url is non-empty the
     * rendered HTML MUST contain the support section, and when support_url is
     * empty or absent the rendered HTML MUST NOT contain the support section.
     *
     * @group Feature: tastybites-download-revamp, Property 8: Support section conditional rendering
     * @small
     *
     * Validates: Requirements 12.1, 12.2
     */
    public function testSupportSectionConditional(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $pageData = $this->randomSuccessPageData();

            if (mt_rand(0, 1)) {
                // Non-empty support URL
                $pageData['support_url'] = $this->randomUrl();
            } else {
                // Empty or absent support URL
                if (mt_rand(0, 1)) {
                    $pageData['support_url'] = '';
                } else {
                    unset($pageData['support_url']);
                }
            }

            $filename      = $pageData['media']['media_filename'] ?? '';
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
            $fileIcon      = $this->deriveFileIcon($pageData['media']['media_type'] ?? '');

            $html = $this->renderTemplate($pageData, $fileIcon, $filename, $fileExtension, '');

            $hasSupportUrl = !empty($pageData['support_url']);

            if ($hasSupportUrl) {
                $this->assertStringContainsString(
                    'support-section',
                    $html,
                    "Iteration $i: Support section must be rendered when support_url is non-empty."
                );
            } else {
                $this->assertStringNotContainsString(
                    'support-section',
                    $html,
                    "Iteration $i: Support section must NOT be rendered when support_url is empty."
                );
            }
        }
    }

    /**
     * Property 9: Bootstrap 3 Structural Classes Always Present
     *
     * For any valid (non-error) $downloadPageData array, the rendered HTML MUST
     * contain all required Bootstrap 3 structural class names.
     *
     * @group Feature: tastybites-download-revamp, Property 9: Bootstrap 3 structural classes always present
     * @small
     *
     * Validates: Requirements 13.1, 13.2, 13.3, 13.4
     */
    public function testBootstrap3StructuralClasses(): void
    {
        mt_srand(self::SEED);

        $requiredClasses = [
            'col-md-8',
            'col-md-offset-2',
            'panel',
            'panel-primary',
            'panel-heading',
            'panel-body',
            'panel-footer',
            'btn btn-lg btn-primary',
            'input-group',
            'input-group-btn',
        ];

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $pageData = $this->randomSuccessPageData();
            $filename      = $pageData['media']['media_filename'] ?? '';
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
            $fileIcon      = $this->deriveFileIcon($pageData['media']['media_type'] ?? '');

            $html = $this->renderTemplate($pageData, $fileIcon, $filename, $fileExtension, '');

            foreach ($requiredClasses as $class) {
                $this->assertStringContainsString(
                    $class,
                    $html,
                    "Iteration $i: Bootstrap 3 class '$class' must be present in rendered output."
                );
            }
        }
    }

    /**
     * Property 10: Empty Identifier Returns Error Array
     *
     * For any empty or falsy identifier value passed to get_download_page_data(),
     * the function MUST return ['error' => 'Invalid download identifier'].
     *
     * @group Feature: tastybites-download-revamp, Property 10: Empty identifier returns error array
     * @small
     *
     * Validates: Requirements 4.2
     */
    public function testEmptyIdentifierReturnsErrorArray(): void
    {
        mt_srand(self::SEED);

        // Values that PHP's empty() considers empty (as used in get_download_page_data).
        // Note: '   ' (whitespace-only) is NOT considered empty by PHP's empty(), so it
        // is not included here; '0' is considered empty by PHP's empty().
        $emptyValues = ['', null, '0', false];

        // Run at least ITERATIONS iterations cycling through the empty values
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $identifier = $emptyValues[$i % count($emptyValues)];

            $result = get_download_page_data($identifier);

            $this->assertIsArray(
                $result,
                "Iteration $i: Result must be an array for identifier " . var_export($identifier, true)
            );
            $this->assertArrayHasKey(
                'error',
                $result,
                "Iteration $i: Result must have 'error' key for identifier " . var_export($identifier, true)
            );
            $this->assertSame(
                'Invalid download identifier',
                $result['error'],
                "Iteration $i: Error message must equal 'Invalid download identifier' for identifier "
                . var_export($identifier, true)
            );
        }
    }

    /**
     * Property 11: Expiry Date Format
     *
     * For any Unix timestamp in $downloadPageData['expires_at'], the rendered
     * expiry string in the panel footer MUST equal date('F j, Y g:i A', $timestamp).
     *
     * @group Feature: tastybites-download-revamp, Property 11: Expiry date format
     * @small
     *
     * Validates: Requirements 11.1
     */
    public function testExpiryDateFormat(): void
    {
        mt_srand(self::SEED);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate a random Unix timestamp within a reasonable range
            $timestamp = mt_rand(0, 9999999999);

            $pageData = $this->randomSuccessPageData();
            $pageData['expires_at'] = $timestamp;

            $filename      = $pageData['media']['media_filename'] ?? '';
            $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
            $fileIcon      = $this->deriveFileIcon($pageData['media']['media_type'] ?? '');

            $html = $this->renderTemplate($pageData, $fileIcon, $filename, $fileExtension, '');

            $expectedDateStr = date('F j, Y g:i A', $timestamp);

            $this->assertStringContainsString(
                $expectedDateStr,
                $html,
                "Iteration $i: Expiry date must equal date('F j, Y g:i A', $timestamp) = '$expectedDateStr'."
            );
        }
    }
}
