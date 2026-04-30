<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Get download URL based on permalink setting
 * 
 * @param string $identifier UUID identifier
 * @param string $type 'page' for download page, 'file' for direct download
 * @return string Full download URL
 */
function get_download_link($identifier, $type = 'page')
{
    if (empty($identifier)) {
        return '';
    }
    
    $config = read_config(invoke_config());
    $appUrl = isset($config['app']['url']) ? rtrim($config['app']['url'], '/') : '';
    
    if (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes') {
        // SEO-friendly URLs (enabled)
        if ($type === 'file') {
            return $appUrl . '/download/' . $identifier . '/file';
        } else {
            return $appUrl . '/download/' . $identifier;
        }
    } else {
        // Query string URLs (disabled)
        if ($type === 'file') {
            return $appUrl . '/?download=' . $identifier . '/file';
        } else {
            return $appUrl . '/?download=' . $identifier;
        }
    }
}

/**
 * Get both download page and direct file links
 * 
 * @param string $identifier UUID identifier
 * @return array ['page' => url, 'file' => url]
 */
function get_download_links($identifier)
{
    return [
        'page' => get_download_link($identifier, 'page'),
        'file' => get_download_link($identifier, 'file')
    ];
}
