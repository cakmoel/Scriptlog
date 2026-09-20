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
 * Single source of truth for the supported admin content locales.
 *
 * Falls back to the exact seven supported languages (with their native
 * names) when no database connection is available. When a LanguageDao is
 * available the catalog is overridden by the active languages from
 * tbl_languages (lang_code => native label).
 *
 * @category function
 * @see      lib/dao/LanguageDao.php
 * @return array<string,string>
 *
 */
function admin_locales_catalog()
{
    static $catalog = null;

    if ($catalog !== null) {
        return $catalog;
    }

    $catalog = array(
        'en' => 'English',
        'ar' => 'العربية',
        'zh' => '中文',
        'fr' => 'Français',
        'ru' => 'Русский',
        'es' => 'Español',
        'id' => 'Bahasa Indonesia',
    );

    if (class_exists('LanguageDao')) {
        try {
            $dao = new LanguageDao();

            if (method_exists($dao, 'findActiveLanguages')) {
                $rows = $dao->findActiveLanguages();

                if (is_array($rows) && !empty($rows)) {
                    $dbCatalog = array();

                    foreach ($rows as $row) {
                        if (!is_array($row)) {
                            continue;
                        }

                        $code = isset($row['lang_code']) ? (string)$row['lang_code'] : '';

                        if ($code === '') {
                            continue;
                        }

                        $label = isset($row['lang_native']) && $row['lang_native'] !== ''
                            ? (string)$row['lang_native']
                            : (isset($row['lang_name']) ? (string)$row['lang_name'] : $code);

                        $dbCatalog[$code] = $label;
                    }

                    if (!empty($dbCatalog)) {
                        $catalog = $dbCatalog;
                    }
                }
            }
        } catch (\Scriptlog\Core\DbException $e) {
            /* Database unavailable: keep the fallback catalog. */
        }
    }

    return $catalog;
}

/**
 * admin_locale_select()
 *
 * Renders the admin content locale <select> with native labels.
 *
 * Every attribute and label is escaped, and a legacy stored locale that is
 * not part of the catalog is preserved (uppercased) as a selected option so
 * previously saved values round-trip safely.
 *
 * @category function
 * @param  string $name     Select name/id attribute
 * @param  string $selected Stored locale code (optional)
 * @return string
 *
 */
function admin_locale_select($name, $selected = '')
{
    $safeName = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8');
    $selected = (string)$selected;

    $html = '<select class="form-control select2" name="' . $safeName . '" id="' . $safeName . '">' . PHP_EOL;

    $hasSelected = false;

    foreach (admin_locales_catalog() as $code => $label) {
        $select = ($selected !== '' && $selected === (string)$code) ? ' selected' : '';

        if ($select !== '') {
            $hasSelected = true;
        }

        $safeCode = htmlspecialchars((string)$code, ENT_QUOTES, 'UTF-8');
        $safeLabel = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');

        $html .= '<option value="' . $safeCode . '"' . $select . '>' . $safeLabel . '</option>' . PHP_EOL;
    }

    if (!$hasSelected && $selected !== '') {
        $safeSelected = htmlspecialchars($selected, ENT_QUOTES, 'UTF-8');
        $html .= '<option value="' . $safeSelected . '" selected>'
            . htmlspecialchars(strtoupper($selected), ENT_QUOTES, 'UTF-8')
            . '</option>' . PHP_EOL;
    }

    $html .= '</select>' . PHP_EOL;

    return $html;
}
