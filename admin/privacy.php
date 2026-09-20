<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

$action = isset($_GET['action']) ? htmlentities(strip_tags($_GET['action']), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : "";
$page = isset($_GET['p']) ? htmlentities(strip_tags($_GET['p']), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401) : "index";

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
    $commentDao = class_exists('CommentDao') ? new CommentDao() : null;
    $postDao = class_exists('PostDao') ? new PostDao() : null;
    $userService = class_exists('UserService') ? new UserService($app->userDao, $app->validator, $app->userToken, $app->sanitizer, $commentDao, $postDao) : null;
    $dataRequestService = new DataRequestService($dataRequestDao, $privacyLogDao, $app->sanitizer, null, $userService, $app->userDao, $commentDao, $postDao);
}

$errors = [];
$status = [];

try {
    if ($page === 'data-export' && $action === 'export') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_email']) && $dataRequestService !== null) {
            if (!isset($_POST['csrfToken']) || !csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                if (empty($errors)) {
                    $errors = [];
                }
                $errors[] = MESSAGE_UNPLEASANT_ATTEMPT;
            } else {
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

                        while (ob_get_level()) {
                            ob_end_clean();
                        }

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
    }

    if ($page === 'data-deletion' && $action === 'delete') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_email']) && $dataRequestService !== null) {
            if (!isset($_POST['csrfToken']) || !csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                if (empty($errors)) {
                    $errors = [];
                }
                $errors[] = MESSAGE_UNPLEASANT_ATTEMPT;
            } elseif (!dsar_request_allowed()) {
                if (empty($errors)) {
                    $errors = [];
                }
                $errors[] = "Too many data requests from this IP address. Please try again later.";
            } else {
                $email = filter_input(INPUT_POST, 'delete_email', FILTER_VALIDATE_EMAIL);

                if (!$email) {
                    if (empty($errors)) {
                        $errors = [];
                    }
                    $errors[] = "Please enter a valid email address.";
                } else {
                    $note = 'User requested data deletion';
                    if (isset($_POST['delete_reason']) && trim((string)$_POST['delete_reason']) !== '') {
                        $note = trim(strip_tags((string)$_POST['delete_reason']));
                    }

                    try {
                        $dataRequestService->createRequest('deletion', $email, ['note' => $note]);
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
    }

    switch ($page) {
        case 'data-export':
            $csrfToken = class_exists('CSRFGuard') ? csrf_generate_token('csrfToken') : "";
            $pageTitle = "Export Your Data";
            include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'data-export.php';
            break;

        case 'data-deletion':
            $csrfToken = class_exists('CSRFGuard') ? csrf_generate_token('csrfToken') : "";
            $pageTitle = "Delete Your Data";
            include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'data-deletion.php';
            break;

        case 'data-requests':
            if ($dataRequestService !== null) {
                $requestId = 0;
                $newStatus = '';
                $isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

                if ($isPost && isset($_POST['request_id'], $_POST['action'])) {
                    if (!isset($_POST['csrfToken']) || !csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                        if (empty($errors)) {
                            $errors = [];
                        }
                        $errors[] = MESSAGE_UNPLEASANT_ATTEMPT;
                    } else {
                        $requestId = intval($_POST['request_id']);
                        $postedAction = htmlentities(strip_tags($_POST['action']), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401);
                        if ($postedAction === 'process') {
                            $newStatus = 'processing';
                        } elseif ($postedAction === 'complete') {
                            $newStatus = 'completed';
                        } elseif ($postedAction === 'reject') {
                            $newStatus = 'rejected';
                        }
                    }
                } elseif ($action === 'update') {
                    $requestId = isset($_GET['Id']) ? intval($_GET['Id']) : 0;
                    $newStatus = isset($_GET['status']) ? $_GET['status'] : 'processing';
                }

                if ($requestId > 0 && $newStatus !== '') {
                    try {
                        $targetRequest = $dataRequestDao->getRequestById($requestId);
                        if (!$targetRequest) {
                            throw new AppException("Request not found");
                        }

                        $isErasure = in_array($targetRequest['request_type'], ['deletion', 'erasure'], true);

                        if ($newStatus === 'completed' && $isErasure) {
                            if (!$isPost || empty($_POST['confirm_erasure'])) {
                                throw new AppException("Please confirm the irreversible erasure before completing this request.");
                            }
                            $dataRequestService->deleteUserData($targetRequest['request_email']);
                        }

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

                $dataRequests = $dataRequestService->getAllRequests();
                $requestsTotal = $dataRequestService->getTotalRequests();
                $pendingCount = $dataRequestService->getPendingCount();
            } else {
                $dataRequests = [];
                $requestsTotal = 0;
                $pendingCount = 0;
            }
            $csrfToken = class_exists('CSRFGuard') ? csrf_generate_token('csrfToken') : "";
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

        case 'retention':
            $consentDao = class_exists('ConsentDao') ? new ConsentDao() : null;
            $consentService = ($consentDao !== null && class_exists('ConsentService')) ? new ConsentService($consentDao) : null;

            $configDao = class_exists('ConfigurationDao') ? new ConfigurationDao() : null;
            $configService = ($configDao !== null && class_exists('ConfigurationService') && $app->validator !== null && $app->sanitizer !== null) ? new ConfigurationService($configDao, $app->validator, $app->sanitizer) : null;

            $consentDays = 365;
            $logDays = 365;

            if ($configService !== null) {
                $consentSetting = $configService->grabSettingByName('consent_retention_days');
                if (!empty($consentSetting['setting_value'])) {
                    $consentDays = (int)$consentSetting['setting_value'];
                }
                $logSetting = $configService->grabSettingByName('privacy_log_retention_days');
                if (!empty($logSetting['setting_value'])) {
                    $logDays = (int)$logSetting['setting_value'];
                }
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!isset($_POST['csrfToken']) || !csrf_check_token('csrfToken', $_POST, 60 * 10)) {
                    if (empty($errors)) {
                        $errors = [];
                    }
                    $errors[] = MESSAGE_UNPLEASANT_ATTEMPT;
                } else {
                    $consentDays = max(1, intval($_POST['consent_retention_days'] ?? 365));
                    $logDays = max(1, intval($_POST['privacy_log_retention_days'] ?? 365));

                    if (isset($_POST['save_retention']) && $configService !== null) {
                        foreach (['consent_retention_days' => $consentDays, 'privacy_log_retention_days' => $logDays] as $settingName => $settingValue) {
                            $existing = $configService->grabSettingByName($settingName);
                            if (!empty($existing)) {
                                $configService->setConfigId($existing['ID']);
                                $configService->setConfigName($settingName);
                                $configService->setConfigValue((string)$settingValue);
                                $configService->modifySetting();
                            } else {
                                $configService->setConfigName($settingName);
                                $configService->setConfigValue((string)$settingValue);
                                $configService->addSetting();
                            }
                        }
                        if (empty($status)) {
                            $status = [];
                        }
                        $status[] = "Retention windows updated.";
                    }

                    if (isset($_POST['run_cleanup']) && empty($_POST['confirm_cleanup'])) {
                        if (empty($errors)) {
                            $errors = [];
                        }
                        $errors[] = "Please confirm the retention cleanup before running it.";
                    } elseif (isset($_POST['run_cleanup']) && $consentService !== null && $privacyLogDao !== null && $consentDao !== null) {
                        $consentsBefore = $consentDao->totalConsentRecords();
                        $logsBefore = $privacyLogDao->totalLogRecords();

                        $consentService->cleanOldConsents($consentDays);
                        $privacyLogDao->deleteOldLogs($logDays);

                        $consentsRemoved = $consentsBefore - $consentDao->totalConsentRecords();
                        $logsRemoved = $logsBefore - $privacyLogDao->totalLogRecords();

                        if (empty($status)) {
                            $status = [];
                        }
                        $status[] = "Cleanup complete: {$consentsRemoved} consent record(s) and {$logsRemoved} audit log(s) removed.";
                    }
                }
            }

            $retentionConsentDays = $consentDays;
            $retentionLogDays = $logDays;
            $csrfToken = class_exists('CSRFGuard') ? csrf_generate_token('csrfToken') : "";
            $pageTitle = "Data Retention";
            include dirname(__FILE__) . DS . 'ui' . DS . 'privacy' . DS . 'data-retention.php';
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
