<?php

/**
 * Blog Theme i18n Helpers
 *
 * Internationalization and locale helpers for the Bootstrap Blog theme.
 * Extracted from the monolithic functions.php (Phase 5 remediation).
 * All functions use function_exists() guards to avoid redeclaration errors.
 *
 * @category Theme Function
 * @package Scriptlog
 */

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * t() - Translation function for frontend
 */
if (!function_exists('t')) {
    function t(string $key, array $params = []): string
    {
        static $translations = [];
        static $locale = null;

        if ($locale === null) {
            $locale = detect_browser_locale();
        }

        if (!isset($translations[$locale])) {
            $translations[$locale] = load_theme_translations($locale);
        }

        $value = $translations[$locale][$key] ?? ($translations['en'][$key] ?? $key);

        if (!empty($params)) {
            foreach ($params as $param => $val) {
                $value = str_replace('%' . $param . '%', $val, $value);
            }
        }

        return $value;
    }
}

/**
 * detect_browser_locale() - Detect browser locale
 */
if (!function_exists('detect_browser_locale')) {
    function detect_browser_locale(): string
    {
        $available = ['en', 'es', 'ar', 'zh', 'fr', 'ru', 'id'];
        $default = 'en';

        if (isset($_SESSION['scriptlog_locale']) && in_array($_SESSION['scriptlog_locale'], $available)) {
            return $_SESSION['scriptlog_locale'];
        }

        if (isset($_COOKIE['scriptlog_locale']) && in_array($_COOKIE['scriptlog_locale'], $available)) {
            return $_COOKIE['scriptlog_locale'];
        }

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
            foreach ($languages as $lang) {
                $parts = explode(';', trim($lang));
                $code = explode('-', $parts[0])[0];
                if (in_array($code, $available)) {
                    return $code;
                }
            }
        }

        return $default;
    }
}

/**
 * load_theme_translations() - Load translations from JSON file
 */
if (!function_exists('load_theme_translations')) {
    function load_theme_translations(string $locale): array
    {
        static $cache = [];

        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

        $themeLangDir = __DIR__ . '/lang/';
        $file = $themeLangDir . $locale . '.json';

        if (file_exists($file)) {
            $content = file_get_contents($file);
            $cache[$locale] = json_decode($content, true) ?: [];
        } else {
            $cache[$locale] = [];
        }

        return $cache[$locale];
    }
}

/**
 * reset_i18n_cache() - Reset translation cache
 */
if (!function_exists('reset_i18n_cache')) {
    function reset_i18n_cache(): void
    {
        // Utility function for testing
    }
}

/**
 * locale_url() - Get URL with locale prefix
 */
if (!function_exists('locale_url')) {
    function locale_url(string $path = '', ?string $locale = null): string
    {
        if (!class_exists('I18nManager')) {
            return $path;
        }

        $i18n = I18nManager::getInstance();
        $detector = $i18n->getDetector();
        $defaultLocale = $detector->getDefaultLocale();
        $targetLocale = $locale ?? $i18n->getLocale();

        $permalinksEnabled = function_exists('rewrite_status') && rewrite_status() === 'yes';
        $prefixEnabled = function_exists('is_locale_prefix_enabled') ? is_locale_prefix_enabled() : false;

        if (!$permalinksEnabled) {
            return $path;
        }

        if ($permalinksEnabled && !$prefixEnabled) {
            if ($targetLocale === $defaultLocale) {
                return $path;
            }
            return $path;
        }

        if ($targetLocale === $defaultLocale) {
            return $path;
        }

        return '/' . $targetLocale . ($path ? '/' . ltrim($path, '/') : '');
    }
}

/**
 * is_locale_prefix_enabled() - Check if locale prefix should be added
 */
if (!function_exists('is_locale_prefix_enabled')) {
    function is_locale_prefix_enabled(): bool
    {
        if (!class_exists('ConfigurationDao')) {
            return false;
        }

        try {
            $configDao = new ConfigurationDao();
            $setting = $configDao->findConfigByName('lang_prefix_required', new Sanitize());
            return ($setting['setting_value'] ?? '0') === '1';
        } catch (Throwable $e) {
            return false;
        }
    }
}

/**
 * get_default_locale() - Get the default locale
 */
if (!function_exists('get_default_locale')) {
    function get_default_locale(): string
    {
        if (!class_exists('I18nManager')) {
            return 'en';
        }

        $i18n = I18nManager::getInstance();
        return $i18n->getDetector()->getDefaultLocale();
    }
}

