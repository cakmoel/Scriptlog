<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class TranslationService
{
    private $translationDao;
    private $languageDao;
    private $loader;

    public function __construct()
    {
        $this->translationDao = new TranslationDao();
        $this->languageDao = new LanguageDao();
        $this->loader = new TranslationLoader();
    }

    public function createTranslation(array $data): int
    {
        $this->validateTranslationData($data);

        $language = $this->languageDao->findLanguageByCode($data['lang_code']);
        if (!$language) {
            throw new ServiceException("Language not found: {$data['lang_code']}");
        }

        $existing = $this->translationDao->findTranslationByKey($language['ID'], $data['translation_key']);
        if ($existing) {
            throw new ServiceException("Translation key already exists");
        }

        $translationId = $this->translationDao->createTranslation([
            'lang_id' => $language['ID'],
            'translation_key' => $data['translation_key'],
            'translation_value' => $data['translation_value'],
            'translation_context' => $data['translation_context'] ?? null,
            'is_html' => $data['is_html'] ?? false,
        ]);

        $this->loader->invalidate($data['lang_code']);

        return $translationId;
    }

    public function updateTranslation(int $id, array $data): void
    {
        $translation = $this->translationDao->findById($id);
        if (!$translation) {
            throw new ServiceException("Translation not found");
        }

        $language = $this->languageDao->findById($translation['lang_id']);

        $this->translationDao->updateTranslation($id, [
            'translation_value' => $data['translation_value'] ?? $translation['translation_value'],
            'translation_context' => $data['translation_context'] ?? $translation['translation_context'],
            'is_html' => $data['is_html'] ?? $translation['is_html'],
        ]);

        $this->loader->invalidate($language['lang_code']);
    }

    public function deleteTranslation(int $id): void
    {
        $translation = $this->translationDao->findById($id);
        if (!$translation) {
            throw new ServiceException("Translation not found");
        }

        $language = $this->languageDao->findById($translation['lang_id']);

        $this->translationDao->deleteTranslation($id);
        $this->loader->invalidate($language['lang_code']);
    }

    public function getTranslations(string $langCode, int $page = 1, int $perPage = 50): array
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            return [];
        }

        return $this->translationDao->findTranslationsPaginated($language['ID'], ($page - 1) * $perPage, $perPage);
    }

    public function getTranslationsByContext(string $langCode, string $context, int $page = 1, int $perPage = 50): array
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            return [];
        }

        return $this->translationDao->findByContextPaginated($language['ID'], $context, ($page - 1) * $perPage, $perPage);
    }

    public function searchTranslations(string $langCode, string $query, int $page = 1, int $perPage = 50): array
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            return [];
        }

        return $this->translationDao->searchPaginated($language['ID'], $query, ($page - 1) * $perPage, $perPage);
    }

    public function countAll(string $langCode): int
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            return 0;
        }
        return $this->translationDao->countByLanguage($language['ID']);
    }

    public function countAllTranslations(): int
    {
        return $this->translationDao->countAllTranslations();
    }

    public function getAllTranslations(int $page = 1, int $perPage = 50): array
    {
        return $this->translationDao->findAllTranslationsPaginated(($page - 1) * $perPage, $perPage);
    }

    public function countByContext(string $langCode, string $context): int
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            return 0;
        }
        return $this->translationDao->countByContext($language['ID'], $context);
    }

    public function countSearch(string $langCode, string $query): int
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            return 0;
        }
        return $this->translationDao->countSearch($language['ID'], $query);
    }

    public function importFromArray(string $langCode, array $translations): int
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            throw new ServiceException("Language not found: {$langCode}");
        }

        $imported = 0;

        foreach ($translations as $key => $value) {
            $existing = $this->translationDao->findTranslationByKey($language['ID'], $key);

            if ($existing) {
                $this->translationDao->updateTranslation($existing['ID'], [
                    'translation_value' => $value,
                ]);
            } else {
                $this->translationDao->createTranslation([
                    'lang_id' => $language['ID'],
                    'translation_key' => $key,
                    'translation_value' => $value,
                ]);
            }

            $imported++;
        }

        $this->loader->invalidate($langCode);

        return $imported;
    }

    public function exportToArray(string $langCode): array
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            throw new ServiceException("Language not found: {$langCode}");
        }

        return $this->translationDao->exportToJson($language['ID']);
    }

    public function getContexts(): array
    {
        return $this->translationDao->getDistinctContexts();
    }

    public function regenerateCache(string $langCode): void
    {
        $this->loader->invalidate($langCode);
        $this->loader->load($langCode);
    }

    public function getTranslationCount(string $langCode): int
    {
        $language = $this->languageDao->findLanguageByCode($langCode);
        if (!$language) {
            return 0;
        }

        return $this->translationDao->countByLanguage($language['ID']);
    }

    private function validateTranslationData(array $data): void
    {
        if (empty($data['lang_code'])) {
            throw new ServiceException("Language code is required");
        }

        if (empty($data['translation_key'])) {
            throw new ServiceException("Translation key is required");
        }

        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $data['translation_key'])) {
            throw new ServiceException("Invalid translation key format");
        }

        if (!isset($data['translation_value'])) {
            throw new ServiceException("Translation value is required");
        }
    }
}
