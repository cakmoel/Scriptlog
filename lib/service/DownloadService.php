<?php

namespace Scriptlog\Service;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class DownloadService
 *
 * Business logic for download management
 *
 * @category Service Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */

use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\MediaDao;
use Scriptlog\Model\DownloadModel;

final class DownloadService
{
    private $downloadModel;
    private $mediaDao;
    private $sanitizer;

    public function __construct(DownloadModel $downloadModel, MediaDao $mediaDao)
    {
        $this->downloadModel = $downloadModel;
        $this->mediaDao = $mediaDao;
        $this->sanitizer = new Sanitize();
    }

    /**
     * Validate download request
     *
     * @param string $identifier
     * @return array|null
     */
    public function validateDownloadRequest($identifier)
    {
        if (!\DownloadUtility::validateDownloadRequest($identifier)) {
            return null;
        }

        return $this->getDownloadInfo($identifier);
    }

    /**
     * Get download info by identifier
     *
     * @param string $identifier
     * @return array|null
     */
    public function getDownloadInfo($identifier)
    {
        return $this->downloadModel->getMediaDownloadURL($identifier, $this->sanitizer);
    }

    /**
     * Get media by identifier
     *
     * @param string $identifier
     * @return array|null
     */
    public function getMediaByIdentifier($identifier)
    {
        $downloadInfo = $this->getDownloadInfo($identifier);

        if (!$downloadInfo || !isset($downloadInfo['media_id'])) {
            return null;
        }

        return $this->mediaDao->findMediaById($downloadInfo['media_id'], $this->sanitizer);
    }

    /**
     * Check if download is expired
     *
     * @param string $identifier
     * @return bool
     */
    public function isDownloadExpired($identifier)
    {
        $downloadInfo = $this->getDownloadInfo($identifier);

        if (!$downloadInfo || !isset($downloadInfo['before_expired'])) {
            return true;
        }

        return \DownloadUtility::isExpired($downloadInfo['before_expired']);
    }

    /**
     * Check if the requester IP is allowed to redeem the download grant
     *
     * Compares the IP address captured when the grant was created against the
     * current requester address. Fails closed when no usable grant information
     * is available so unbound or unknown identifiers are never treated as valid.
     *
     * @param string $identifier
     * @param string $ipAddress
     * @return bool
     */
    public function isDownloadIpAllowed($identifier, $ipAddress)
    {
        $downloadInfo = $this->getDownloadInfo($identifier);

        if (!$downloadInfo || !isset($downloadInfo['ip_address'])) {
            return false;
        }

        $storedIpAddress = (string)$downloadInfo['ip_address'];

        // Fail closed: an empty stored address never matches a requester.
        return $storedIpAddress !== '' && $storedIpAddress === (string)$ipAddress;
    }

    /**
     * Check if media is downloadable at redemption time
     *
     * Re-validates the media access level and publish status captured at grant
     * creation against the current record, failing closed when the media is
     * private or no longer enabled for download.
     *
     * @param array $media
     * @return bool
     */
    public function isMediaDownloadable(array $media)
    {
        if (empty($media)) {
            return false;
        }

        if (!isset($media['media_access']) || $media['media_access'] !== 'public') {
            return false;
        }

        if (!isset($media['media_status']) || (int)$media['media_status'] !== 1) {
            return false;
        }

        return true;
    }

    /**
     * Check if MIME type is allowed
     *
     * @param string $mimeType
     * @return bool
     */
    public function isMimeTypeAllowed($mimeType)
    {
        return \DownloadUtility::isMimeTypeAllowed($mimeType);
    }

    /**
     * Create download record
     *
     * @param int $mediaId
     * @param string $ipAddress
     * @return string
     */
    public function createDownloadRecord($mediaId, $ipAddress)
    {
        $identifier = $this->generateDownloadIdentifier();
        $expiryHours = \DownloadSettings::getDownloadExpiry();
        $beforeExpired = time() + ($expiryHours * 3600);

        $this->downloadModel->createMediaDownload([
            'media_id' => $mediaId,
            'media_identifier' => $identifier,
            'before_expired' => $beforeExpired,
            'ip_address' => $ipAddress
        ]);

        return $identifier;
    }

    /**
     * Delete download record
     *
     * @param string $identifier
     * @return bool
     */
    public function deleteDownloadRecord($identifier)
    {
        return $this->downloadModel->deleteMediaDownload($identifier, $this->sanitizer);
    }

    /**
     * Delete multiple download records
     *
     * @param array $identifiers
     * @return bool
     */
    public function deleteDownloadRecords($identifiers)
    {
        $result = true;

        foreach ($identifiers as $identifier) {
            $result = $result && $this->deleteDownloadRecord($identifier);
        }

        return $result;
    }

    /**
     * Expire a download record
     *
     * @param string $identifier
     * @return bool
     */
    public function expireDownloadRecord($identifier)
    {
        $this->downloadModel->updateMediaDownload($this->sanitizer, [
            'media_identifier' => $this->generateDownloadIdentifier(),
            'before_expired' => time() - 1,
            'ip_address' => ''
        ], $identifier);

        return true;
    }

    /**
     * Expire multiple download records
     *
     * @param array $identifiers
     * @return bool
     */
    public function expireDownloadRecords($identifiers)
    {
        $result = true;

        foreach ($identifiers as $identifier) {
            $result = $result && $this->expireDownloadRecord($identifier);
        }

        return $result;
    }

