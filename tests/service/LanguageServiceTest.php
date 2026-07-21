<?php defined('SCRIPTLOG') || define('SCRIPTLOG', true);

use PHPUnit\Framework\TestCase;

class LanguageServiceTest extends TestCase
{
    private $languageService;

    protected function setUp(): void
    {
        $this->languageService = new \Scriptlog\Service\LanguageService();
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(\Scriptlog\Service\LanguageService::class, $this->languageService);
    }

    public function testCreateLanguageThrowsExceptionWhenCodeMissing(): void
    {
        $this->expectException(\Scriptlog\Core\ServiceException::class);
        $this->languageService->createLanguage(['lang_name' => 'English']);
    }

    public function testCreateLanguageThrowsExceptionWhenCodeInvalid(): void
    {
        $this->expectException(\Scriptlog\Core\ServiceException::class);
        $this->languageService->createLanguage(['lang_code' => 'invalid', 'lang_name' => 'Test', 'lang_native' => 'Test']);
    }

    public function testGetLanguageCodesReturnsArray(): void
    {
        $this->markTestSkipped('Requires real DB connection - LanguageDao instantiates its own DB');
    }

    public function testGetActiveLanguagesReturnsArray(): void
    {
        $this->markTestSkipped('Requires real DB connection - LanguageDao instantiates its own DB');
    }

    public function testGetAllLanguagesReturnsArray(): void
    {
        $this->markTestSkipped('Requires real DB connection - LanguageDao instantiates its own DB');
    }

    public function testGetDefaultLanguageReturnsNullOrArray(): void
    {
        $this->markTestSkipped('Requires real DB connection - LanguageDao instantiates its own DB');
    }

    public function testGetLanguageByIdReturnsNullOrArray(): void
    {
        $this->markTestSkipped('Requires real DB connection - LanguageDao instantiates its own DB');
    }

    public function testGetLanguageByCodeReturnsNullOrArray(): void
    {
        $this->markTestSkipped('Requires real DB connection - LanguageDao instantiates its own DB');
    }

    public function testDeleteLanguageThrowsExceptionOnNonExistent(): void
    {
        $this->markTestSkipped('Requires real DB connection - LanguageDao instantiates its own DB');
    }

    public function testSetDefaultLanguageThrowsExceptionOnNonExistent(): void
    {
        $this->markTestSkipped('Requires real DB connection - LanguageDao instantiates its own DB');
    }
}
