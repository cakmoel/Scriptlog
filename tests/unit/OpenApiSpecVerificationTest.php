<?php
/**
 * OpenAPI Specification Verification Test
 *
 * Verifies that dev-docs/API_OPENAPI.yaml and dev-docs/API_OPENAPI.json are coherent
 * and correspond to the actual RESTful API implementation.
 *
 * @category   tests
 * @author    Blogware Team
 * @license   MIT
 * @version   1.0.0
 * @since     April 2026
 */

require_once __DIR__ . '/../bootstrap.php';

class OpenApiSpecVerificationTest extends PHPUnit\Framework\TestCase
{
    private string $baseDir;
    private string $yamlFile;
    private string $jsonFile;
    private string $apiIndexFile;

    private ?array $yamlSpec = null;
    private ?array $jsonSpec = null;

    protected function setUp(): void
    {
        $this->baseDir = dirname(__DIR__) . '/..';
        $this->yamlFile = $this->baseDir . '/dev-docs/API_OPENAPI.yaml';
        $this->jsonFile = $this->baseDir . '/dev-docs/API_OPENAPI.json';
        $this->apiIndexFile = $this->baseDir . '/api/index.php';

        $this->loadSpecs();
    }

    private function loadSpecs(): void
    {
        if (function_exists('yaml_parse_file')) {
            $this->yamlSpec = yaml_parse_file($this->yamlFile);
        }
        $this->jsonSpec = json_decode(file_get_contents($this->jsonFile), true);
    }

    public function testYamlFileExists(): void
    {
        $this->assertFileExists($this->yamlFile);
    }

    public function testJsonFileExists(): void
    {
        $this->assertFileExists($this->jsonFile);
    }

    public function testApiIndexFileExists(): void
    {
        $this->assertFileExists($this->apiIndexFile);
    }

    public function testYamlIsValid(): void
    {
        if (!function_exists('yaml_parse')) {
            $this->markTestSkipped('yaml_parse function not available');
            return;
        }

        $content = file_get_contents($this->yamlFile);
        $parsed = yaml_parse($content);

        $this->assertNotFalse($parsed);
        $this->assertIsArray($parsed);
    }

    public function testJsonIsValid(): void
    {
        $content = file_get_contents($this->jsonFile);
        $parsed = json_decode($content, true);

        $this->assertNotNull($parsed);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
    }

    public function testYamlAndJsonHaveOpenapiVersion(): void
    {
        $yamlVersion = $this->yamlSpec['openapi'] ?? null;
        $jsonVersion = $this->jsonSpec['openapi'] ?? null;

        $this->assertNotNull($yamlVersion);
        $this->assertNotNull($jsonVersion);
        $this->assertSame($yamlVersion, $jsonVersion);
    }

    public function testYamlAndJsonHaveSameInfo(): void
    {
        $yamlInfo = $this->yamlSpec['info'] ?? [];
        $jsonInfo = $this->jsonSpec['info'] ?? [];

        $this->assertEquals($yamlInfo['title'], $jsonInfo['title']);
        $this->assertEquals($yamlInfo['version'], $jsonInfo['version']);
    }

    public function testYamlAndJsonPathsMatch(): void
    {
        $yamlPaths = array_keys($this->yamlSpec['paths']);
        $jsonPaths = array_keys($this->jsonSpec['paths']);

        $this->assertEquals($yamlPaths, $jsonPaths);
    }

    public function testYamlAndJsonTagsMatch(): void
    {
        $yamlTags = array_column($this->yamlSpec['tags'], 'name');
        $jsonTags = array_column($this->jsonSpec['tags'], 'name');

        $this->assertEquals($yamlTags, $jsonTags);
    }

    public function testSchemaCountsMatch(): void
    {
        $yamlSchemaCount = count($this->yamlSpec['components']['schemas']);
        $jsonSchemaCount = count($this->jsonSpec['components']['schemas']);

        $this->assertEquals($yamlSchemaCount, $jsonSchemaCount);
    }

    public function testRequiredSchemasExist(): void
    {
        $schemas = $this->yamlSpec['components']['schemas'] ?? [];

        $requiredSchemas = [
            'Error',
            'SuccessResponse',
            'Post',
            'PostCreate',
            'PostUpdate',
            'Category',
            'CategoryCreate',
            'CategoryUpdate',
            'Comment',
            'CommentCreate',
            'CommentUpdate',
            'ArchiveMonth',
            'ArchiveYear',
            'Link',
            'Links',
            'Pagination',
            'ApiInfo'
        ];

        foreach ($requiredSchemas as $schema) {
            $this->assertArrayHasKey($schema, $schemas, "Schema '$schema' should exist");
        }
    }

