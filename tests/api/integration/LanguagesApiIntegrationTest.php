<?php
/**
 * LanguagesApi Integration Test
 *
 * Database-backed integration tests for the LanguageService layer, verifying
 * that setDefaultLanguage() and deleteLanguage() mutate tbl_languages as
 * documented. Runs against the real blogware_test database seeded by
 * ApiDataFixture.
 *
 * @package Scriptlog\Tests
 */

require_once __DIR__ . '/../ApiTestCase.php';
require_once __DIR__ . '/../fixtures/ApiDataFixture.php';

class LanguagesApiIntegrationTest extends ApiTestCase
{
    /**
     * @var \Scriptlog\Service\LanguageService
     */
    private $languageService;

    /**
     * Seed the test database and build a real LanguageService.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (!ApiDataFixture::isAvailable()) {
            $this->markTestSkipped('Test database unavailable');
        }

        ApiDataFixture::seed();
        $this->languageService = new \Scriptlog\Service\LanguageService();
    }

    /**
     * setDefaultLanguage() persists the new default and clears the old one.
     *
     * The fixture seeds "en" as the default language.
     *
     * @return void
     */
    public function testSetDefaultUpdatesDatabase()
    {
        $arabic = $this->languageService->getLanguageByCode('ar');
        $this->assertNotNull($arabic);

        $this->languageService->setDefaultLanguage((int)$arabic['ID']);

        $default = $this->languageService->getDefaultLanguage();
        $this->assertSame('ar', $default['lang_code']);

        $english = $this->languageService->getLanguageByCode('en');
        $this->assertSame(0, (int)$english['lang_is_default']);

        $active = $this->languageService->getActiveLanguages();
        $defaultCount = 0;
        foreach ($active as $language) {
            if ((int)$language['lang_is_default'] === 1) {
                $defaultCount++;
            }
        }
        $this->assertSame(1, $defaultCount);
    }

    /**
     * deleteLanguage() removes a non-default language from the database.
     *
     * @return void
     */
    public function testDeleteRemovesNonDefaultLanguage()
    {
        $zh = $this->languageService->getLanguageByCode('zh');
        $this->assertNotNull($zh);

        $this->languageService->deleteLanguage((int)$zh['ID']);

        $this->assertNull($this->languageService->getLanguageByCode('zh'));
    }

    /**
     * deleteLanguage() rejects deleting the default language.
     *
     * @return void
     */
    public function testDeleteRejectsDefaultLanguage()
    {
        $english = $this->languageService->getLanguageByCode('en');
        $this->assertNotNull($english);

        $this->expectException(\Scriptlog\Core\ServiceException::class);

        $this->languageService->deleteLanguage((int)$english['ID']);
    }

    /**
     * createLanguage() with lang_is_default switches the default language.
     *
     * @return void
     */
    public function testCreateDefaultLanguageBecomesDefault()
    {
        $this->languageService->createLanguage([
            'lang_code' => 'de',
            'lang_name' => 'German',
            'lang_native' => 'Deutsch',
            'lang_locale' => 'de_DE',
            'lang_direction' => 'ltr',
            'lang_sort' => 0,
            'lang_is_default' => 1,
        ]);

        $default = $this->languageService->getDefaultLanguage();
        $this->assertSame('de', $default['lang_code']);
    }
}
