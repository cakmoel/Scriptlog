<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class LocaleDetector
{
    private $availableLocales = [];
    private $defaultLocale = 'en';
    private $autoDetectEnabled = true;
    private $settingsLoaded = false;

    public function __construct(array $settings = [])
    {
        if (!empty($settings)) {
            $this->applySettings($settings);
        } else {
            $this->loadSettings();
        }
    }

    private function applySettings(array $settings): void
    {
        $this->defaultLocale = $settings['default'] ?? 'en';
        $this->availableLocales = $settings['available'] ?? ['en'];
        $this->autoDetectEnabled = ($settings['auto_detect'] ?? '1') === '1';
        $this->settingsLoaded = true;
    }

    private function loadSettings(): void
    {
        if ($this->settingsLoaded) {
            return;
        }

        if (!class_exists('ConfigurationDao')) {
            $this->availableLocales = ['en'];
            $this->settingsLoaded = true;
            return;
        }

        try {
            $configDao = new ConfigurationDao();

            $default = $configDao->findConfigByName('lang_default', new Sanitize());
            $available = $configDao->findConfigByName('lang_available', new Sanitize());
            $autoDetect = $configDao->findConfigByName('lang_auto_detect', new Sanitize());

            $this->defaultLocale = $default['setting_value'] ?? 'en';
            $this->availableLocales = array_filter(explode(',', $available['setting_value'] ?? 'en'));

            if (empty($this->availableLocales)) {
                $this->availableLocales = ['en'];
            }

            $this->autoDetectEnabled = ($autoDetect['setting_value'] ?? '1') === '1';
            $this->settingsLoaded = true;
        } catch (Throwable $e) {
            $this->availableLocales = ['en'];
            $this->settingsLoaded = true;
        }
    }

    public function detect(): string
    {
        $urlLocale = $this->extractFromUrl();
        if ($urlLocale !== null && $this->isValidLocale($urlLocale)) {
            $this->setLocale($urlLocale);
            return $urlLocale;
        }

        if (isset($_SESSION['scriptlog_locale']) && $this->isValidLocale($_SESSION['scriptlog_locale'])) {
            return $_SESSION['scriptlog_locale'];
        }

        if (isset($_COOKIE['scriptlog_locale']) && $this->isValidLocale($_COOKIE['scriptlog_locale'])) {
            return $_COOKIE['scriptlog_locale'];
        }

        if ($this->autoDetectEnabled) {
            $browserLocale = $this->detectFromBrowser();
            if ($browserLocale !== null) {
                return $browserLocale;
            }
        }

        return $this->defaultLocale;
    }

    private function extractFromUrl(): ?string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH);
        $path = trim($path, '/');
        $segments = explode('/', $path);
        $firstSegment = $segments[0] ?? '';

        if (!empty($firstSegment) && $this->isValidLocale($firstSegment)) {
            return $firstSegment;
        }

        return null;
    }

    private function detectFromBrowser(): ?string
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        foreach ($languages as $lang) {
            $parts = explode(';', $lang);
            $locale = trim($parts[0]);

            if ($this->isValidLocale($locale)) {
                return $locale;
            }

            $langCode = explode('-', $locale)[0];
            if ($this->isValidLocale($langCode)) {
                return $langCode;
            }
        }

        return null;
    }

    public function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->availableLocales, true);
    }

    public function setLocale(string $locale): bool
    {
        if (!$this->isValidLocale($locale)) {
            return false;
        }

        $_SESSION['scriptlog_locale'] = $locale;
        setcookie('scriptlog_locale', $locale, time() + (86400 * 365), '/');

        return true;
    }

    public function getAvailableLocales(): array
    {
        return $this->availableLocales;
    }

    public function setAvailableLocales(array $locales): void
    {
        $this->availableLocales = $locales;
    }

    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }

    public function setDefaultLocale(string $locale): void
    {
        $this->defaultLocale = $locale;
    }

    public function isAutoDetectEnabled(): bool
    {
        return $this->autoDetectEnabled;
    }

    public function setAutoDetect(bool $enabled): void
    {
        $this->autoDetectEnabled = $enabled;
    }
}
