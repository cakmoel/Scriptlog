<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class ConsentService
 *
 * Business logic for consent management
 *
 * @category  Service class
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ConsentService
{

    /**
     * Consent type
     * @var string
     */
    private $consentType;

    /**
     * Consent status
     * @var string
     */
    private $consentStatus;

    /**
     * IP Address
     * @var string
     */
    private $ipAddress;

    /**
     * User Agent
     * @var string
     */
    private $userAgent;

    /**
     * ConsentDao instance
     * @var object
     */
    private $consentDao;

    /**
     * Constructor
     * 
     * @param ConsentDao $consentDao
     */
    public function __construct(ConsentDao $consentDao)
    {
        $this->consentDao = $consentDao;
    }

    /**
     * Record user consent
     * 
     * @param string $consentType
     * @param string $consentStatus
     * @param string $ipAddress
     * @param string $userAgent
     * @return int|bool
     */
    public function recordConsent($consentType, $consentStatus, $ipAddress, $userAgent = null)
    {
        $this->consentType = $consentType;
        $this->consentStatus = $consentStatus;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;

        return $this->consentDao->recordConsent(
            $this->consentType,
            $this->consentStatus,
            $this->ipAddress,
            $this->userAgent
        );
    }

    /**
     * Update user consent
     * 
     * @param int $consentId
     * @param string $consentStatus
     * @return bool
     */
    public function updateConsent($consentId, $consentStatus)
    {
        return $this->consentDao->updateConsent($consentId, $consentStatus);
    }

    /**
     * Get latest consent by type
     * 
     * @param string $consentType
     * @return array|bool
     */
    public function getLatestConsent($consentType)
    {
        return $this->consentDao->getLatestConsent($consentType);
    }

    /**
     * Get all consent records
     * 
     * @return array|bool
     */
    public function getAllConsents()
    {
        return $this->consentDao->getAllConsents();
    }

    /**
     * Check if user has given consent
     * 
     * @param string $consentType
     * @return bool
     */
    public function hasConsented($consentType)
    {
        return $this->consentDao->hasConsented($consentType);
    }

    /**
     * Process cookie consent from user
     * 
     * @param string $status 'accepted' or 'rejected'
     * @return bool
     */
    public function processCookieConsent($status)
    {
        $ipAddress = function_exists('get_ip_address') ? get_ip_address() : $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $this->recordConsent('cookie', $status, $ipAddress, $userAgent);

        return true;
    }

    /**
     * Get cookie consent status
     * 
     * @return bool
     */
    public function getCookieConsentStatus()
    {
        $consent = $this->getLatestConsent('cookie');
        
        if ($consent && $consent['consent_status'] === 'accepted') {
            return true;
        }
        
        return false;
    }

    /**
     * Clean old consent records
     * 
     * @param int $days
     * @return bool
     */
    public function cleanOldConsents($days = 365)
    {
        return $this->consentDao->deleteOldConsents($days);
    }
}
