<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class MediaProviderModel extends Dao
 * 
 * @category provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class MediaProviderModel extends Dao
{

const TIME_BEFORE_EXPIRED = 8;

public function __construct()
{
  parent::__construct();
}

/**
 * Find all media for downloaded
 *
 * @param int $orderBy
 * @return void
 * 
 */
public function findAllMediaDownload($orderBy = 'ID')
{
   
 $sql = "SELECT ID, media_filename, media_caption, media_type, media_taget, 
                media_user, media_access, media_status
         FROM tbl_media 
         WHERE media_target = 'download' 
         AND media_access = 'public' AND media_status = '1'
         ORDER BY :orderBy DESC";
 
  $this->setSQL($sql);

  $items = $this->findAll([':orderBy' => $orderBy]);

  return (empty($items)) ?:  $items;
   
}

/**
 * Find media for downloaded based on ID
 *
 * @param int $mediaId
 * @param object $sanitize
 * @return void
 * 
 */
public function findMediaDownload($mediaId, $sanitize)
{

 $id_sanitized = $this->filteringId($sanitize, $mediaId, 'sql');

 $sql = "SELECT ID, media_filename, media_caption, media_type, media_taget, 
                media_user, media_access, media_status
         FROM tbl_media 
         WHERE ID = :ID 
         AND media_target = 'download' 
         AND media_access = 'public' AND media_status = '1' ";

 $this->setSQL($sql);

 $item = $this->findRow([':ID' => $id_sanitized]);
 
 return (empty($item)) ?: $item;

}

/**
 * Find media download based on Id,time before expired and ip
 *
 * @param integer $mediaId
 * @param object $sanitize
 * @return array
 * 
 */
public function findMediaDownloadUrl($mediaId, $sanitize)
{

  $ip_address = get_ip_address();

  $id_sanitized = $this->filteringId($sanitize, $mediaId, 'sql');

  $sql = "SELECT ID, media_id, media_identifier, before_expired, ip_address, created_at
          FROM tbl_media_download 
          WHERE media_id = :media_id 
          AND ip_address = '".$ip_address."'
          AND before_expired >= '".time()."'";

  $this->setSQL($sql);

  $item = $this->findAll([':media_id'=>$id_sanitized]);

  return (empty($item)) ?: $item;

}

/**
 * Find media download by it's identifier
 *
 * @param string $media_identifier
 * @return array
 * 
 */
public function findMediaDownloadByIdentifier($media_identifier)
{
  $sql = "SELECT ID, media_id, media_identifier, before_expired, ip_address, created_at
          FROM tbl_media_download
          WHERE media_identifier = ?";

  $this->setSQL($sql);

  $item = $this->findAll([$media_identifier]);

  return (empty($item)) ?: $item;

}

/**
 * create media downloaded
 *
 * @param array $bind
 * 
 */
public function createMediaDownload($bind)
{

  $this->create("tbl_media_download", [

     'media_id' => $bind['media_id'],
     'media_identifier' => generate_media_identifier(),
     'before_expired' => (time()+self::TIME_BEFORE_EXPIRED*60*60),
     'ip_addres' => (get_ip_address())

  ]);

}

}