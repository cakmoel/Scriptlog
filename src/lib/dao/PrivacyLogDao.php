<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * PrivacyLogDao Class
 *
 * Data Access Object for privacy audit logs
 *
 * @category  Dao Class
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PrivacyLogDao extends Dao
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create a privacy log entry
     *
     * @param string $action
     * @param string $type
     * @param int|null $userId
     * @param string|null $email
     * @param string|null $details
     * @param string $ipAddress
     * @return int
     */
    public function createLog($action, $type, $userId = null, $email = null, $details = null, $ipAddress = null)
    {
        $this->create("tbl_privacy_logs", [
            'log_action' => $action,
            'log_type' => $type,
            'log_user_id' => $userId,
            'log_email' => $email,
            'log_details' => $details,
            'log_ip' => $ipAddress
        ]);

        return $this->lastId();
    }

    /**
     * Get log by ID
     *
     * @param int $id
     * @return array|bool
     */
    public function getLogById($id)
    {
        $sql = "SELECT * FROM tbl_privacy_logs WHERE ID = ?";
        $this->setSQL($sql);
        return $this->findRow([(int)$id]);
    }

    /**
     * Get logs by user ID
     *
     * @param int $userId
     * @return array|bool
     */
    public function getLogsByUserId($userId)
    {
        $sql = "SELECT * FROM tbl_privacy_logs 
                WHERE log_user_id = ? 
                ORDER BY log_date DESC";

        $this->setSQL($sql);
        return $this->findAll([(int)$userId]);
    }

    /**
     * Get logs by email
     *
     * @param string $email
     * @return array|bool
     */
    public function getLogsByEmail($email)
    {
        $sql = "SELECT * FROM tbl_privacy_logs 
                WHERE log_email = ? 
                ORDER BY log_date DESC";

        $this->setSQL($sql);
        return $this->findAll([$email]);
    }

    /**
     * Get logs by action
     *
     * @param string $action
     * @return array|bool
     */
    public function getLogsByAction($action)
    {
        $sql = "SELECT * FROM tbl_privacy_logs 
                WHERE log_action = ? 
                ORDER BY log_date DESC";

        $this->setSQL($sql);
        return $this->findAll([$action]);
    }

    /**
     * Get all logs
     *
     * @param string $orderBy
     * @return array|bool
     */
    public function getAllLogs($orderBy = 'ID')
    {
        $sql = "SELECT * FROM tbl_privacy_logs ORDER BY '$orderBy' DESC";
        $this->setSQL($sql);
        return $this->findAll([]);
    }

    /**
     * Get recent logs
     *
     * @param int $limit
     * @return array|bool
     */
    public function getRecentLogs($limit = 50)
    {
        $sql = "SELECT * FROM tbl_privacy_logs 
                ORDER BY log_date DESC 
                LIMIT ?";

        $this->setSQL($sql);
        return $this->findAll([(int)$limit]);
    }

    /**
     * Delete old logs
     *
     * @param int $days
     * @return bool
     */
    public function deleteOldLogs($days = 365)
    {
        $sql = "DELETE FROM tbl_privacy_logs 
                WHERE log_date < DATE_SUB(NOW(), INTERVAL ? DAY)";

        $this->setSQL($sql);

        $stmt = $this->dbc->dbQuery($sql, [$days]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Total log records
     *
     * @return int
     */
    public function totalLogRecords()
    {
        $sql = "SELECT ID FROM tbl_privacy_logs";
        $this->setSQL($sql);
        return $this->checkCountValue([]);
    }
}
