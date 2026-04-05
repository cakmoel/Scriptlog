<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class DownloadModel extends Dao
 *
 * @category Model class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
class DownloadModel extends BaseModel
{
    /**
     * getAllMediaDownload
     *
     * @param string $orderBy
     * @return mixed
     *
     */
    public function getAllMediaDownload($orderBy = 'ID')
    {

        $sql = "SELECT md.ID, md.media_id, md.media_identifier, md.before_expired, md.created_at,
        m.media_filename, m.media_caption, m.media_type, m.media_target, 
        m.media_user, m.media_access, m.media_status
        FROM tbl_media_download md
        INNER JOIN tbl_media m ON md.media_id = m.ID
        ORDER BY md.ID DESC";

        $this->setSQL($sql);

        $items = $this->findAll([]);

        if (empty($items)) {
            return [];
        }

        return $items;
    }

    /**
     * getMediaDownload
     *
     * @param int|num $id
     * @param object $sanitize
     * @return mixed
     *
     */
    public function getMediaDownload($mediaId, $sanitize)
    {

        $idsanitized = $this->filteringId($sanitize, $mediaId, 'sql');

        $sql = "SELECT md.ID, md.media_id, md.media_identifier, md.before_expired, md.created_at,
          m.ID, m.media_filename, m.media_caption, m.media_type, m.media_target,
          m.media_user, m.media_access, m.media_status
          FROM tbl_media_download md
          INNER JOIN tbl_media m ON md.media_id = m.ID
          WHERE md.media_id = ? 
          AND m.media_access = 'public' AND m.media_status = '1'";

        $this->setSQL($sql);

        $item = $this->findRow([$idsanitized]);

        if (empty($item)) {
            return [];
        }

        return $item;
    }

    /**
     * getMediaDownloadURL
     *
     * @param int|num $id
     * @param object $sanitize
     * @return mixed
     *
     */
    public function getMediaDownloadURL($identifier, $sanitize)
    {
        $ip_address = get_ip_address();

        $identifierSanitized = $sanitize->sanitize($identifier, 'sql');

        $sql = "SELECT ID, media_id, media_identifier, before_expired, ip_address, created_at
          FROM tbl_media_download 
          WHERE media_identifier = ?";

        $this->setSQL($sql);

        $item = $this->findRow([$identifierSanitized]);

        if (empty($item)) {
            return [];
        }

        return $item;
    }

    /**
     * createMediaDownload
     *
     * @param array $bind
     *
     */
    public function createMediaDownload($bind)
    {

        $this->create("tbl_media_download", [
          'media_id' => $bind['media_id'],
          'media_identifier' => $bind['media_identifier'],
          'before_expired' => $bind['before_expired'],
          'ip_address' => $bind['ip_address'],
          'created_at' => date("Y-m-d H:i:s")
        ]);
    }

    /**
     * updateMediaDownload
     *
     * @param object $sanitize
     * @param array $bind
     * @param int|num $mediaId
     *
     */
    public function updateMediaDownload($sanitize, $bind, $mediaId)
    {

        $idsanitized = $this->filteringId($sanitize, $mediaId, 'sql');

        if (!empty($bind['media_id'])) {
            $this->modify(
                "tbl_media_download",
                [
                'media_identifier' => $bind['media_identifier'],
                'before_expired' => $bind['before_expired'],
                'ip_address' => $bind['ip_address']
                ],
                "media_id = {$idsanitized}"
            );
        } else {
            $this->modify(
                "tbl_media_download",
                [
                'media_identifier' => $bind['media_identifier'] ?? '',
                'before_expired' => $bind['before_expired'] ?? time(),
                'ip_address' => $bind['ip_address'] ?? ''
                ],
                "media_identifier = '{$mediaId}'"
            );
        }
    }

    /**
     * deleteMediaDownload
     *
     * @param string $identifier
     * @param object $sanitize
     * @return bool
     */
    public function deleteMediaDownload($identifier, $sanitize)
    {
        $idsanitized = $this->filteringId($sanitize, $identifier, 'sql');
        return $this->deleteRecord("tbl_media_download", ['media_identifier' => $identifier]);
    }

    /**
     * getDownloadCountByMedia
     *
     * @param int $mediaId
     * @return int
     */
    public function getDownloadCountByMedia($mediaId)
    {
        $sql = "SELECT COUNT(*) as count FROM tbl_download_log WHERE media_id = ?";
        $this->setSQL($sql);
        $result = $this->findRow([$mediaId]);
        return (int)($result['count'] ?? 0);
    }

    /**
     * getDownloadHistoryByMedia
     *
     * @param int $mediaId
     * @return array
     */
    public function getDownloadHistoryByMedia($mediaId)
    {
        $sql = "SELECT * FROM tbl_download_log WHERE media_id = ? ORDER BY downloaded_at DESC";
        $this->setSQL($sql);
        return $this->findAll([$mediaId]) ?? [];
    }

    /**
     * getDownloadHistory
     *
     * @param int $limit
     * @return array
     */
    public function getDownloadHistory($limit = 50)
    {
        $sql = "SELECT dl.*, m.media_filename, m.media_caption 
            FROM tbl_download_log dl
            INNER JOIN tbl_media m ON dl.media_id = m.ID
            ORDER BY dl.downloaded_at DESC LIMIT ?";
        $this->setSQL($sql);
        return $this->findAll([$limit]) ?? [];
    }

    /**
     * getFileTypeDistribution
     *
     * @return array
     */
    public function getFileTypeDistribution()
    {
        $sql = "SELECT m.media_type, COUNT(*) as count 
            FROM tbl_media m
            INNER JOIN tbl_download_log dl ON m.ID = dl.media_id
            GROUP BY m.media_type";
        $this->setSQL($sql);
        return $this->findAll() ?? [];
    }

    /**
     * getRecentDownloads
     *
     * @param int $limit
     * @return array
     */
    public function getRecentDownloads($limit = 10)
    {
        $sql = "SELECT dl.*, m.media_filename, m.media_caption 
            FROM tbl_download_log dl
            INNER JOIN tbl_media m ON dl.media_id = m.ID
            ORDER BY dl.downloaded_at DESC LIMIT ?";
        $this->setSQL($sql);
        return $this->findAll([$limit]) ?? [];
    }

    /**
     * createDownloadLog
     *
     * @param array $bind
     * @return bool
     */
    public function createDownloadLog($bind)
    {
        return $this->create("tbl_download_log", [
            'media_id' => $bind['media_id'],
            'media_identifier' => $bind['media_identifier'],
            'ip_address' => $bind['ip_address'],
            'user_agent' => $bind['user_agent'] ?? '',
            'status' => $bind['status'] ?? 'success'
        ]);
    }
}
