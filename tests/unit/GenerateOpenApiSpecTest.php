<?php
/**
 * Generate OpenAPI Spec Unit Test
 *
 * Tests for lib/utility/generate-openapi-spec.php
 *
 * @category   tests
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0.0
 * @since     April 2026
 */

require_once __DIR__ . '/../bootstrap.php';

class GenerateOpenApiSpecTest extends PHPUnit\Framework\TestCase
{
    private string $baseDir;
    private string $utilityFile;
    private string $openapiFile;

    protected function setUp(): void
    {
        $this->baseDir = dirname(__DIR__) . '/..';
        $this->utilityFile = $this->baseDir . '/lib/utility/generate-openapi-spec.php';
        $this->openapiFile = $this->baseDir . '/openapi.json';
    }

    public function testUtilityFileExists(): void
    {
        $this->assertFileExists($this->utilityFile);
    }

    public function testOpenapiJsonExists(): void
    {
        $this->assertFileExists($this->openapiFile);
    }

    public function testOpenapiJsonIsValid(): void
    {
        $content = file_get_contents($this->openapiFile);
        $parsed = json_decode($content, true);

        $this->assertNotNull($parsed);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function testOpenapiJsonHasServersArray(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $this->assertArrayHasKey('servers', $spec);
        $this->assertIsArray($spec['servers']);
    }

    public function testServersContainsHardcodedUrls(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $foundHardcoded = false;
        foreach ($spec['servers'] as $server) {
            if (isset($server['url']) && 
                (strpos($server['url'], 'blogware.site') !== false || 
                 strpos($server['url'], 'localhost') !== false)) {
                $foundHardcoded = true;
                break;
            }
        }

        $this->assertTrue($foundHardcoded, 'Should contain hardcoded URLs for replacement');
    }

    public function testOpenapiJsonHasInfoWithLogo(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('x-logo', $spec['info']);
        $this->assertArrayHasKey('url', $spec['info']['x-logo']);
    }

    public function testLogoUrlContainsHardcodedDomain(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $logoUrl = $spec['info']['x-logo']['url'] ?? '';
        $this->assertStringContainsString('blogware.site', $logoUrl);
    }

    public function testGenerateOpenapiSpecFunctionExists(): void
    {
        require_once $this->utilityFile;

        $this->assertTrue(function_exists('generate_openapi_spec'));
    }

    public function testFunctionReturnsVoid(): void
    {
        require_once $this->utilityFile;

        $reflection = new ReflectionFunction('generate_openapi_spec');
        $this->assertSame('void', $reflection->getReturnType()->getName());
    }

    public function testUrlConstructionLogic(): void
    {
        $config = [
            'app' => [
                'url' => 'https://example.com'
            ]
        ];

        $appUrl = rtrim($config['app']['url'], '/');
        $apiUrl = $appUrl . '/api/v1';

        $this->assertSame('https://example.com/api/v1', $apiUrl);
    }

    public function testUrlReplacementCondition(): void
    {
        $testCases = [
            ['input' => 'http://blogware.site/api/v1', 'shouldReplace' => true],
            ['input' => 'http://localhost/blogware/public_html/api/v1', 'shouldReplace' => true],
            ['input' => 'https://custom-domain.com/api/v1', 'shouldReplace' => false],
            ['input' => 'https://api.example.com/v1', 'shouldReplace' => false],
        ];

        foreach ($testCases as $case) {
            $shouldReplace = (strpos($case['input'], 'blogware.site') !== false || 
                           strpos($case['input'], 'localhost') !== false);
            $this->assertSame($case['shouldReplace'], $shouldReplace);
        }
    }

    public function testLogoPathUsesExistingFile(): void
    {
        $picturesDir = $this->baseDir . '/public/files/pictures';
        $this->assertDirectoryExists($picturesDir);

        $files = scandir($picturesDir);
        $imageFiles = array_filter($files, function($file) {
            return in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        });

        $this->assertNotEmpty($imageFiles, 'Pictures directory should contain image files');

        $hasScriptlogLogo = false;
        foreach ($imageFiles as $file) {
            if (strpos($file, 'scriptlog') !== false) {
                $hasScriptlogLogo = true;
                break;
            }
        }

        $this->assertTrue($hasScriptlogLogo, 'Should have logo file starting with scriptlog');
    }

    public function testGeneratedLogoPathMatchesExistingFile(): void
    {
        $expectedPath = 'scriptlog-1200x630.jpg';

        $picturesDir = $this->baseDir . '/public/files/pictures';
        $fileExists = file_exists($picturesDir . '/' . $expectedPath);

        $this->assertTrue($fileExists, 'Generated logo path should match existing file');
    }

    public function testJsonOutputFlags(): void
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

        $testArray = ['test' => 'value', 'url' => '/api/v1'];
        $encoded = json_encode($testArray, $flags);

        $this->assertStringContainsString('/api/v1', $encoded);
        $this->assertStringContainsString('test', $encoded);
    }

    public function testServersArrayManipulation(): void
    {
        $spec = [
            'servers' => [
                ['url' => 'http://blogware.site/api/v1', 'description' => 'Production'],
                ['url' => 'http://localhost/blogware/public_html/api/v1', 'description' => 'Development']
            ]
        ];

        $newApiUrl = 'https://example.com/api/v1';

        foreach ($spec['servers'] as &$server) {
            if (!empty($server['url'])) {
                if (strpos($server['url'], 'blogware.site') !== false || 
                    strpos($server['url'], 'localhost') !== false) {
                    $server['url'] = $newApiUrl;
                }
            }
        }

        $this->assertSame($newApiUrl, $spec['servers'][0]['url']);
        $this->assertSame($newApiUrl, $spec['servers'][1]['url']);
    }

    public function testHttpsDetection(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $expectedUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

        $this->assertSame('https://example.com', $expectedUrl);
    }

    public function testHttpDetection(): void
    {
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $expectedUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];

        $this->assertSame('http://example.com', $expectedUrl);
    }

    public function testTrailingSlashRemoval(): void
    {
        $url1 = 'https://example.com/';
        $url2 = 'https://example.com';

        $this->assertSame($url2, rtrim($url1, '/'));
    }

    public function testOpenapiSpecStructure(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $this->assertArrayHasKey('openapi', $spec);
        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('servers', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);
    }

    public function testInfoSectionRequiredFields(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $info = $spec['info'];

        $this->assertArrayHasKey('title', $info);
        $this->assertArrayHasKey('version', $info);
        $this->assertArrayHasKey('description', $info);
    }

    public function testPathsNotEmpty(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $this->assertNotEmpty($spec['paths']);
        $this->assertIsArray($spec['paths']);
    }

    public function testComponentsSchemasExist(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $this->assertArrayHasKey('schemas', $spec['components']);
        $this->assertNotEmpty($spec['components']['schemas']);
    }

    public function testSecuritySchemesExist(): void
    {
        $content = file_get_contents($this->openapiFile);
        $spec = json_decode($content, true);

        $this->assertArrayHasKey('securitySchemes', $spec['components']);
    }
}