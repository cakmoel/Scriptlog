<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class DownloadAdminController
 *
 * Handle admin download management
 *
 * @category Controller Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */
class DownloadAdminController extends BaseApp
{
    private $downloadService;
    private $view;

    public function __construct(DownloadService $downloadService)
    {
        $this->downloadService = $downloadService;
    }

    /**
     * List all downloads - displays download management page
     */
    public function listItems()
    {
        $errors = [];
        $status = [];
        $checkError = true;
        $checkStatus = false;

        if (isset($_SESSION['error'])) {
            $checkError = false;
            unset($_SESSION['error']);
        }

        if (isset($_SESSION['status'])) {
            $checkStatus = true;
            ($_SESSION['status'] == 'downloadsExpired') ? array_push($status, "Selected downloads have been expired") : "";
            ($_SESSION['status'] == 'downloadsRegenerated') ? array_push($status, "Selected downloads have been regenerated") : "";
            ($_SESSION['status'] == 'downloadsDeleted') ? array_push($status, "Selected downloads have been deleted") : "";
            ($_SESSION['status'] == 'downloadExpired') ? array_push($status, "Download has been expired") : "";
            ($_SESSION['status'] == 'downloadRegenerated') ? array_push($status, "Download has been regenerated") : "";
            ($_SESSION['status'] == 'downloadDeleted') ? array_push($status, "Download has been deleted") : "";
            ($_SESSION['status'] == 'downloadLinkCreated') ? array_push($status, "Download link has been created") : "";
            unset($_SESSION['status']);
        }

        $bulkAction = $_POST['bulk-action'] ?? '';
        $downloads = $_POST['downloads'] ?? [];

        if (!empty($bulkAction) && !empty($downloads)) {
            if (!csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                $errors[] = "Invalid token";
            } else {
                switch ($bulkAction) {
                    case 'expire':
                        if ($this->bulkExpireDownloads()) {
                            $_SESSION['status'] = 'downloadsExpired';
                        }
                        break;
                    case 'regenerate':
                        if ($this->bulkRegenerateDownloads()) {
                            $_SESSION['status'] = 'downloadsRegenerated';
                        }
                        break;
                    case 'delete':
                        if ($this->bulkDeleteDownloads()) {
                            $_SESSION['status'] = 'downloadsDeleted';
                        }
                        break;
                }
                direct_page('index.php?load=downloads', 302);
            }
        }

        $this->setView('all-downloads');
        $this->setPageTitle('Downloads');
        $this->view->set('pageTitle', $this->getPageTitle());

        if (!$checkError) {
            $this->view->set('errors', $errors);
        }

        if ($checkStatus) {
            $this->view->set('status', $status);
        }

        $this->view->set('allDownloads', $this->downloadService->getAllDownloads());
        $this->view->set('statistics', $this->downloadService->getDownloadStatistics());
        $this->view->set('fileDistribution', $this->downloadService->getFileTypeDistribution());
        $this->view->set('downloadService', $this->downloadService);
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));

        return $this->view->render();
    }

    /**
     * View download statistics
     */
    public function viewStatistics()
    {
        return $this->downloadService->getDownloadStatistics();
    }

    /**
     * View download history
     *
     * @param int $downloadId
     */
    public function viewDownloadHistory($mediaId)
    {
        return $this->downloadService->getDownloadsByMedia($mediaId);
    }

    /**
     * Get download history for AJAX
     *
     * @param int $mediaId
     * @return array
     */
    public function getDownloadHistoryForMedia($mediaId)
    {
        return $this->downloadService->getDownloadsByMedia($mediaId);
    }

    /**
     * Expire a single download
     *
     * @param string $identifier
     * @return bool
     */
    public function expireDownload($identifier)
    {
        return $this->downloadService->expireDownloadRecord($identifier);
    }

    /**
     * Regenerate a single download
     *
     * @param string $identifier
     * @return string|false
     */
    public function regenerateDownload($identifier)
    {
        return $this->downloadService->regenerateDownloadRecord($identifier);
    }

    /**
     * Delete a single download
     *
     * @param string $identifier
     * @return bool
     */
    public function deleteDownload($identifier)
    {
        return $this->downloadService->deleteDownloadRecord($identifier);
    }

    /**
     * Bulk expire downloads
     *
     * @return bool
     */
    public function bulkExpireDownloads()
    {
        $identifiers = $this->getIdentifiersFromPost();

        if (empty($identifiers)) {
            return false;
        }

        return $this->downloadService->expireDownloadRecords($identifiers);
    }

    /**
     * Bulk regenerate downloads
     *
     * @return bool
     */
    public function bulkRegenerateDownloads()
    {
        $identifiers = $this->getIdentifiersFromPost();

        if (empty($identifiers)) {
            return false;
        }

        return $this->downloadService->regenerateDownloadRecords($identifiers);
    }

    /**
     * Bulk delete downloads
     *
     * @return bool
     */
    public function bulkDeleteDownloads()
    {
        $identifiers = $this->getIdentifiersFromPost();

        if (empty($identifiers)) {
            return false;
        }

        return $this->downloadService->deleteDownloadRecords($identifiers);
    }

    /**
     * Create a new download link for a media file
     *
     * @param int $mediaId
     * @param string $ipAddress
     * @return string|false New identifier or false
     */
    public function createDownloadLink($mediaId, $ipAddress = '')
    {
        return $this->downloadService->createDownloadRecord($mediaId, $ipAddress);
    }

    /**
     * Bulk create download links for media IDs
     *
     * @param array $mediaIds
     * @return bool
     */
    public function bulkCreateDownloadLinks($mediaIds)
    {
        $result = true;

        foreach ($mediaIds as $mediaId) {
            $newIdentifier = $this->downloadService->createDownloadRecord((int)$mediaId, '');
            $result = $result && ($newIdentifier !== false);
        }

        return $result;
    }

    /**
     * Get identifiers from POST data
     *
     * @return array
     */
    private function getIdentifiersFromPost()
    {
        $identifiers = [];

        if (isset($_POST['downloads']) && is_array($_POST['downloads'])) {
            $identifiers = $_POST['downloads'];
        }

        return $identifiers;
    }

    /**
     * Get recent downloads
     *
     * @param int $limit
     * @return array
     */
    public function getRecentDownloads($limit = 10)
    {
        return $this->downloadService->getRecentDownloads($limit);
    }

    /**
     * Get file type distribution
     *
     * @return array
     */
    public function getFileTypeDistribution()
    {
        return $this->downloadService->getFileTypeDistribution();
    }

    /**
     * Get download history
     *
     * @param int $limit
     * @return array
     */
    public function getDownloadHistory($limit = 50)
    {
        return $this->downloadService->getDownloadHistory($limit);
    }

    /**
     * Render download history page
     *
     * @param int $mediaId
     * @param string $mediaFilename
     * @param array $history
     * @return void
     */
    public function viewDownloadHistoryPage($mediaId, $mediaFilename, $history)
    {
        $this->setView('download-history-page');
        $this->setPageTitle('Download History');
        $this->view->set('pageTitle', $this->getPageTitle());
        $this->view->set('mediaId', $mediaId);
        $this->view->set('mediaFilename', $mediaFilename);
        $this->view->set('history', $history);
        $this->view->set('csrfToken', csrf_generate_token('csrfToken'));
        $this->view->render();
    }

    /**
     * Set view for rendering
     *
     * @param string $viewName
     */
    protected function setView($viewName)
    {
        $this->view = new View('admin', 'ui', 'downloads', $viewName);
    }

    /**
     * Insert - not implemented for downloads
     */
    protected function insert()
    {
        // Not used for downloads admin
    }

    /**
     * Update - not implemented for downloads
     *
     * @param int $id
     */
    protected function update($id)
    {
        // Not used for downloads admin
    }

    /**
     * Remove - not implemented for downloads
     *
     * @param int $id
     */
    protected function remove($id)
    {
        // Not used for downloads admin
    }
}