/**
 * get_locale() - Get current locale
 */
if (!function_exists('get_locale')) {
    function get_locale(): string
    {
        if (!class_exists('I18nManager')) {
            return 'en';
        }

        $i18n = I18nManager::getInstance();
        return $i18n->getLocale();
    }
}

/**
 * available_locales() - Get available locales
 */
if (!function_exists('available_locales')) {
    function available_locales(): array
    {
        if (!class_exists('I18nManager')) {
            return ['en'];
        }

        $i18n = I18nManager::getInstance();
        return $i18n->getAvailableLocales();
    }
}

/**
 * is_rtl() - Check if current locale is RTL
 */
if (!function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        if (!class_exists('I18nManager')) {
            return false;
        }

        $i18n = I18nManager::getInstance();
        return $i18n->isRtl();
    }
}

/**
 * get_html_dir() - Get HTML dir attribute
 */
if (!function_exists('get_html_dir')) {
    function get_html_dir(): string
    {
        return is_rtl() ? 'rtl' : 'ltr';
    }
}

/**
 * get_language_name() - Get human-readable language name
 */
if (!function_exists('get_language_name')) {
    function get_language_name(string $locale, bool $native = true): string
    {
        $names = [
            'en' => ['native' => 'English', 'english' => 'English'],
            'ar' => ['native' => 'العربية', 'english' => 'Arabic'],
            'zh' => ['native' => '中文', 'english' => 'Chinese'],
            'fr' => ['native' => 'Français', 'english' => 'French'],
            'ru' => ['native' => 'Русский', 'english' => 'Russian'],
            'es' => ['native' => 'Español', 'english' => 'Spanish'],
            'id' => ['native' => 'Bahasa Indonesia', 'english' => 'Indonesian'],
        ];

        $key = $native ? 'native' : 'english';
        return $names[$locale][$key] ?? ucfirst($locale);
    }
}

/**
 * get_all_language_names() - Get all available language names
 */
if (!function_exists('get_all_language_names')) {
    function get_all_language_names(): array
    {
        $locales = available_locales();
        $names = [];

        foreach ($locales as $locale) {
            $names[$locale] = [
                'native' => get_language_name($locale, true),
                'english' => get_language_name($locale, false),
                'code' => strtoupper($locale),
            ];
        }

        return $names;
    }
}

/**
 * language_switcher() - Generate language switcher HTML
 */
if (!function_exists('language_switcher')) {
    function language_switcher(array $args = []): string
    {
        $current = get_locale();
        $locales = available_locales();

        if (count($locales) <= 1) {
            return '';
        }

        $style = $args['style'] ?? 'dropdown';
        $show_names = $args['show_names'] ?? true;
        $class = $args['class'] ?? 'language-switcher';
        $current_native = get_language_name($current, true);
        $current_code = strtoupper($current);

        $html = '<div class="' . theme_escape_html($class) . '">';

        if ($style === 'dropdown') {
            $html .= '<div class="dropdown">';
            $html .= '<button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
            $html .= '<i class="fa fa-globe" aria-hidden="true"></i> ';
            $html .= $show_names ? theme_escape_html($current_native) : $current_code;
            $html .= '</button>';
            $html .= '<div class="dropdown-menu">';

            foreach ($locales as $locale) {
                $active = ($locale === $current) ? 'active' : '';
                $url = '?switch-lang=' . theme_escape_html($locale) . '&redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/');
                $native_name = get_language_name($locale, true);
                $lang_code = strtoupper($locale);
                $html .= '<a class="dropdown-item ' . $active . '" href="' . $url . '">';
                $html .= '<span class="lang-code-badge">' . $lang_code . '</span>';
                $html .= '<span class="lang-name">' . theme_escape_html($native_name) . '</span>';
                if ($active) {
                    $html .= ' <i class="fa fa-check" aria-hidden="true"></i>';
                }
                $html .= '</a>';
            }

            $html .= '</div></div>';
        } else {
            foreach ($locales as $locale) {
                $active = ($locale === $current) ? 'active' : '';
                $url = '?switch-lang=' . theme_escape_html($locale) . '&redirect=' . urlencode($_SERVER['REQUEST_URI'] ?? '/');
                $html .= '<a class="' . $active . '" href="' . $url . '">' . get_language_name($locale, true) . '</a>';
            }
        }

        $html .= '</div>';

        return $html;
    }
}
