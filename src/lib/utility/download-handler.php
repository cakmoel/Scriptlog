<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class DownloadHandler
 *
 * Core utility class for handling file downloads
 *
 * @category Utility Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */
class DownloadHandler
{
    public const CHUNK_SIZE = 8192;
    public const DEFAULT_EXPIRY = 28800;

    /**
     * Set download headers for file delivery
     *
     * @param string $filename
     * @param string $mimeType
     * @param int $filesize
     * @return void
     */
    public static function setDownloadHeaders($filename, $mimeType, $filesize)
    {
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $filesize);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
    }

    /**
     * Validate download request by identifier
     *
     * @param string $identifier
     * @return bool
     */
    public static function validateDownloadRequest($identifier)
    {
        return (bool)preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $identifier);
    }

    /**
     * Check if hotlinking is allowed based on referer
     *
     * @param string|null $referer
     * @param array $allowedDomains
     * @return bool
     */
    public static function isHotlinkingAllowed($referer, $allowedDomains = [])
    {
        if (empty($allowedDomains)) {
            return true;
        }

        if (empty($referer)) {
            return true;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        return in_array($refererHost, $allowedDomains);
    }

    /**
     * Check if MIME type is allowed based on settings
     *
     * @param string $mimeType
     * @return bool
     */
    public static function isMimeTypeAllowed($mimeType)
    {
        $allowedTypes = DownloadSettings::getAllowedMimeTypes();
        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Deliver file to client with streaming
     *
     * @param string $filepath
     * @param string $filename
     * @param string $mimeType
     * @return void
     */
    public static function deliverFile($filepath, $filename, $mimeType)
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            http_response_code(404);
            echo 'File not found';
            exit;
        }

        $filesize = filesize($filepath);
        self::setDownloadHeaders($filename, $mimeType, $filesize);

        $handle = fopen($filepath, 'rb');
        while (!feof($handle)) {
            echo fread($handle, self::CHUNK_SIZE);
            flush();
        }
        fclose($handle);
        exit;
    }

    /**
     * Get MIME type of a file
     *
     * @param string $filepath
     * @return string
     */
    public static function getMimeType($filepath)
    {
        if (!file_exists($filepath)) {
            return 'application/octet-stream';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filepath);
            finfo_close($finfo);
            return $mimeType;
        }

        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $mimeTypes = self::getMimeTypeDictionary();
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * Sanitize file path to prevent traversal attacks
     *
     * @param string $filename
     * @return string|false
     */
    public static function sanitizeFilePath($filename)
    {
        $filename = basename($filename);

        if (strpos($filename, "\0") !== false) {
            return false;
        }

        if (strpos($filename, '..') !== false) {
            return false;
        }

        return $filename;
    }

    /**
     * Check if download link has expired
     *
     * @param string $beforeExpired
     * @return bool
     */
    public static function isExpired($beforeExpired)
    {
        return time() > (int)$beforeExpired;
    }

    /**
     * Get basic MIME type dictionary
     *
     * @return array
     */
    private static function getMimeTypeDictionary()
    {
        return [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'rtf' => 'application/rtf',
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            '7z' => 'application/x-7z-compressed',
            'tar' => 'application/x-tar',
            'gz' => 'application/gzip',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'sql' => 'application/sql',
        ];
    }
}
