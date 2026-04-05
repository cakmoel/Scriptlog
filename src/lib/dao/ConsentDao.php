<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * ConsentDao Class
 *
 * Data Access Object for consent records
 *
 * @category  Dao Class
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ConsentDao extends Dao
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Record a new consent
     *
     * @param string $consentType
     * @param string $consentStatus
     * @param string $ipAddress
     * @param string $userAgent
     * @return int|bool
     */
    public function recordConsent($consentType, $consentStatus, $ipAddress, $userAgent = null)
    {
        $this->create("tbl_consents", [
            'consent_type' => $consentType,
            'consent_status' => $consentStatus,
            'consent_ip' => $ipAddress,
            'consent_user_agent' => $userAgent
        ]);

        return $this->lastId();
    }

    /**
     * Update existing consent record
     *
     * @param int $id
     * @param string $consentStatus
     * @return bool
     */
    public function updateConsent($id, $consentStatus)
    {
        $this->modify("tbl_consents", [
            'consent_status' => $consentStatus,
            'consent_updated' => date('Y-m-d H:i:s')
        ], ['ID' => (int)$id]);

        return true;
    }

    /**
     * Get latest consent by type
     *
     * @param string $consentType
     * @return array|bool
     */
    public function getLatestConsent($consentType)
    {
        $sql = "SELECT * FROM tbl_consents 
                WHERE consent_type = ? 
                ORDER BY consent_date DESC 
                LIMIT 1";

        $this->setSQL($sql);

        $consent = $this->findRow([$consentType]);

        return (empty($consent)) ? false : $consent;
    }

    /**
     * Get all consent records
     *
     * @param string $orderBy
     * @return array|bool
     */
    public function getAllConsents($orderBy = 'ID')
    {
        $sql = "SELECT * FROM tbl_consents 
                ORDER BY '$orderBy' DESC";

        $this->setSQL($sql);

        $consents = $this->findAll([]);

        return (empty($consents)) ? false : $consents;
    }

    /**
     * Get consent history by IP
     *
     * @param string $ipAddress
     * @return array|bool
     */
    public function getConsentsByIp($ipAddress)
    {
        $sql = "SELECT * FROM tbl_consents 
                WHERE consent_ip = ? 
                ORDER BY consent_date DESC";

        $this->setSQL($sql);

        $consents = $this->findAll([$ipAddress]);

        return (empty($consents)) ? false : $consents;
    }

    /**
     * Check if user has given consent
     *
     * @param string $consentType
     * @return bool
     */
    public function hasConsented($consentType)
    {
        $sql = "SELECT COUNT(ID) FROM tbl_consents 
                WHERE consent_type = ? 
                AND consent_status = 'accepted' 
                ORDER BY consent_date DESC 
                LIMIT 1";

        $this->setSQL($sql);

        $count = $this->findColumn([$consentType]);

        return $count > 0;
    }

    /**
     * Delete consent records older than specified days
     *
     * @param int $days
     * @return bool
     */
    public function deleteOldConsents($days = 365)
    {
        $sql = "DELETE FROM tbl_consents 
                WHERE consent_date < DATE_SUB(NOW(), INTERVAL ? DAY)";

        $this->setSQL($sql);

        $stmt = $this->dbc->dbQuery($sql, [$days]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Total consent records
     *
     * @return int
     */
    public function totalConsentRecords()
    {
        $sql = "SELECT ID FROM tbl_consents";
        $this->setSQL($sql);
        return $this->checkCountValue([]);
    }
}
