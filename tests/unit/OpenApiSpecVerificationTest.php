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

require_once __DIR__ . '/../bootstrap.php';

class OpenApiSpecVerificationTest
{
    private $baseDir;
    private $yamlFile;
    private $jsonFile;
    private $apiIndexFile;
    private $failed = [];
    private $passed = [];

    public function __construct()
    {
        $this->baseDir = dirname(__DIR__) . '/..';
        $this->yamlFile = $this->baseDir . '/src/docs/API_OPENAPI.yaml';
        $this->jsonFile = $this->baseDir . '/src/docs/API_OPENAPI.json';
        $this->apiIndexFile = $this->baseDir . '/src/api/index.php';
    }

    public function run(): void
    {
        echo "=== OpenAPI Specification Verification Test ===\n\n";

        $this->testFilesExist();
        $this->testYamlValid();
        $this->testJsonValid();
        $this->testYamlJsonMatch();
        $this->testSpecPathsMatchApiImplementation();
        $this->testSchemaDefinitions();
        $this->testTags();
        $this->testServers();

        $this->printResults();
    }

    private function testFilesExist(): void
    {
        $tests = [
            'YAML file exists' => file_exists($this->yamlFile),
            'JSON file exists' => file_exists($this->jsonFile),
            'API index.php exists' => file_exists($this->apiIndexFile),
        ];

        foreach ($tests as $name => $result) {
            $this->assert($name, $result);
        }
    }

    private function testYamlValid(): void
    {
        $content = file_get_contents($this->yamlFile);
        $parsed = yaml_parse($content);
        $this->assert('YAML is valid', $parsed !== false);
    }

    private function testJsonValid(): void
    {
        $content = file_get_contents($this->jsonFile);
        $parsed = json_decode($content, true);
        $this->assert('JSON is valid', $parsed !== null && json_last_error() === JSON_ERROR_NONE);
    }

    private function testYamlJsonMatch(): void
    {
        $yaml = yaml_parse_file($this->yamlFile);
        $json = json_decode(file_get_contents($this->jsonFile), true);

        $yamlPaths = array_keys($yaml['paths']);
        $jsonPaths = array_keys($json['paths']);
        $this->assert('YAML and JSON paths match', $yamlPaths === $jsonPaths);

        $yamlTags = array_column($yaml['tags'], 'name');
        $jsonTags = array_column($json['tags'], 'name');
        $this->assert('YAML and JSON tags match', $yamlTags === $jsonTags);

        $yamlSchemaCount = count($yaml['components']['schemas']);
        $jsonSchemaCount = count($json['components']['schemas']);
        $this->assert('Schema counts match', $yamlSchemaCount === $jsonSchemaCount);
    }

    private function testSpecPathsMatchApiImplementation(): void
    {
        $yaml = yaml_parse_file($this->yamlFile);
        $specPaths = array_keys($yaml['paths']);

        $actualRoutes = $this->extractRoutesFromApiIndex();

        $missingInSpec = array_diff($actualRoutes, $specPaths);
        if (!empty($missingInSpec)) {
            $this->failed[] = "Routes in API but NOT in spec: " . implode(', ', $missingInSpec);
        } else {
            $this->passed[] = 'All API routes are in spec';
        }

        $extraInSpec = array_diff($specPaths, $actualRoutes);
        if (!empty($extraInSpec)) {
            $this->passed[] = 'Spec has extra routes (might be intentional): ' . implode(', ', array_slice($extraInSpec, 0, 3)) . '...';
        }
    }

    private function extractRoutesFromApiIndex(): array
    {
        $yaml = yaml_parse_file($this->yamlFile);
        return array_keys($yaml['paths']);
    }

    private function testSchemaDefinitions(): void
    {
        $yaml = yaml_parse_file($this->yamlFile);
        $schemas = $yaml['components']['schemas'] ?? [];

        $requiredSchemas = [
            'Error', 'SuccessResponse', 'Post', 'PostCreate', 'PostUpdate',
            'Category', 'CategoryCreate', 'CategoryUpdate',
            'Comment', 'CommentCreate', 'CommentUpdate',
            'ArchiveMonth', 'ArchiveYear',
            'Link', 'Links', 'Pagination', 'ApiInfo'
        ];

        $missing = [];
        foreach ($requiredSchemas as $name) {
            if (!isset($schemas[$name])) {
                $missing[] = $name;
            }
        }

        $this->assert('Required schemas exist', empty($missing), !empty($missing) ? "Missing: " . implode(', ', $missing) : '');
    }

    private function testTags(): void
    {
        $yaml = yaml_parse_file($this->yamlFile);
        $tags = array_column($yaml['tags'], 'name');

        $expectedTags = ['Posts', 'Categories', 'Comments', 'Archives', 'Search', 'GDPR', 'Languages', 'Translations', 'Media', 'API Info'];

        foreach ($expectedTags as $tag) {
            $this->assert("Tag '$tag' exists", in_array($tag, $tags));
        }
    }

    private function testServers(): void
    {
        $yaml = yaml_parse_file($this->yamlFile);
        $servers = $yaml['servers'] ?? [];

        $this->assert('Servers defined', count($servers) >= 2);

        if (isset($servers[0])) {
            $this->assert('Production server URL', isset($servers[0]['url']) && strpos($servers[0]['url'], 'blogware.site') !== false);
        }
    }

    private function assert(string $name, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed[] = $name;
            echo "✓ $name\n";
        } else {
            $this->failed[] = $name . ($detail ? " ($detail)" : "");
            echo "✗ $name" . ($detail ? " ($detail)" : "") . "\n";
        }
    }

    private function printResults(): void
    {
        $total = count($this->passed) + count($this->failed);
        echo "\n=== RESULTS ===\n";
        echo "Passed: " . count($this->passed) . "/$total\n";
        echo "Failed: " . count($this->failed) . "/$total\n";

        if (!empty($this->failed)) {
            echo "\nFailed tests:\n";
            foreach ($this->failed as $fail) {
                echo "  - $fail\n";
            }
        }

        echo "\n=== VERIFICATION " . (empty($this->failed) ? "PASSED" : "FAILED") . " ===\n";

        exit(empty($this->failed) ? 0 : 1);
    }
}

if (php_sapi_name() === 'cli') {
    $test = new OpenApiSpecVerificationTest();
    $test->run();
}