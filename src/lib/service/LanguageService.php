<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * class LanguageService
 *
 * @category service class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 */
class LanguageService
{
    private $languageDao;
    private $translationDao;

    public function __construct()
    {
        $this->languageDao = new LanguageDao();
        $this->translationDao = new TranslationDao();
    }

    public function createLanguage(array $data): int
    {
        $this->validateLanguageData($data);

        if ($this->languageDao->codeExists($data['lang_code'])) {
            throw new ServiceException("Language code '{$data['lang_code']}' already exists");
        }

        if (!empty($data['lang_is_default'])) {
            $this->languageDao->setDefaultLanguage(0);
        }

        return $this->languageDao->createLanguage([
            'lang_code' => strtolower($data['lang_code']),
            'lang_name' => $data['lang_name'],
            'lang_native' => $data['lang_native'],
            'lang_locale' => $data['lang_locale'] ?? null,
            'lang_direction' => $data['lang_direction'] ?? 'ltr',
            'lang_sort' => $data['lang_sort'] ?? 0,
            'lang_is_default' => $data['lang_is_default'] ?? 0,
            'lang_is_active' => $data['lang_is_active'] ?? 1,
        ]);
    }

    public function updateLanguage(int $id, array $data): void
    {
        $language = $this->languageDao->findById($id);
        if (!$language) {
            throw new ServiceException("Language not found");
        }

        if (!empty($data['lang_code']) && $data['lang_code'] !== $language['lang_code']) {
            if ($this->languageDao->codeExists($data['lang_code'])) {
                throw new ServiceException("Language code '{$data['lang_code']}' already exists");
            }
        }

        if (!empty($data['lang_is_default'])) {
            $this->languageDao->setDefaultLanguage($id);
            unset($data['lang_is_default']);
        }

        $this->languageDao->updateLanguage($id, $data);
    }

    public function deleteLanguage(int $id): void
    {
        $language = $this->languageDao->findById($id);
        if (!$language) {
            throw new ServiceException("Language not found");
        }

        if ($language['lang_is_default']) {
            throw new ServiceException("Cannot delete the default language");
        }

        $this->languageDao->deleteLanguage($id);
    }

    public function setDefaultLanguage(int $id): void
    {
        $language = $this->languageDao->findById($id);
        if (!$language) {
            throw new ServiceException("Language not found");
        }

        if (!$language['lang_is_active']) {
            throw new ServiceException("Cannot set inactive language as default");
        }

        $this->languageDao->setDefaultLanguage($id);
    }

    public function getActiveLanguages(): array
    {
        return $this->languageDao->findActiveLanguages();
    }

    public function getAllLanguages(): array
    {
        return $this->languageDao->findAllLanguages();
    }

    public function getDefaultLanguage(): ?array
    {
        return $this->languageDao->findDefaultLanguage();
    }

    public function getLanguageByCode(string $code): ?array
    {
        return $this->languageDao->findLanguageByCode($code);
    }

    public function getLanguageById(int $id): ?array
    {
        return $this->languageDao->findById($id);
    }

    public function getLanguageCodes(): array
    {
        $languages = $this->languageDao->findActiveLanguages();
        return array_column($languages, 'lang_code');
    }

    private function validateLanguageData(array $data): void
    {
        if (empty($data['lang_code'])) {
            throw new ServiceException("Language code is required");
        }

        if (!preg_match('/^[a-z]{2}(-[a-z]{2})?$/i', $data['lang_code'])) {
            throw new ServiceException("Invalid language code format (use 'en' or 'en-US')");
        }

        if (empty($data['lang_name'])) {
            throw new ServiceException("Language name is required");
        }

        if (empty($data['lang_native'])) {
            throw new ServiceException("Native language name is required");
        }
    }
}
