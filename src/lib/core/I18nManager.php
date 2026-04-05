<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

class I18nManager
{
    private static $instance = null;
    private $locale;
    private $translations = [];
    private $detector;
    private $loader;
    private $router;
    private $initialized = false;
    private $languageDirectionCache = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    private function __construct()
    {
    }

    public function initialize(): void
    {
        if ($this->initialized) {
            return;
        }

        $this->detector = new LocaleDetector();
        $this->loader = new TranslationLoader();
        $this->router = new LocaleRouter();

        $this->locale = $this->detector->detect();
        $this->translations = $this->loader->load($this->locale);

        $this->initialized = true;
    }

    public function initializeWithSettings(array $settings): void
    {
        if ($this->initialized) {
            return;
        }

        $this->detector = new LocaleDetector($settings);
        $this->loader = new TranslationLoader();
        $this->router = new LocaleRouter();

        $this->locale = $this->detector->detect();
        $this->translations = $this->loader->load($this->locale);

        $this->initialized = true;
    }

    public function t(string $key, array $params = [], ?string $locale = null): string
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        $locale = $locale ?? $this->locale;

        if ($locale !== $this->locale) {
            $translations = $this->loader->load($locale);
            $value = $translations[$key] ?? null;
        } else {
            $value = $this->translations[$key] ?? null;
        }

        if ($value === null) {
            $value = $this->loadFromJsonFallback($key, $locale);
        }

        if ($value === null) {
            $value = $key;
        }

        if (!empty($params)) {
            return $this->interpolate($value, $params);
        }

        return $value;
    }

    private function loadFromJsonFallback(string $key, string $locale): ?string
    {
        if (!defined('APP_ROOT')) {
            return null;
        }

        $themeLangPath = APP_ROOT . 'public' . DS . 'themes' . DS . 'blog' . DS . 'lang' . DS . $locale . '.json';

        if (!file_exists($themeLangPath)) {
            return null;
        }

        $jsonContent = file_get_contents($themeLangPath);
        $translations = json_decode($jsonContent, true);

        return $translations[$key] ?? null;
    }

    public function setLocale(string $locale): bool
    {
        if (!$this->initialized) {
            $this->initialize();
        }

        if (!$this->detector->isValidLocale($locale)) {
            return false;
        }

        $this->locale = $locale;
        $this->translations = $this->loader->load($locale);
        $this->detector->setLocale($locale);

        return true;
    }

    public function getLocale(): string
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->locale;
    }

    public function getAvailableLocales(): array
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->detector->getAvailableLocales();
    }

    public function isRtl(): bool
    {
        return $this->getLanguageDirection() === 'rtl';
    }

    public function getLanguageDirection(): string
    {
        if (isset($this->languageDirectionCache[$this->locale])) {
            return $this->languageDirectionCache[$this->locale];
        }

        $direction = 'ltr';

        if (class_exists('LanguageDao')) {
            try {
                $languageDao = new LanguageDao();
                $language = $languageDao->findLanguageByCode($this->locale);
                $direction = $language['lang_direction'] ?? 'ltr';
            } catch (Throwable $e) {
                $direction = 'ltr';
            }
        }

        $this->languageDirectionCache[$this->locale] = $direction;
        return $direction;
    }

    public function setLanguageDirection(string $direction): void
    {
        $this->languageDirectionCache[$this->locale] = $direction;
    }

    public function url(string $path, ?string $locale = null): string
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->router->buildUrl($path, $locale ?? $this->locale);
    }

    public function getLoader(): TranslationLoader
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->loader;
    }

    public function getRouter(): LocaleRouter
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->router;
    }

    public function getDetector(): LocaleDetector
    {
        if (!$this->initialized) {
            $this->initialize();
        }
        return $this->detector;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    private function interpolate(string $text, array $params): string
    {
        return str_replace(
            array_map(fn ($k) => ':' . $k, array_keys($params)),
            array_values($params),
            $text
        );
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
