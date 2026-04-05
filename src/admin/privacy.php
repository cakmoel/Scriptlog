<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action'])) : "";
$page = isset($_GET['p']) ? htmlentities(strip_tags($_GET['p'])) : "index";

if (!isset($sanitizer) || empty($sanitizer)) {
    $sanitizer = class_exists('Sanitize') ? new Sanitize() : null;
}

if (false === $app->authenticator->userAccessControl(ActionConst::PRIVACY)) {
    direct_page('index.php?load=403&forbidden=' . forbidden_id(), 403);
}

$dataRequestDao = null;
$privacyLogDao = null;
$dataRequestService = null;

if (class_exists('DataRequestDao')) {
    $dataRequestDao = new DataRequestDao();
}

if (class_exists('PrivacyLogDao')) {
    $privacyLogDao = new PrivacyLogDao();
}

if (class_exists('DataRequestService') && $dataRequestDao !== null && $privacyLogDao !== null && $app->sanitizer !== null) {
    $dataRequestService = new DataRequestService($dataRequestDao, $privacyLogDao, $app->sanitizer);
}

$errors = [];
$status = [];

try {
    if ($page === 'data-export' && $action === 'export') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_email']) && $dataRequestService !== null) {
            $email = filter_input(INPUT_POST, 'export_email', FILTER_VALIDATE_EMAIL);

            if (!$email) {
                if (empty($errors)) {
                    $errors = [];
                }
                $errors[] = "Please enter a valid email address.";
            } else {
                $options = [
                    'export_profile' => true,
                    'export_comments' => isset($_POST['export_comments']),
                    'export_posts' => isset($_POST['export_posts']),
                    'export_activity' => isset($_POST['export_activity'])
                ];

                try {
                    $exportData = $dataRequestService->exportUserData($email, $options);

                    header('Content-Type: application/json');
                    header('Content-Disposition: attachment; filename="user_data_' . time() . '.json"');
                    echo json_encode($exportData, JSON_PRETTY_PRINT);
                    exit;
                } catch (AppException $e) {
                    if (empty($errors)) {
                        $errors = [];
                    }
                    $errors[] = $e->getMessage();
                }
            }
        }
    }

    if ($page === 'data-deletion' && $action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_email']) && $dataRequestService !== null) {
            $email = filter_input(INPUT_POST, 'delete_email', FILTER_VALIDATE_EMAIL);

            if (!$email) {
                if (empty($errors)) {
                    $errors = [];
                }
                $errors[] = "Please enter a valid email address.";
            } else {
                try {
                    $dataRequestService->createRequest('deletion', $email, ['note' => 'User requested data deletion']);
                    if (empty($status)) {
                        $status = [];
                    }
                    $status[] = "Your data deletion request has been submitted. We will process it within 30 days.";
                } catch (AppException $e) {
                    if (empty($errors)) {
                        $errors = [];
                    }
                    $errors[] = $e->getMessage();
                }
            }
        }
    }

    switch ($page) {
        case 'data-export':
            $pageTitle = "Export Your Data";
            include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'data-export.php';
            break;

        case 'data-deletion':
            $pageTitle = "Delete Your Data";
            include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'data-deletion.php';
            break;

        case 'data-requests':
            if ($dataRequestService !== null) {
                if ($action === 'update') {
                    $requestId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
                    $newStatus = isset($_GET['status']) ? $_GET['status'] : 'processing';
                    if ($requestId > 0) {
                        try {
                            $dataRequestService->updateRequestStatus($requestId, $newStatus);
                            if (empty($status)) {
                                $status = [];
                            }
                            $status[] = "Request status updated.";
                        } catch (AppException $e) {
                            if (empty($errors)) {
                                $errors = [];
                            }
                            $errors[] = $e->getMessage();
                        }
                    }
                }

                $dataRequests = $dataRequestService->getAllRequests();
                $requestsTotal = $dataRequestService->getTotalRequests();
                $pendingCount = $dataRequestService->getPendingCount();
            } else {
                $dataRequests = [];
                $requestsTotal = 0;
                $pendingCount = 0;
            }
            $pageTitle = "Data Requests";
            include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'data-requests.php';
            break;

        case 'audit-logs':
            if ($privacyLogDao !== null) {
                $privacyLogs = $privacyLogDao->getAllLogs();
                $logsTotal = $privacyLogDao->totalLogRecords();
            } else {
                $privacyLogs = [];
                $logsTotal = 0;
            }
            $pageTitle = "Audit Logs";
            include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'audit-logs.php';
            break;

        default:
            if ($dataRequestService !== null) {
                $pendingCount = $dataRequestService->getPendingCount();
                $totalRequests = $dataRequestService->getTotalRequests();
            } else {
                $pendingCount = 0;
                $totalRequests = 0;
            }

            if ($privacyLogDao !== null) {
                $totalLogs = $privacyLogDao->totalLogRecords();
                $recentLogs = $privacyLogDao->getRecentLogs(5);
            } else {
                $totalLogs = 0;
                $recentLogs = [];
            }
            $pageTitle = "Privacy Settings";
            include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'index.php';
    }
} catch (\Throwable $th) {
    if (class_exists('LogError')) {
        LogError::setStatusCode(http_response_code());
        LogError::exceptionHandler($th);
    }
} catch (AppException $e) {
    LogError::setStatusCode(http_response_code());
    LogError::exceptionHandler($e);
}
