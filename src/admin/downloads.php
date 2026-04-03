<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * admin/downloads.php
 *
 * Downloads management admin page
 *
 * @category Admin Page
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 *
 */

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$identifier = isset($_GET['identifier']) ? htmlentities(strip_tags($_GET['identifier'])) : "";

$downloadModel = class_exists('DownloadModel') ? new DownloadModel() : "";
$mediaDao = class_exists('MediaDao') ? new MediaDao() : "";
$downloadService = class_exists('DownloadService') ? new DownloadService($downloadModel, $mediaDao) : "";
$downloadController = class_exists('DownloadAdminController') ? new DownloadAdminController($downloadService) : "";

try {
    switch ($action) {
        case ActionConst::DELETEDOWNLOAD:
            if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if (empty($identifier)) {
                    direct_page('index.php?load=downloads&error=invalidId', 400);
                }

                $downloadController->deleteDownload($identifier);
                $_SESSION['status'] = 'downloadDeleted';
                direct_page('index.php?load=downloads', 302);
            }

            break;

        case 'expire':
            if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if (empty($identifier)) {
                    direct_page('index.php?load=downloads&error=invalidId', 400);
                }

                $downloadController->expireDownload($identifier);
                $_SESSION['status'] = 'downloadExpired';
                direct_page('index.php?load=downloads', 302);
            }

            break;

        case 'regenerate':
            if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if (empty($identifier)) {
                    direct_page('index.php?load=downloads&error=invalidId', 400);
                }

                $downloadController->regenerateDownload($identifier);
                $_SESSION['status'] = 'downloadRegenerated';
                direct_page('index.php?load=downloads', 302);
            }

            break;

        case 'history':
            if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $mediaId = isset($_GET['mediaId']) ? (int)$_GET['mediaId'] : 0;

                if ($mediaId > 0) {
                    $downloadController->viewDownloadHistory($mediaId);
                } else {
                    direct_page('index.php?load=downloads&error=invalidId', 400);
                }
            }

            break;

        case 'bulkExpire':
            if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if ($downloadController->bulkExpireDownloads()) {
                    $_SESSION['status'] = 'downloadsExpired';
                }
                direct_page('index.php?load=downloads', 302);
            }

            break;

        case 'bulkRegenerate':
            if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if ($downloadController->bulkRegenerateDownloads()) {
                    $_SESSION['status'] = 'downloadsRegenerated';
                }
                direct_page('index.php?load=downloads', 302);
            }

            break;

        case 'bulkDelete':
            if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                if ($downloadController->bulkDeleteDownloads()) {
                    $_SESSION['status'] = 'downloadsDeleted';
                }
                direct_page('index.php?load=downloads', 302);
            }

            break;

        default:
            if (false === $app->authenticator->userAccessControl(ActionConst::MEDIALIB)) {
                direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
            } else {
                $downloadController->listItems();
            }

            break;
    }
} catch (Throwable $th) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($th);
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
