<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

function sanitize_locale($locale)
{
    $allowed_locales = [
        'en', 'es', 'fr', 'de', 'it', 'pt', 'ru', 'zh', 'ja', 'ko',
        'ar', 'hi', 'id', 'ms', 'tr', 'nl', 'pl', 'vi', 'th', 'he',
        'bg', 'cs', 'da', 'el', 'et', 'fi', 'hu', 'lt', 'lv', 'ro',
        'sk', 'sl', 'sv', 'uk', 'fa', 'bn', 'ta', 'te', 'mr', 'gu'
    ];

    $locale = strtolower(trim($locale));

    if (in_array($locale, $allowed_locales, true)) {
        return $locale;
    }

    return 'en';
}

function get_available_locales()
{
    return [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
        'it' => 'Italian',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'zh' => 'Chinese',
        'ja' => 'Japanese',
        'ko' => 'Korean',
        'ar' => 'Arabic',
        'hi' => 'Hindi',
        'id' => 'Indonesian',
        'ms' => 'Malay',
        'tr' => 'Turkish',
        'nl' => 'Dutch',
        'pl' => 'Polish',
        'vi' => 'Vietnamese',
        'th' => 'Thai',
        'he' => 'Hebrew',
        'bg' => 'Bulgarian',
        'cs' => 'Czech',
        'da' => 'Danish',
        'el' => 'Greek',
        'et' => 'Estonian',
        'fi' => 'Finnish',
        'hu' => 'Hungarian',
        'lt' => 'Lithuanian',
        'lv' => 'Latvian',
        'ro' => 'Romanian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'sv' => 'Swedish',
        'uk' => 'Ukrainian',
        'fa' => 'Persian',
        'bn' => 'Bengali',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'mr' => 'Marathi',
        'gu' => 'Gujarati'
    ];
}

function locale_dropdown($name, $selected = '')
{
    $locales = get_available_locales();
    return dropdown($name, $locales, $selected);
}

/**
 * admin_locales_catalog()
 *
 * Returns the single source of truth for the admin content locale dropdowns
 * (posts, pages, topics, menus). The catalog is synchronized with the active
 * languages in `tbl_languages` when a database connection is available and
 * falls back to the seven supported languages otherwise, so the application
 * keeps working in CLI/unit-test contexts without a connection.
 *
 * Labels are the native language names (e.g. `Español`, `العربية`), falling
 * back to the English `lang_name` column when `lang_native` is empty. Labels
 * are returned raw; callers MUST escape them before rendering.
 *
 * The catalog is cached with a `static` variable for the duration of the
 * request.
 *
 * @category function
 * @return array<string, string> Map of lang_code => native name
 */
function admin_locales_catalog(): array
{
    static $catalog = null;

    if (is_array($catalog)) {
        return $catalog;
    }

    $catalog = [
        'en' => 'English',
        'ar' => 'العربية',
        'zh' => '中文',
        'fr' => 'Français',
        'ru' => 'Русский',
        'es' => 'Español',
        'id' => 'Bahasa Indonesia',
    ];

    if (class_exists('LanguageDao')) {
        try {
            $languageDao = new LanguageDao();
            $languages = $languageDao->findActiveLanguages();

            if (!empty($languages)) {
                $dbCatalog = [];
                foreach ($languages as $language) {
                    $code = (string)$language['lang_code'];
                    $label = (!empty($language['lang_native']))
                        ? (string)$language['lang_native']
                        : (string)$language['lang_name'];
                    $dbCatalog[$code] = $label;
                }

                $catalog = $dbCatalog;
            }
        } catch (\Throwable $e) {
            // Database unavailable or unusable: keep the static fallback catalog.
        }
    }

    return $catalog;
}

/**
 * admin_locale_select()
 *
 * Renders a native-name locale `<select>` driven by admin_locales_catalog().
 * The markup mirrors the generic `dropdown()` helper (`class="form-control
 * select2"`, name/id set to `$name`) so existing admin CSS/JavaScript keeps
 * working, but every attribute value and label is escaped for XSS safety.
 *
 * A legacy stored locale that is not part of the catalog (e.g. an older
 * `'ja'` value) is appended as an uppercased-code option so editing an
 * existing record never silently rewrites its locale.
 *
 * @category function
 * @param  string $name     The `name`/`id` attribute of the select element
 * @param  string $selected The currently stored locale code, if any
 * @return string Escaped HTML snippet for the select element
 */
function admin_locale_select(string $name, string $selected = ''): string
{
    $options = admin_locales_catalog();

    if ($selected !== '' && !array_key_exists($selected, $options)) {
        $options[$selected] = strtoupper($selected);
    }

    $nameAttr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

    $html = '<select class="form-control select2" name="' . $nameAttr . '" id="' . $nameAttr . '">' . PHP_EOL;

    foreach ($options as $code => $label) {
        $isSelected = ($selected !== '' && (string)$code === $selected) ? ' selected' : '';
        $html .= '<option value="' . htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8') . '"' . $isSelected . '>'
               . htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8') . '</option>' . PHP_EOL;
    }

    $html .= '</select>' . PHP_EOL;

    return $html;
}
