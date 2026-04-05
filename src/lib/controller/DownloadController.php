<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class DownloadController
 *
 * Handle frontend download requests
 *
 * @category Controller Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */
class DownloadController extends BaseApp
{
    private $downloadService;
    private $view;

    public function __construct(DownloadService $downloadService)
    {
        $this->downloadService = $downloadService;
    }

    /**
     * List items - not used for downloads
     */
    protected function listItems()
    {
        // Not used
    }

    /**
     * Insert - not used for downloads
     */
    protected function insert()
    {
        // Not used
    }

    /**
     * Update - not used for downloads
     */
    protected function update($id)
    {
        // Not used
    }

    /**
     * Remove - not used for downloads
     */
    protected function remove($id)
    {
        // Not used
    }

    /**
     * Handle download request
     *
     * @param string $identifier
     */
    public function download($identifier)
    {
        $downloadInfo = $this->downloadService->validateDownloadRequest($identifier);

        if (!$downloadInfo) {
            http_response_code(404);
            echo 'Download link not found';
            exit;
        }

        if ($this->downloadService->isDownloadExpired($identifier)) {
            http_response_code(410);
            echo 'Download link has expired';
            exit;
        }

        $media = $this->downloadService->getMediaByIdentifier($identifier);

        if (!$media) {
            http_response_code(404);
            echo 'File not found';
            exit;
        }

        $mimeType = $media['media_type'];

        if (!$this->downloadService->isMimeTypeAllowed($mimeType)) {
            http_response_code(403);
            echo 'File type not allowed';
            exit;
        }

        $hotlinkProtection = DownloadSettings::isHotlinkProtectionEnabled();

        if ($hotlinkProtection) {
            $allowedDomains = DownloadSettings::getAllowedDomains();
            $referer = $_SERVER['HTTP_REFERER'] ?? '';

            if (!DownloadHandler::isHotlinkingAllowed($referer, $allowedDomains)) {
                http_response_code(403);
                echo 'Hotlinking not allowed';
                exit;
            }
        }

        $ipAddress = function_exists('get_ip_address') ? get_ip_address() : '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $this->downloadService->recordDownloadAttempt(
            $downloadInfo['media_id'],
            $identifier,
            $ipAddress,
            $userAgent,
            'success'
        );

        $filepath = $this->getFilePath($media);

        if (!file_exists($filepath)) {
            http_response_code(404);
            echo 'File not found on server';
            exit;
        }

        $filename = basename($media['media_filename']);
        DownloadHandler::deliverFile($filepath, $filename, $mimeType);
    }

    /**
     * Get download info page
     *
     * @param string $identifier
     */
    public function getDownloadPage($identifier)
    {
        $downloadInfo = $this->downloadService->validateDownloadRequest($identifier);

        if (!$downloadInfo) {
            http_response_code(404);
            return [
                'error' => 'Download link not found'
            ];
        }

        if ($this->downloadService->isDownloadExpired($identifier)) {
            return [
                'error' => 'Download link has expired',
                'expired' => true
            ];
        }

        $media = $this->downloadService->getMediaByIdentifier($identifier);

        if (!$media) {
            return [
                'error' => 'File not found'
            ];
        }

        $downloadUrl = $this->downloadService->getDownloadUrl($media['ID'], $identifier);
        $supportUrl = DownloadSettings::getSupportUrl();
        $supportLabel = DownloadSettings::getSupportLabel();

        $fileSize = $this->getFileSize($media);

        return [
            'media' => $media,
            'download_url' => $downloadUrl,
            'support_url' => $supportUrl,
            'support_label' => $supportLabel,
            'file_size' => $fileSize,
            'expires_at' => $downloadInfo['before_expired']
        ];
    }

    /**
     * Get file path based on media type
     *
     * @param array $media
     * @return string
     */
    private function getFilePath($media)
    {
        $filename = basename($media['media_filename']);
        $mediaType = $media['media_type'];

        $basePath = dirname(__FILE__) . '/../../public/files/';

        if (strpos($mediaType, 'image/') === 0) {
            return $basePath . 'pictures/' . $filename;
        } elseif (strpos($mediaType, 'audio/') === 0) {
            return $basePath . 'audio/' . $filename;
        } elseif (strpos($mediaType, 'video/') === 0) {
            return $basePath . 'video/' . $filename;
        } else {
            return $basePath . 'docs/' . $filename;
        }
    }

    /**
     * Get formatted file size
     *
     * @param array $media
     * @return string
     */
    private function getFileSize($media)
    {
        $filepath = $this->getFilePath($media);

        if (!file_exists($filepath)) {
            return 'Unknown';
        }

        $size = filesize($filepath);

        if (function_exists('format_size_unit')) {
            return format_size_unit($size);
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