    /**
     * Regenerate download identifier
     *
     * @param string $identifier
     * @return string|false
     */
    public function regenerateDownloadRecord($identifier)
    {
        $newIdentifier = $this->generateDownloadIdentifier();
        $expiryHours = \DownloadSettings::getDownloadExpiry();
        $beforeExpired = time() + ($expiryHours * 3600);

        $this->downloadModel->updateMediaDownload($this->sanitizer, [
            'media_identifier' => $newIdentifier,
            'before_expired' => $beforeExpired,
            'ip_address' => ''
        ], $identifier);

        return $newIdentifier;
    }

    /**
     * Regenerate multiple download records
     *
     * @param array $identifiers
     * @return bool
     */
    public function regenerateDownloadRecords($identifiers)
    {
        $result = true;

        foreach ($identifiers as $identifier) {
            $result = $result && ($this->regenerateDownloadRecord($identifier) !== false);
        }

        return $result;
    }

    /**
     * Generate download identifier
     *
     * Token identifiers for media downloads are generated through the
     * CSPRNG-backed generate_media_identifier() helper (random_bytes, failing
     * closed when no secure RNG is available). The previous insecure fallback
     * generator was removed so download token generation never silently
     * downgrades to a weak source.
     *
     * @return string
     */
    public function generateDownloadIdentifier()
    {
        if (function_exists('generate_media_identifier')) {
            return generate_media_identifier();
        }

        // random_bytes() is a CSPRNG available on all PHP versions supported
        // by Scriptlog (>= 7.4); fail closed instead of silently downgrading
        // token material to an insecure generator.
        if (function_exists('random_bytes')) {
            $data = random_bytes(16);
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes(16);
        } else {
            scriptlog_error('no cryptographically secure random function available');
            return '';
        }

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        $identifier = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

        // vsprintf() only fails on a format/argument mismatch; fail closed
        // rather than letting a boolean leak into an identifier column.
        return is_string($identifier) ? $identifier : '';
    }

    /**
     * Refresh download expiry
     *
     * @param string $identifier
     * @return bool
     */
    public function refreshDownloadExpiry($identifier)
    {
        $expiryHours = \DownloadSettings::getDownloadExpiry();
        $beforeExpired = time() + ($expiryHours * 3600);

        $this->downloadModel->updateMediaDownload($this->sanitizer, [
            'before_expired' => $beforeExpired
        ], $identifier);

        return true;
    }

    /**
     * Get download URL
     *
     * @param int $_mediaId
     * @param string $identifier
     * @return string
     */
    public function getDownloadUrl($_mediaId, $identifier)
    {
        $appUrl = function_exists('app_url') ? app_url() : '';

        if (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes') {
            return $appUrl . '/download/' . $identifier . '/file';
        }

        return $appUrl . '/?download=' . $identifier . '/file';
    }

    /**
     * Record download attempt
     *
     * @param int $mediaId
     * @param string $identifier
     * @param string $ipAddress
     * @param string $userAgent
     * @param string $status
     * @return bool
     */
    public function recordDownloadAttempt($mediaId, $identifier, $ipAddress, $userAgent, $status = 'success')
    {
        $this->downloadModel->createDownloadLog([
            'media_id' => $mediaId,
            'media_identifier' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'status' => $status
        ]);

        return true;
    }

    /**
     * Get download statistics
     *
     * @return array
     */
    public function getDownloadStatistics()
    {
        $allDownloads = $this->getAllDownloads();

        $totalDownloads = 0;
        $activeLinks = 0;
        $expiredLinks = 0;

        foreach ($allDownloads as $download) {
            $totalDownloads += $this->getDownloadCountByMedia($download['media_id']);
            if ($this->isDownloadExpired($download['media_identifier'])) {
                $expiredLinks++;
                continue;
            }
            $activeLinks++;
        }

        return [
            'total_downloads' => $totalDownloads,
            'active_links' => $activeLinks,
            'expired_links' => $expiredLinks,
            'total_files' => count($allDownloads)
        ];
    }

    /**
     * Get download count by media
     *
     * @param int $mediaId
     * @return int
     */
    public function getDownloadCountByMedia($mediaId)
    {
        return $this->downloadModel->getDownloadCountByMedia($mediaId);
    }

    /**
     * Get downloads by media
     *
     * @param int $mediaId
     * @return array
     */
    public function getDownloadsByMedia($mediaId)
    {
        return $this->downloadModel->getDownloadHistoryByMedia($mediaId);
    }

    /**
     * Get download history
     *
     * @param int $limit
     * @return array
     */
    public function getDownloadHistory($limit = 50)
    {
        return $this->downloadModel->getDownloadHistory($limit);
    }

    /**
     * Get file type distribution
     *
     * @return array
     */
    public function getFileTypeDistribution()
    {
        return $this->downloadModel->getFileTypeDistribution();
    }

    /**
     * Get recent downloads
     *
     * @param int $limit
     * @return array
     */
    public function getRecentDownloads($limit = 10)
    {
        return $this->downloadModel->getRecentDownloads($limit);
    }

    /**
     * Get all downloads with media info
     *
     * @return array
     */
    public function getAllDownloads()
    {
        return $this->downloadModel->getAllMediaDownload();
    }
}
