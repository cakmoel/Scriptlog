<?php

namespace Scriptlog\Service;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * DataRequestService Class
 *
 * Service layer for GDPR data requests (access, deletion)
 *
 * @category  Service Class
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Core\AppException;
use Scriptlog\Core\FormValidator;
use Scriptlog\Core\Sanitize;
use Scriptlog\Dao\CommentDao;
use Scriptlog\Dao\DataRequestDao;
use Scriptlog\Dao\PostDao;
use Scriptlog\Dao\PrivacyLogDao;
use Scriptlog\Dao\UserDao;
use Scriptlog\Dao\UserTokenDao;

class DataRequestService
{
    /**
     * DataRequestDao
     * @var DataRequestDao
     */
    private $dataRequestDao;

    /**
     * PrivacyLogDao
     * @var PrivacyLogDao
     */
    private $privacyLogDao;

    /**
     * Sanitizer
     * @var Sanitize
     */
    private $sanitizer;

    /**
     * NotificationService
     * @var NotificationService
     */
    private $notificationService;

    /**
     * UserService used to anonymize accounts on erasure.
     * @var UserService|null
     */
    private $userService;

    /**
     * UserDao
     * @var UserDao|null
     */
    private $userDao;

    /**
     * CommentDao
     * @var CommentDao|null
     */
    private $commentDao;

    /**
     * PostDao
     * @var PostDao|null
     */
    private $postDao;

    /**
     * Constructor
     *
     * @param DataRequestDao $dataRequestDao
     * @param PrivacyLogDao $privacyLogDao
     * @param Sanitize $sanitizer
     * @param ConfigurationService|null $configService
     * @param UserService|null $userService
     * @param UserDao|null $userDao
     * @param CommentDao|null $commentDao
     * @param PostDao|null $postDao
     */
    public function __construct(DataRequestDao $dataRequestDao, PrivacyLogDao $privacyLogDao, Sanitize $sanitizer, ?ConfigurationService $configService = null, ?UserService $userService = null, ?UserDao $userDao = null, ?CommentDao $commentDao = null, ?PostDao $postDao = null)
    {
        $this->dataRequestDao = $dataRequestDao;
        $this->privacyLogDao = $privacyLogDao;
        $this->sanitizer = $sanitizer;
        $this->notificationService = class_exists('NotificationService') ? new NotificationService($configService) : null;
        $this->userService = $userService;
        $this->userDao = $userDao;
        $this->commentDao = $commentDao;
        $this->postDao = $postDao;
    }

    /**
     * Create a new data request
     *
     * @param string $requestType
     * @param string $email
     * @param array $options
     * @return int|bool
     */
    public function createRequest($requestType, $email, $options = [])
    {
        if (!$this->validateEmail($email)) {
            throw new AppException("Invalid email address");
        }

        $ipAddress = function_exists('get_ip_address') ? get_ip_address() : '';
        $note = isset($options['note']) ? $options['note'] : null;

        $requestId = $this->dataRequestDao->createRequest(
            $requestType,
            $email,
            $ipAddress,
            $note
        );

        $this->privacyLogDao->createLog(
            'data_request_created',
            $requestType,
            null,
            $email,
            "Data {$requestType} request created",
            $ipAddress
        );

        if ($this->notificationService) {
            $this->notificationService->sendDataRequestConfirmation($email, $requestType, $requestId);
            $adminEmail = function_exists('app_info') ? (app_info()['site_email'] ?? '') : '';
            if ($adminEmail) {
                $this->notificationService->sendAdminNotification($adminEmail, $email, $requestType, $requestId);
            }
        }

        return $requestId;
    }

    /**
     * Get all requests
     *
     * @return array
     */
    public function getAllRequests()
    {
        return $this->dataRequestDao->getAllRequests();
    }

    /**
     * Get pending requests count
     *
     * @return int
     */
    public function getPendingCount()
    {
        return $this->dataRequestDao->getPendingCount();
    }

    /**
     * Get total requests count
     *
     * @return int
     */
    public function getTotalRequests()
    {
        return $this->dataRequestDao->totalRequestRecords();
    }

    /**
     * Update request status
     *
     * @param int $requestId
     * @param string $status
     * @param string|null $note
     * @return bool
     */
    public function updateRequestStatus($requestId, $status, $note = null)
    {
        $request = $this->dataRequestDao->getRequestById($requestId);

        if (!$request) {
            throw new AppException("Request not found");
        }

        $this->dataRequestDao->updateRequestStatus($requestId, $status, $note);

        $ipAddress = function_exists('get_ip_address') ? get_ip_address() : '';

        $this->privacyLogDao->createLog(
            'request_status_updated',
            $request['request_type'],
            null,
            $request['request_email'],
            "Request status changed to: {$status}",
            $ipAddress
        );

        if ($status === 'completed' && $this->notificationService) {
            $this->notificationService->sendRequestCompleted($request['request_email'], $request['request_type']);
        }

        return true;
    }

