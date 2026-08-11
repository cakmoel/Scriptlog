<?php

namespace Scriptlog\Controller\Api;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * GDPR API Controller
 *
 * Handles GDPR-related API endpoints including consent management
 *
 * @category  Controller Class
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

use Scriptlog\Controller\ApiController;
use Scriptlog\Core\ApiHateoas;
use Scriptlog\Core\ApiResponse;
use Scriptlog\Dao\ConsentDao;
use Scriptlog\Service\ConsentService;

class GdprApiController extends ApiController
{
    /**
     * ConsentDao instance
     * @var object
     */
    private $consentDao;

    /**
     * ConsentService instance
     * @var object
     */
    private $consentService;

    /**
     * @var ApiHateoas
     */
    private $hateoas;

    /**
     * Constructor
     */
    public function __construct()
    {
        // GDPR endpoints don't require authentication
        $this->requiresAuth = false;

        parent::__construct();

        $this->hateoas = new ApiHateoas();

        // Initialize consent DAO and service
        if (class_exists('ConsentDao')) {
            $this->consentDao = new ConsentDao();
            $this->consentService = new ConsentService($this->consentDao);
        }
    }

    /**
     * Process cookie consent
     *
     * POST /api/v1/gdpr/consent
     *
     * @param array $params
     */
    public function consent($_params = [])
    {
        if ($this->method !== 'POST') {
            ApiResponse::error('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
            return;
        }

        // Validate request data
        if (empty($this->requestData)) {
            // Try to get from raw input
            $input = file_get_contents('php://input');
            $this->requestData = json_decode($input, true);
        }

        if (!isset($this->requestData['status']) || !in_array($this->requestData['status'], ['accepted', 'rejected'])) {
            ApiResponse::error('Invalid consent status. Must be "accepted" or "rejected"', 400, 'INVALID_STATUS');
            return;
        }

        $status = $this->requestData['status'];
        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        try {
            if ($this->consentService) {
                $this->consentService->recordConsent('cookie', $status, $ipAddress, $userAgent);
            }

            $links = $this->hateoas->rootLinks();

            ApiResponse::success([
                'status' => $status,
                'message' => 'Consent recorded successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ], 200, 'Consent recorded', $links);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to record consent: ' . $e->getMessage(), 500, 'CONSENT_FAILED');
        }
    }

    /**
     * Get consent status
     *
     * GET /api/v1/gdpr/consent
     *
     * @param array $params
     */
    public function getConsentStatus($_params = [])
    {
        if ($this->method !== 'GET') {
            ApiResponse::error('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
            return;
        }

        try {
            $consentType = isset($this->queryParams['type']) ? $this->queryParams['type'] : 'cookie';
            $hasConsented = false;

            if ($this->consentService) {
                $hasConsented = $this->consentService->hasConsented($consentType, $this->getClientIp());
            }

            $links = $this->hateoas->rootLinks();

            ApiResponse::success([
                'consent_given' => $hasConsented,
                'consent_type' => $consentType
            ], 200, null, $links);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to get consent status: ' . $e->getMessage(), 500, 'CONSENT_STATUS_FAILED');
        }
    }

    /**
     * Get client IP address
     *
     * Delegates to the shared get_ip_address() helper so proxy headers are
     * only trusted when the request actually came through Cloudflare.
     *
     * @return string
     */
    private function getClientIp()
    {
        if (function_exists('get_ip_address')) {
            return get_ip_address();
        }

        return '0.0.0.0';
    }
}
