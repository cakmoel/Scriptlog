<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Map a MIME type to its image format identifier.
 *
 * Returns a short format name ('png', 'gif', 'jpeg', 'webp', 'bmp')
 * suitable for constructing GD function names or Intervention save
 * parameters, or null if the MIME type is not a recognised image.
 *
 * @param string $mimeType
 * @return string|null
 */
function get_image_format(string $mimeType): ?string
{
    $map = [
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/jpeg' => 'jpeg',
        'image/pjpeg' => 'jpeg',
        'image/jpg' => 'jpeg',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
    ];

    return $map[$mimeType] ?? null;
}

/**
 * Centralized MIME type to group mapping.
 *
 * Returns the media group ('image', 'audio', 'video', 'doc') for a given
 * MIME type, or null if the type is not recognised.
 *
 * All other MIME-helpers in the codebase should delegate to this function
 * so that adding support for a new MIME type (e.g. image/avif) requires
 * only one change.
 *
 * @param string $mimeType
 * @return string|null
 */
function get_mime_group(string $mimeType): ?string
{
    $groups = [
        'image' => [
            'image/jpeg', 'image/pjpeg', 'image/jpg', 'image/png',
            'image/gif', 'image/webp', 'image/bmp', 'image/tiff', 'image/x-icon',
        ],
        'audio' => [
            'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/aac', 'audio/flac',
        ],
        'video' => [
            'video/mp4', 'video/webm', 'video/ogg', 'video/mpeg',
            'video/quicktime', 'video/x-msvideo',
        ],
        'doc' => [
            'application/pdf', 'application/msword', 'application/vnd.ms-excel',
            'application/rtf', 'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/zip', 'application/x-zip', 'multipart/x-zip',
            'application/x-zip-compressed', 'application/x-rar-compressed',
            'application/rar', 'application/vnd.microsoft.portable-executable',
            'application/octet-stream',
        ],
    ];

    foreach ($groups as $group => $types) {
        foreach ($types as $type) {
            if (strpos($type, '*') !== false) {
                $pattern = '/^' . str_replace('\*', '.*', preg_quote($type, '/')) . '$/';
                if (preg_match($pattern, $mimeType)) {
                    return $group;
                }
            } elseif ($mimeType === $type) {
                return $group;
            }
        }
    }

    return null;
}
