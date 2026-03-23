<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
     * Constructor
     */
    public function __construct()
    {
        // GDPR endpoints don't require authentication
        $this->requiresAuth = false;

        parent::__construct();

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
    public function consent($params = [])
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

            ApiResponse::success([
                'status' => $status,
                'message' => 'Consent recorded successfully',
                'timestamp' => date('Y-m-d H:i:s')
            ], 'Consent recorded');
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
    public function getConsentStatus($params = [])
    {
        if ($this->method !== 'GET') {
            ApiResponse::error('Method not allowed', 405, 'METHOD_NOT_ALLOWED');
            return;
        }

        try {
            $consentType = isset($this->queryParams['type']) ? $this->queryParams['type'] : 'cookie';
            $hasConsented = false;

            if ($this->consentService) {
                $hasConsented = $this->consentService->hasConsented($consentType);
            }

            ApiResponse::success([
                'consent_given' => $hasConsented,
                'consent_type' => $consentType
            ]);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to get consent status: ' . $e->getMessage(), 500, 'CONSENT_STATUS_FAILED');
        }
    }

    /**
     * Get client IP address
     *
     * @return string
     */
    private function getClientIp()
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED',
                   'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
