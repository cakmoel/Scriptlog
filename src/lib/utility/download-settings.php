<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class DownloadSettings
 *
 * Manages download configuration settings
 *
 * @category Utility Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */
class DownloadSettings
{
    public const DEFAULT_MIME_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/rtf',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'video/mp4',
        'video/webm',
        'application/json',
    ];

    public const DEFAULT_EXPIRY_HOURS = 8;

    public const KEY_ALLOWED_MIME_TYPES = 'download_allowed_mime_types';
    public const KEY_EXPIRY_HOURS = 'download_expiry_hours';
    public const KEY_HOTLINK_PROTECTION = 'download_hotlink_protection';
    public const KEY_ALLOWED_DOMAINS = 'download_allowed_domains';
    public const KEY_SUPPORT_URL = 'download_support_url';
    public const KEY_SUPPORT_LABEL = 'download_support_label';

    /**
     * Get allowed MIME types from settings
     *
     * @return array
     */
    public static function getAllowedMimeTypes()
    {
        $value = self::getSetting(self::KEY_ALLOWED_MIME_TYPES);

        if (empty($value)) {
            return self::DEFAULT_MIME_TYPES;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : self::DEFAULT_MIME_TYPES;
    }

    /**
     * Set allowed MIME types
     *
     * @param array $types
     * @return bool
     */
    public static function setAllowedMimeTypes($types)
    {
        return self::saveSetting(self::KEY_ALLOWED_MIME_TYPES, json_encode($types));
    }

    /**
     * Get download expiry hours
     *
     * @return int
     */
    public static function getDownloadExpiry()
    {
        $value = self::getSetting(self::KEY_EXPIRY_HOURS);
        return $value ? (int)$value : self::DEFAULT_EXPIRY_HOURS;
    }

    /**
     * Set download expiry hours
     *
     * @param int $hours
     * @return bool
     */
    public static function setDownloadExpiry($hours)
    {
        return self::saveSetting(self::KEY_EXPIRY_HOURS, (int)$hours);
    }

    /**
     * Check if hotlink protection is enabled
     *
     * @return bool
     */
    public static function isHotlinkProtectionEnabled()
    {
        $value = self::getSetting(self::KEY_HOTLINK_PROTECTION);
        return $value === 'yes';
    }

    /**
     * Set hotlink protection
     *
     * @param bool $enabled
     * @return bool
     */
    public static function setHotlinkProtection($enabled)
    {
        return self::saveSetting(self::KEY_HOTLINK_PROTECTION, $enabled ? 'yes' : 'no');
    }

    /**
     * Get allowed domains for hotlink protection
     *
     * @return array
     */
    public static function getAllowedDomains()
    {
        $value = self::getSetting(self::KEY_ALLOWED_DOMAINS);

        if (empty($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Set allowed domains
     *
     * @param array $domains
     * @return bool
     */
    public static function setAllowedDomains($domains)
    {
        return self::saveSetting(self::KEY_ALLOWED_DOMAINS, json_encode($domains));
    }

    /**
     * Get support URL
     *
     * @return string
     */
    public static function getSupportUrl()
    {
        return self::getSetting(self::KEY_SUPPORT_URL) ?? '';
    }

    /**
     * Set support URL
     *
     * @param string $url
     * @return bool
     */
    public static function setSupportUrl($url)
    {
        return self::saveSetting(self::KEY_SUPPORT_URL, $url);
    }

    /**
     * Get support label
     *
     * @return string
     */
    public static function getSupportLabel()
    {
        return self::getSetting(self::KEY_SUPPORT_LABEL) ?? 'Support';
    }

    /**
     * Set support label
     *
     * @param string $label
     * @return bool
     */
    public static function setSupportLabel($label)
    {
        return self::saveSetting(self::KEY_SUPPORT_LABEL, $label);
    }

    /**
     * Get all settings as array
     *
     * @return array
     */
    public static function getAllSettings()
    {
        return [
            'allowed_mime_types' => self::getAllowedMimeTypes(),
            'expiry_hours' => self::getDownloadExpiry(),
            'hotlink_protection' => self::isHotlinkProtectionEnabled(),
            'allowed_domains' => self::getAllowedDomains(),
            'support_url' => self::getSupportUrl(),
            'support_label' => self::getSupportLabel(),
        ];
    }

    /**
     * Save all settings at once
     *
     * @param array $settings
     * @return bool
     */
    public static function saveSettings($settings)
    {
        $result = true;

        if (isset($settings['allowed_mime_types'])) {
            $result = $result && self::setAllowedMimeTypes($settings['allowed_mime_types']);
        }

        if (isset($settings['expiry_hours'])) {
            $result = $result && self::setDownloadExpiry($settings['expiry_hours']);
        }

        if (isset($settings['hotlink_protection'])) {
            $result = $result && self::setHotlinkProtection($settings['hotlink_protection']);
        }

        if (isset($settings['allowed_domains'])) {
            $result = $result && self::setAllowedDomains($settings['allowed_domains']);
        }

        if (isset($settings['support_url'])) {
            $result = $result && self::setSupportUrl($settings['support_url']);
        }

        if (isset($settings['support_label'])) {
            $result = $result && self::setSupportLabel($settings['support_label']);
        }

        return $result;
    }

    /**
     * Get a single setting from database
     *
     * @param string $key
     * @return string|null
     */
    private static function getSetting($key)
    {
        if (!class_exists('ConfigurationDao')) {
            return null;
        }

        try {
            $dao = new ConfigurationDao();
            $sanitizer = new Sanitize();
            $result = $dao->findConfigByName($key, $sanitizer);

            return $result['setting_value'] ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Save a single setting to database
     *
     * @param string $key
     * @param string $value
     * @return bool
     */
    private static function saveSetting($key, $value)
    {
        if (!class_exists('ConfigurationDao')) {
            return false;
        }

        try {
            $dao = new ConfigurationDao();
            $sanitizer = new Sanitize();

            $existing = $dao->findConfigByName($key, $sanitizer);

            if (!empty($existing)) {
                return $dao->updateConfigByName($key, $value, $sanitizer);
            } else {
                return $dao->createConfig($key, $value, $sanitizer);
            }
        } catch (Throwable $e) {
            return false;
        }
    }
}
