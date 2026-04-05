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
