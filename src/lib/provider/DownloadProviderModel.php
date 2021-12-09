<?php
/**
 * class DownloadProviderModel extends Dao
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class DownloadProviderModel extends Dao
{

public function __construct()
{
  parent::__construct();   
}

/**
 * getAllMediaDownload
 *
 * @param string $orderBy
 * @return mixed
 * 
 */
public function getAllMediaDownload($orderBy = 'ID')
{

$sql = "SELECT ID, media_filename, media_caption, media_type, media_taget, 
    media_user, media_access, media_status
    FROM tbl_media 
    WHERE media_target = 'download' 
    AND media_access = 'public' AND media_status = '1'
    ORDER BY :orderBy DESC";

$this->setSQL($sql);

$items = $this->findAll([':orderBy'=>$orderBy]);

return (empty($items)) ?: $items;

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

  $sql = "SELECT ID, media_filename, media_caption, media_type, media_target
                  media_user, media_access, media_status
          FROM tbl_media 
          WHERE ID = :ID 
          AND media_target = 'download' 
          AND media_access = 'public' AND media_status = '1' ";

  $this->setSQL($sql);

  $item = $this->findRow([':ID' => $idsanitized]);

  return (empty($item)) ?: $item;
  
}

/**
 * getMediaDownloadURL
 *
 * @param int|num $id
 * @param object $sanitize
 * @return mixed
 * 
 */
public function getMediaDownloadURL($mediaId, $sanitize)
{
  $ip_address = get_ip_address();
  
  $idsanitized = $this->filteringId($sanitize, $mediaId, 'sql');

  $sql = "SELECT ID, media_id, media_identifier, before_expired, ip_address, created_at
  FROM tbl_media_download 
  WHERE media_id = :media_id 
  AND ip_address = ':ip'
  AND before_expired >= '" . time() . "'";

  $this->setSQL($sql);

  $item = $this->findAll([':media_id' => $idsanitized, ':ip' => $ip_address]);

  return (empty($item)) ?: $item;
  
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

      $this->modify("tbl_media_download", [
        'media_identifier' => $bind['media_identifier'],
        'before_expired' => $bind['before_expired'],
        'ip_address' => $bind['ip_address']
      ], 
        "media_id = {$idsanitized}");

  } 

}

}