    /**
     * Export user data
     *
     * @param string $email
     * @param array $options
     * @return array
     */
    public function exportUserData($email, $options = [])
    {
        if (!$this->validateEmail($email)) {
            throw new AppException("Invalid email address");
        }

        $exportData = [
            'requested_at' => date('Y-m-d H:i:s'),
            'email' => $email,
            'profile' => [],
            'comments' => [],
            'posts' => [],
            'activity' => []
        ];

        $user = $this->getUserDao()->getUserByEmail($email);

        if ($user) {
            $exportData['profile'] = [
                'user_login' => $user['user_login'],
                'user_email' => $user['user_email'],
                'user_fullname' => $user['user_fullname'] ?? '',
                'user_url' => $user['user_url'] ?? '',
                'user_registered' => $user['user_registered']
            ];

            $exportData['user_id'] = $user['ID'];

            if (isset($options['export_comments']) && $options['export_comments']) {
                $exportData['comments'] = $this->getCommentDao()->findComments('ID', $email);
            }

            if (isset($options['export_posts']) && $options['export_posts']) {
                $posts = $this->getPostDao()->findPosts('ID', $user['ID'], false);
                $exportData['posts'] = $posts ?: [];
            }

            if (isset($options['export_activity']) && $options['export_activity']) {
                $exportData['activity'] = $this->privacyLogDao->getLogsByEmail($email);
            }
        }

        $ipAddress = function_exists('get_ip_address') ? get_ip_address() : '';

        $this->privacyLogDao->createLog(
            'data_exported',
            'export',
            $user['ID'] ?? null,
            $email,
            "User data exported",
            $ipAddress
        );

        return $exportData;
    }

    /**
     * Delete user data (anonymize)
     *
     * Delegates to UserService::removeUserWithAnonymization() so the erasure
     * machinery (comment anonymization, post reassignment, account removal)
     * is actually executed instead of only logging.
     *
     * @param string $email
     * @return bool
     */
    public function deleteUserData($email)
    {
        if (!$this->validateEmail($email)) {
            throw new AppException("Invalid email address");
        }

        $user = $this->getUserDao()->getUserByEmail($email);

        if (!$user) {
            throw new AppException("User not found");
        }

        $userId = $user['ID'];
        $ipAddress = function_exists('get_ip_address') ? get_ip_address() : '';

        $deleted = $this->getUserService()->removeUserWithAnonymization($userId, $email);

        if (!$deleted) {
            throw new AppException("Unable to delete user data");
        }

        $this->privacyLogDao->createLog(
            'data_deleted',
            'deletion',
            $userId,
            $email,
            "User data anonymized",
            $ipAddress
        );

        return true;
    }

    /**
     * Lazy-access the injected UserService, falling back to a fresh instance
     * when none was provided (backward compatibility with existing callers).
     *
     * @return UserService
     */
    private function getUserService()
    {
        if ($this->userService === null) {
            $this->userService = new UserService($this->getUserDao(), new FormValidator(), new UserTokenDao(), $this->sanitizer, $this->getCommentDao(), $this->getPostDao());
        }

        return $this->userService;
    }

    /**
     * Lazy-access the injected UserDao, falling back to a fresh instance
     * when none was provided (backward compatibility with existing callers).
     *
     * @return UserDao
     */
    private function getUserDao()
    {
        if ($this->userDao === null) {
            $this->userDao = new UserDao();
        }

        return $this->userDao;
    }

    /**
     * Lazy-access the injected CommentDao, falling back to a fresh instance
     * when none was provided (backward compatibility with existing callers).
     *
     * @return CommentDao
     */
    private function getCommentDao()
    {
        if ($this->commentDao === null) {
            $this->commentDao = new CommentDao();
        }

        return $this->commentDao;
    }

    /**
     * Lazy-access the injected PostDao, falling back to a fresh instance
     * when none was provided (backward compatibility with existing callers).
     *
     * @return PostDao
     */
    private function getPostDao()
    {
        if ($this->postDao === null) {
            $this->postDao = new PostDao();
        }

        return $this->postDao;
    }

    /**
     * Validate email address
     *
     * @param string $email
     * @return bool
     */
    private function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
