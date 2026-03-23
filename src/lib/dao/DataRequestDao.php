<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * DataRequestDao Class
 * 
 * Data Access Object for GDPR data requests (access, rectification, erasure)
 *
 * @category  Dao Class
 * @author    Scriptlog
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class DataRequestDao extends Dao
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create a new data request
     * 
     * @param string $requestType
     * @param string $email
     * @param string $ipAddress
     * @param string $note
     * @return int
     */
    public function createRequest($requestType, $email, $ipAddress, $note = null)
    {
        $this->create("tbl_data_requests", [
            'request_type' => $requestType,
            'request_email' => $email,
            'request_ip' => $ipAddress,
            'request_note' => $note,
            'request_status' => 'pending'
        ]);

        return $this->lastId();
    }

    /**
     * Update request status
     * 
     * @param int $id
     * @param string $status
     * @param string $note
     * @return bool
     */
    public function updateRequestStatus($id, $status, $note = null)
    {
        $data = ['request_status' => $status];
        
        if ($status === 'completed') {
            $data['request_completed_date'] = date('Y-m-d H:i:s');
        }
        
        if ($note !== null) {
            $data['request_note'] = $note;
        }

        $this->modify("tbl_data_requests", $data, ['ID' => (int)$id]);

        return true;
    }

    /**
     * Get request by ID
     * 
     * @param int $id
     * @return array|bool
     */
    public function getRequestById($id)
    {
        $sql = "SELECT * FROM tbl_data_requests WHERE ID = ?";
        $this->setSQL($sql);
        return $this->findRow([(int)$id]);
    }

    /**
     * Get request by email
     * 
     * @param string $email
     * @return array|bool
     */
    public function getRequestByEmail($email)
    {
        $sql = "SELECT * FROM tbl_data_requests 
                WHERE request_email = ? 
                ORDER BY request_date DESC";
        
        $this->setSQL($sql);
        return $this->findAll([$email]);
    }

    /**
     * Get all requests
     * 
     * @param string $orderBy
     * @return array|bool
     */
    public function getAllRequests($orderBy = 'ID')
    {
        $sql = "SELECT * FROM tbl_data_requests ORDER BY '$orderBy' DESC";
        $this->setSQL($sql);
        return $this->findAll([]);
    }

    /**
     * Get requests by status
     * 
     * @param string $status
     * @return array|bool
     */
    public function getRequestsByStatus($status)
    {
        $sql = "SELECT * FROM tbl_data_requests 
                WHERE request_status = ? 
                ORDER BY request_date DESC";
        
        $this->setSQL($sql);
        return $this->findAll([$status]);
    }

    /**
     * Delete request
     * 
     * @param int $id
     * @return bool
     */
    public function deleteRequest($id)
    {
        $this->deleteRecord("tbl_data_requests", ['ID' => (int)$id]);
        return true;
    }

    /**
     * Total request records
     * 
     * @return int
     */
    public function totalRequestRecords()
    {
        $sql = "SELECT ID FROM tbl_data_requests";
        $this->setSQL($sql);
        return $this->checkCountValue([]);
    }

    /**
     * Get pending requests count
     * 
     * @return int
     */
    public function getPendingCount()
    {
        $sql = "SELECT COUNT(ID) FROM tbl_data_requests WHERE request_status = 'pending'";
        $this->setSQL($sql);
        return (int) $this->findColumn([]);
    }
}
