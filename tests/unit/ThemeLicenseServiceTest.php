<?php

use Scriptlog\Service\ThemeLicenseService;
use PHPUnit\Framework\TestCase;

/**
 * ThemeLicenseServiceTest
 *
 * Unit tests for ThemeLicenseService: theme.ini parsing, premium
 * detection, license API URL/body construction and response parsing.
 * Network calls are captured through a testable subclass of post().
 *
 * @category Tests
 */
class ThemeLicenseServiceTestable extends ThemeLicenseService
{
    public $lastUrl;
    public $lastBody;
    public $lastSecret;

    public function getApiBaseUrl()
    {
        return $this->apiBaseUrl;
    }

    public function getTimeout()
    {
        $reflection = new \ReflectionProperty(ThemeLicenseService::class, 'timeout');
        $reflection->setAccessible(true);

        return $reflection->getValue($this);
    }

    protected function post($url, $body, $secret)
    {
        $this->lastUrl = $url;
        $this->lastBody = $body;
        $this->lastSecret = $secret;

        return ['success' => true, 'message' => 'ok'];
    }

    public function exposeParseResponse($response, $httpCode)
    {
        return $this->parseResponse($response, $httpCode);
    }
}

class ThemeLicenseServiceTest extends TestCase
{
    private const PREMIUM_FIXTURE_DIR = 'license-service-fixture';

    private static $premiumDirCreated = false;

    protected function setUp(): void
    {
        $this->ensurePremiumFixture();
    }

    protected function tearDown(): void
    {
        if (self::$premiumDirCreated) {
            $this->removeDir(self::$premiumDirCreated);
            self::$premiumDirCreated = false;
        }
    }

    private function ensurePremiumFixture(): void
    {
        if (self::$premiumDirCreated !== false) {
            return;
        }

        $iniFile = APP_ROOT . APP_THEME . self::PREMIUM_FIXTURE_DIR . DIRECTORY_SEPARATOR . 'theme.ini';

        if (!file_exists(dirname($iniFile))) {
            mkdir(dirname($iniFile), 0755, true);
        }

        file_put_contents($iniFile, "[info]\ntheme_name = Premium Fixture\ntheme_designer = Test\ntheme_directory = " . self::PREMIUM_FIXTURE_DIR . "\ntheme_type = premium\n");

        self::$premiumDirCreated = dirname($iniFile);
    }

    private function removeDir($dir): void
    {
        if (is_dir($dir)) {
            $items = glob($dir . '/*');
            if ($items) {
                foreach ($items as $item) {
                    if (is_file($item)) {
                        unlink($item);
                    }
                }
            }
            rmdir($dir);
        }
    }

    public function testApiBaseUrlDefaultsToMyblog()
    {
        $service = new ThemeLicenseServiceTestable();
        $this->assertEquals('https://myblog.local', $service->getApiBaseUrl());
    }

    public function testApiBaseUrlCanBeOverridden()
    {
        $service = new ThemeLicenseServiceTestable('https://license.example.test', 5);
        $this->assertEquals('https://license.example.test', $service->getApiBaseUrl());
        $this->assertEquals(5, $service->getTimeout());
    }

    public function testReadThemeConfigReturnsEmptyArrayForMissingTheme()
    {
        $service = new ThemeLicenseServiceTestable();
        $this->assertSame([], $service->readThemeConfig('no-such-theme-dir'));
    }

    public function testReadThemeConfigParsesIniFile()
    {
        $service = new ThemeLicenseServiceTestable();
        $config = $service->readThemeConfig('blog');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('info', $config);
        $this->assertEquals('free', $config['info']['theme_type']);
    }

    public function testIsPremiumThemeReturnsFalseForFreeTheme()
    {
        $service = new ThemeLicenseServiceTestable();
        $this->assertFalse($service->isPremiumTheme('blog'));
    }

    public function testIsPremiumThemeReturnsFalseForMissingTheme()
    {
        $service = new ThemeLicenseServiceTestable();
        $this->assertFalse($service->isPremiumTheme('no-such-theme-dir'));
    }

    public function testIsPremiumThemeReturnsTrueForPremiumIni()
    {
        $service = new ThemeLicenseServiceTestable();
        $this->assertTrue($service->isPremiumTheme(self::PREMIUM_FIXTURE_DIR));
    }

    public function testActivateBuildsActivateEndpointAndBody()
    {
        $service = new ThemeLicenseServiceTestable();

        $service->activate('LIC-KEY-123', 'example.com', 'secret', 42);

        $this->assertStringEndsWith('/api/v2/licenses/activate/42', $service->lastUrl);

        $payload = json_decode($service->lastBody, true);
        $this->assertSame('LIC-KEY-123', $payload['license_key']);
        $this->assertSame('example.com', $payload['domain']);
    }

    public function testValidateBuildsValidateEndpointAndBody()
    {
        $service = new ThemeLicenseServiceTestable();

        $service->validate('LIC-KEY-123', 42, 'secret');

        $this->assertStringEndsWith('/api/v2/licenses/validate', $service->lastUrl);

        $payload = json_decode($service->lastBody, true);
        $this->assertSame('LIC-KEY-123', $payload['license_key']);
        $this->assertSame(42, $payload['product_id']);
    }

    public function testDeactivateBuildsDeactivateEndpointAndBody()
    {
        $service = new ThemeLicenseServiceTestable();

        $service->deactivate('LIC-KEY-123', 42, 'secret');

        $this->assertStringEndsWith('/api/v2/licenses/deactivate', $service->lastUrl);

        $payload = json_decode($service->lastBody, true);
        $this->assertSame('LIC-KEY-123', $payload['license_key']);
        $this->assertSame(42, $payload['product_id']);
    }

    public function testParsesSuccessfulResponse()
    {
        $service = new ThemeLicenseServiceTestable();

        $result = $service->exposeParseResponse(json_encode(['license' => 'active']), 200);

        $this->assertTrue($result['success']);
        $this->assertEquals(200, $result['http_code']);
        $this->assertArrayHasKey('license', $result['data']);
    }

    public function testParsesHttpErrorResponse()
    {
        $service = new ThemeLicenseServiceTestable();

        $result = $service->exposeParseResponse(json_encode(['error' => ['message' => 'Invalid license key']]), 400);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid license key', $result['message']);
    }

    public function testParsesApiStatusErrorResponse()
    {
        $service = new ThemeLicenseServiceTestable();

        $result = $service->exposeParseResponse(json_encode(['status' => 'error', 'message' => 'Domain not allowed']), 200);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Domain not allowed', $result['message']);
    }

    public function testParsesErrorsArrayIntoMessage()
    {
        $service = new ThemeLicenseServiceTestable();

        $result = $service->exposeParseResponse(json_encode(['status' => 'error', 'errors' => ['Licenses exhausted']]), 200);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Licenses exhausted', $result['message']);
    }

    public function testParsesGenericErrorWithoutMessage()
    {
        $service = new ThemeLicenseServiceTestable();

        $result = $service->exposeParseResponse(json_encode(['status' => 'error']), 500);

        $this->assertFalse($result['success']);
        $this->assertSame('License validation failed.', $result['message']);
    }

    public function testParsesInvalidJsonResponse()
    {
        $service = new ThemeLicenseServiceTestable();

        $result = $service->exposeParseResponse('not json at all', 200);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid response', $result['message']);
    }
}