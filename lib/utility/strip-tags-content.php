<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * strip_tags_content
 *
 * Remove the HTML tags
 *
 * @param string $text
 * @param string $tags
 * @param bool $invert
 * @return mixed
 *
 */
function strip_tags_content($text, $tags, $invert = false)
{
    preg_match_all('/<(.+?)[\s]*\/?[\s]*>/si', trim($tags), $tags);
    $tags = array_unique($tags[1]);

    if (is_array($tags) && (!empty($tags))) {
        if ($invert === false) {
            return preg_replace('@<(?!(?:' . implode('|', $tags) . ')\b)(\w+)\b.*?>.*?</\1>@si', '', $text);
        } else {
            return preg_replace('@<(' . implode('|', $tags) . ')\b.*?>.*?</\1>@si', '', $text);
        }
    } elseif ($invert === false) {
        return preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
    }
    return $text;
}
