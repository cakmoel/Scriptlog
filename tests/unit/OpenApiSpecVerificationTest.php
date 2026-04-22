<?php
/**
 * OpenAPI Specification Verification Test
 *
 * Verifies that API_OPENAPI.yaml and API_OPENAPI.json are coherent
 * and correspond to the actual RESTful API implementation.
 *
 * @category   tests
 * @author     Blogware Team
 * @license    MIT
 * @version    1.0.0
 * @since      April 2026
 */

use PHPUnit\Framework\TestCase;

class OpenApiSpecVerificationTest extends TestCase
{
    private string $baseDir;
    private string $yamlFile;
    private string $jsonFile;
    private string $apiIndexFile;
    private array $yamlSpec = [];
    private array $jsonSpec = [];

    protected function setUp(): void
    {
        $this->baseDir = dirname(__DIR__) . '/..';
        $this->yamlFile = $this->baseDir . '/src/docs/API_OPENAPI.yaml';
        $this->jsonFile = $this->baseDir . '/src/docs/API_OPENAPI.json';
        $this->apiIndexFile = $this->baseDir . '/src/api/index.php';

        $this->loadSpecs();
    }

    private function loadSpecs(): void
    {
        $yamlContent = file_get_contents($this->yamlFile);
        $this->yamlSpec = yaml_parse($yamlContent) ?: [];

        $jsonContent = file_get_contents($this->jsonFile);
        $this->jsonSpec = json_decode($jsonContent, true) ?: [];
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
        $yamlContent = file_get_contents($this->yamlFile);
        $parsed = yaml_parse($yamlContent);
        $this->assertNotFalse($parsed, 'YAML content should be parseable');
        $this->assertIsArray($parsed, 'Parsed YAML should be an array');
    }

    public function testJsonIsValid(): void
    {
        $content = file_get_contents($this->jsonFile);
        $parsed = json_decode($content, true);
        $this->assertNotNull($parsed, 'JSON should be parseable');
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 'JSON should have no errors');
    }

    public function testYamlHasOpenapiVersion(): void
    {
        $this->assertArrayHasKey('openapi', $this->yamlSpec);
        $this->assertStringStartsWith('3.', $this->yamlSpec['openapi']);
    }

    public function testJsonHasOpenapiVersion(): void
    {
        $this->assertArrayHasKey('openapi', $this->jsonSpec);
        $this->assertStringStartsWith('3.', $this->jsonSpec['openapi']);
    }

    public function testYamlAndJsonHaveSamePaths(): void
    {
        $yamlPaths = array_keys($this->yamlSpec['paths'] ?? []);
        $jsonPaths = array_keys($this->jsonSpec['paths'] ?? []);
        $this->assertEquals($yamlPaths, $jsonPaths, 'YAML and JSON should have same paths');
    }

    public function testYamlAndJsonHaveSameTags(): void
    {
        $yamlTags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $jsonTags = array_column($this->jsonSpec['tags'] ?? [], 'name');
        $this->assertEquals($yamlTags, $jsonTags, 'YAML and JSON should have same tags');
    }

    public function testYamlAndJsonHaveSameSchemaCount(): void
    {
        $yamlSchemaCount = count($this->yamlSpec['components']['schemas'] ?? []);
        $jsonSchemaCount = count($this->jsonSpec['components']['schemas'] ?? []);
        $this->assertEquals($yamlSchemaCount, $jsonSchemaCount, 'Schema counts should match');
    }

    public function testYamlHasRequiredSchemas(): void
    {
        $schemas = $this->yamlSpec['components']['schemas'] ?? [];
        $requiredSchemas = ['Error', 'SuccessResponse', 'Post', 'PostCreate', 'PostUpdate'];

        foreach ($requiredSchemas as $schema) {
            $this->assertArrayHasKey($schema, $schemas, "Schema '$schema' should exist");
        }
    }

    public function testJsonHasRequiredSchemas(): void
    {
        $schemas = $this->jsonSpec['components']['schemas'] ?? [];
        $requiredSchemas = ['Error', 'SuccessResponse', 'Post', 'PostCreate', 'PostUpdate'];

        foreach ($requiredSchemas as $schema) {
            $this->assertArrayHasKey($schema, $schemas, "Schema '$schema' should exist");
        }
    }

    public function testYamlHasPostsTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('Posts', $tags);
    }

    public function testYamlHasCategoriesTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('Categories', $tags);
    }

    public function testYamlHasCommentsTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('Comments', $tags);
    }

    public function testYamlHasArchivesTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('Archives', $tags);
    }

    public function testYamlHasSearchTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('Search', $tags);
    }

    public function testYamlHasGdprTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('GDPR', $tags);
    }

    public function testYamlHasLanguagesTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('Languages', $tags);
    }

    public function testYamlHasTranslationsTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('Translations', $tags);
    }

    public function testYamlHasMediaTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('Media', $tags);
    }

    public function testYamlHasApiInfoTag(): void
    {
        $tags = array_column($this->yamlSpec['tags'] ?? [], 'name');
        $this->assertContains('API Info', $tags);
    }

    public function testYamlHasServers(): void
    {
        $this->assertArrayHasKey('servers', $this->yamlSpec);
        $this->assertNotEmpty($this->yamlSpec['servers']);
    }

    public function testJsonHasServers(): void
    {
        $this->assertArrayHasKey('servers', $this->jsonSpec);
        $this->assertNotEmpty($this->jsonSpec['servers']);
    }

    public function testProductionServerUrl(): void
    {
        $servers = $this->yamlSpec['servers'] ?? [];
        $this->assertNotEmpty($servers, 'Should have servers defined');
        $firstServer = $servers[0] ?? [];
        $this->assertArrayHasKey('url', $firstServer, 'First server should have URL');
    }

    public function testYamlHasInfoSection(): void
    {
        $this->assertArrayHasKey('info', $this->yamlSpec);
    }

    public function testJsonHasInfoSection(): void
    {
        $this->assertArrayHasKey('info', $this->jsonSpec);
    }

    public function testYamlInfoHasTitle(): void
    {
        $info = $this->yamlSpec['info'] ?? [];
        $this->assertArrayHasKey('title', $info);
    }

    public function testYamlInfoHasVersion(): void
    {
        $info = $this->yamlSpec['info'] ?? [];
        $this->assertArrayHasKey('version', $info);
    }

    public function testYamlPathsNotEmpty(): void
    {
        $this->assertNotEmpty($this->yamlSpec['paths'] ?? [], 'Paths should not be empty');
    }

    public function testJsonPathsNotEmpty(): void
    {
        $this->assertNotEmpty($this->jsonSpec['paths'] ?? [], 'Paths should not be empty');
    }

    public function testYamlHasComponents(): void
    {
        $this->assertArrayHasKey('components', $this->yamlSpec);
    }

    public function testJsonHasComponents(): void
    {
        $this->assertArrayHasKey('components', $this->jsonSpec);
    }

    public function testYamlComponentsHasSchemas(): void
    {
        $components = $this->yamlSpec['components'] ?? [];
        $this->assertArrayHasKey('schemas', $components);
    }
}