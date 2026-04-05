<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class TranslationLoader
{
    private $cacheDir;
    private $cacheEnabled = true;
    private $cacheTtl = 3600;
    private $loaded = [];
    private $translationDao;
    private $languageDao;
    private $initialized = false;

    public function __construct()
    {
        $this->cacheDir = APP_ROOT . 'public' . DS . 'files' . DS . 'cache' . DS . 'translations' . DS;
        $this->ensureCacheDirectory();
        $this->initializeDao();
    }

    private function initializeDao(): void
    {
        if ($this->initialized) {
            return;
        }

        if (class_exists('TranslationDao') && class_exists('LanguageDao')) {
            try {
                $this->translationDao = new TranslationDao();
                $this->languageDao = new LanguageDao();
                $this->initialized = true;
            } catch (Throwable $e) {
                $this->translationDao = null;
                $this->languageDao = null;
            }
        }
    }

    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    public function load(string $locale): array
    {
        if (isset($this->loaded[$locale])) {
            return $this->loaded[$locale];
        }

        if ($this->cacheEnabled) {
            $cached = $this->loadFromCache($locale);
            if ($cached !== null) {
                $this->loaded[$locale] = $cached;
                return $cached;
            }
        }

        $translations = $this->loadFromDatabase($locale);

        if ($this->cacheEnabled) {
            $this->saveToCache($locale, $translations);
        }

        $this->loaded[$locale] = $translations;

        return $translations;
    }

    public function get(string $key, ?string $locale = null, array $params = []): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $translations = $this->load($locale);

        $value = $translations[$key] ?? $translations["{$locale}.{$key}"] ?? $key;

        if (!empty($params)) {
            $value = $this->interpolate($value, $params);
        }

        return $value;
    }

    private function loadFromCache(string $locale): ?array
    {
        $cacheFile = $this->getCacheFilePath($locale);

        if (!file_exists($cacheFile)) {
            return null;
        }

        if ($this->isCacheExpired($cacheFile)) {
            return null;
        }

        $content = file_get_contents($cacheFile);
        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }

    private function saveToCache(string $locale, array $translations): void
    {
        $cacheFile = $this->getCacheFilePath($locale);

        @file_put_contents($cacheFile, json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function loadFromDatabase(string $locale): array
    {
        if (!$this->translationDao || !$this->languageDao) {
            return $this->loadDefaultStrings($locale);
        }

        try {
            $language = $this->languageDao->findLanguageByCode($locale);

            if (!$language) {
                return $this->loadDefaultStrings($locale);
            }

            $translations = $this->translationDao->findTranslationsByLocale($language['ID']);

            $result = [];
            foreach ($translations as $t) {
                $result[$t['translation_key']] = $t['translation_value'];
            }

            return $result;
        } catch (Throwable $e) {
            return $this->loadDefaultStrings($locale);
        }
    }

    private function loadDefaultStrings(string $locale): array
    {
        $themeId = $this->getThemeIdentifier();
        $defaultFile = APP_THEME . DS . $themeId . DS . 'lang' . DS . $locale . '.json';

        if (file_exists($defaultFile)) {
            $content = file_get_contents($defaultFile);
            return json_decode($content, true) ?? [];
        }

        return [];
    }

    private function getThemeIdentifier(): string
    {
        if (function_exists('theme_identifier')) {
            try {
                return theme_identifier();
            } catch (Throwable $e) {
                return 'blog';
            }
        }
        return 'blog';
    }

    private function getCacheFilePath(string $locale): string
    {
        return $this->cacheDir . $locale . '.json';
    }

    private function isCacheExpired(string $cacheFile): bool
    {
        if ($this->cacheTtl === 0) {
            return false;
        }

        $mtime = filemtime($cacheFile);
        return (time() - $mtime) > $this->cacheTtl;
    }

    public function invalidate(string $locale): void
    {
        $cacheFile = $this->getCacheFilePath($locale);

        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }

        unset($this->loaded[$locale]);
    }

    private function interpolate(string $text, array $params): string
    {
        return str_replace(
            array_map(fn ($k) => ':' . $k, array_keys($params)),
            array_values($params),
            $text
        );
    }

    private function getCurrentLocale(): string
    {
        if (class_exists('I18nManager')) {
            $i18n = I18nManager::getInstance();
            return $i18n->getLocale();
        }
        return 'en';
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }
}