    public function testExpectedTagsExist(): void
    {
        $tags = array_column($this->yamlSpec['tags'], 'name');

        $expectedTags = [
            'Posts',
            'Categories',
            'Comments',
            'Archives',
            'Search',
            'GDPR',
            'Languages',
            'Translations',
            'Media',
            'API Info'
        ];

        foreach ($expectedTags as $tag) {
            $this->assertContains($tag, $tags, "Tag '$tag' should exist");
        }
    }

    public function testServersAreDefined(): void
    {
        $servers = $this->yamlSpec['servers'] ?? [];

        $this->assertIsArray($servers);
        $this->assertNotEmpty($servers);
        $this->assertGreaterThanOrEqual(2, count($servers));
    }

    public function testProductionServerUrlExists(): void
    {
        $servers = $this->yamlSpec['servers'] ?? [];

        $this->assertArrayHasKey(0, $servers);
        $this->assertArrayHasKey('url', $servers[0]);
        $this->assertNotEmpty($servers[0]['url']);
    }

    public function testServerUrlsContainPlaceholderDomain(): void
    {
        $servers = $this->yamlSpec['servers'] ?? [];

        $hasPlaceholder = false;
        foreach ($servers as $server) {
            if (isset($server['url']) && 
                (strpos($server['url'], 'blogware.site') !== false || 
                 strpos($server['url'], 'localhost') !== false)) {
                $hasPlaceholder = true;
                break;
            }
        }

        $this->assertTrue($hasPlaceholder, 'Servers should contain placeholder domain for replacement');
    }

    public function testSecuritySchemesExist(): void
    {
        $securitySchemes = $this->yamlSpec['components']['securitySchemes'] ?? [];

        $this->assertNotEmpty($securitySchemes);
    }

    public function testApiKeySecuritySchemeExists(): void
    {
        $securitySchemes = $this->yamlSpec['components']['securitySchemes'] ?? [];

        $this->assertArrayHasKey('ApiKeyAuth', $securitySchemes);
    }

    public function testBearerSecuritySchemeExists(): void
    {
        $securitySchemes = $this->yamlSpec['components']['securitySchemes'] ?? [];

        $this->assertArrayHasKey('BearerAuth', $securitySchemes);
    }

    public function testYamlHasComponents(): void
    {
        $this->assertArrayHasKey('components', $this->yamlSpec);
    }

    public function testJsonHasComponents(): void
    {
        $this->assertArrayHasKey('components', $this->jsonSpec);
    }

    public function testPathsHaveGetOperations(): void
    {
        $paths = $this->yamlSpec['paths'] ?? [];
        $getOperations = 0;

        foreach ($paths as $path => $methods) {
            if (isset($methods['get'])) {
                $getOperations++;
            }
        }

        $this->assertGreaterThan(0, $getOperations, 'Should have GET operations');
    }

    public function testPathsHavePostOperations(): void
    {
        $paths = $this->yamlSpec['paths'] ?? [];
        $postOperations = 0;

        foreach ($paths as $path => $methods) {
            if (isset($methods['post'])) {
                $postOperations++;
            }
        }

        $this->assertGreaterThan(0, $postOperations, 'Should have POST operations');
    }

    public function testInfoHasTitle(): void
    {
        $this->assertArrayHasKey('title', $this->yamlSpec['info']);
        $this->assertNotEmpty($this->yamlSpec['info']['title']);
    }

    public function testInfoHasVersion(): void
    {
        $this->assertArrayHasKey('version', $this->yamlSpec['info']);
        $this->assertNotEmpty($this->yamlSpec['info']['version']);
    }

    public function testInfoHasDescription(): void
    {
        $this->assertArrayHasKey('description', $this->yamlSpec['info']);
    }

    public function testInfoHasContact(): void
    {
        $this->assertArrayHasKey('contact', $this->yamlSpec['info']);
    }

    public function testInfoHasLicense(): void
    {
        $this->assertArrayHasKey('license', $this->yamlSpec['info']);
    }

    public function testXLogoExists(): void
    {
        $this->assertArrayHasKey('x-logo', $this->yamlSpec['info']);
        $this->assertArrayHasKey('url', $this->yamlSpec['info']['x-logo']);
    }

    public function testXLogoContainsPlaceholderDomain(): void
    {
        $logoUrl = $this->yamlSpec['info']['x-logo']['url'] ?? '';

        $this->assertStringContainsString('blogware.site', $logoUrl);
    }
}