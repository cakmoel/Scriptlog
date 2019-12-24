<?php 
/**
 * Class Media extends Dao
 * 
 * 
 * @category Dao Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class MediaDao extends Dao
{


public function __construct()
{
    parent::__construct();
}

/**
 * Find All Media
 * 
 * @method public findAllMedia()
 * @param integer $ID
 * @return array
 * 
 */
public function findAllMedia($orderBy = 'ID', $user_level = null)
{

  if (!is_null($user_level)) {

    $sql = "SELECT m.ID, 
                 m.media_filename, 
                 m.media_caption, 
                 m.media_type, 
                 m.media_target,
                 m.media_user, 
                 m.media_access,
                 m.media_status,
                 u.user_level
         FROM tbl_media AS m
         INNER JOIN tbl_users AS u ON m.media_user = u.user_level
         WHERE m.media_user = :user_level
         ORDER BY :orderBy DESC";

    $this->setSQL($sql);
  
    $medias = $this->findAll([':orderBy' => $orderBy, ':user_level'=> $user_level]);

  } else {

     $sql = "SELECT ID, 
                    media_filename, 
                    media_caption, 
                    media_type, 
                    media_target,
                    media_user, 
                    media_access,
                    media_status
            FROM tbl_media
            ORDER BY :orderBy DESC";

    $this->setSQL($sql);
    
    $medias = $this->findAll([':orderBy' => $orderBy]);
    
  }
  
  if(empty($medias)) return false;

  return $medias;
  
}

/**
 * Find media by Id
 * 
 * @method public findMediaById()
 * @param integer $mediaId
 * @param object $sanitize
 * 
 */
public function findMediaById($mediaId, $sanitize)
{
  $idsanitized = $this->filteringId($sanitize, $mediaId, 'sql');

  $sql = "SELECT ID, 
            media_filename, 
            media_caption,
            media_type,
            media_target,
            media_user, 
            media_access,
            media_status
          FROM tbl_media
          WHERE ID = ?";

  $this->setSQL($sql);

  $mediaDetails = $this->findRow([$idsanitized]);

  if(empty($mediaDetails)) return false;

  return $mediaDetails;

}

/**
 * Find media by media format type
 * 
 * @method public findMediaByType()
 * @param string $type
 * @return array
 * 
 */
public function findMediaByType($type)
{
  $sql = "SELECT ID,
                 media_filename,
                 media_caption,
                 media_type, 
                 media_target,
                 media_user,
                 media_access,
                 media_status
          FROM tbl_media
          WHERE media_type = :media_type 
          AND media_status = '1'";

  $this->setSQL($sql);
  
  $mediaDetails = $this->findRow([':media_type' => $type]);

  if(empty($mediaDetails)) return false;

  return $mediaDetails;
  
}

/**
 * find mediameta by media's Id
 * 
 * @method public findMediaMeta()
 * @param int $mediaId
 * @param object $sanitize
 * 
 */
public function findMediaMetaValue($mediaId, $media_filename, $sanitize)
{
 
 $idsanitized = $this->filteringId($sanitize, $mediaId, 'sql');

 $sql = "SELECT ID, media_id, meta_key, meta_value FROM tbl_mediameta 
         WHERE media_id = ? AND meta_key = ?";

 $this->setSQL($sql);

 $mediameta_details = $this->findRow([$idsanitized, $media_filename]);

 if(empty($mediameta_details)) return false;

 return $mediameta_details;

}

/**
 * Add new media
 * 
 * @method public addMedia()
 * @param string|array $bind
 * 
 */
public function createMedia($bind)
{
  
  $this->create("tbl_media", [

      'media_filename' => $bind['media_filename'],
      'media_caption'  => $bind['media_caption'],
      'media_type'     => $bind['media_type'],
      'media_target'   => $bind['media_target'],
      'media_user'     => $bind['media_user'],
      'media_access'   => $bind['media_access'],
      'media_status'   => $bind['media_status']

  ]);

  return $this->lastId();

}

/**
 * Add new media meta
 * 
 * @param integer $mediaId
 * @param string|array $bind
 * 
 */
public function createMediaMeta($bind)
{

  $this->create("tbl_mediameta", [

     'media_id' => $bind['media_id'],
     'meta_key' => $bind['meta_key'],
     'meta_value' => $bind['meta_value']

  ]);

}

/**
 * Update Media
 * 
 * @method public updateMedia()
 * @param object $sanitize
 * @param array $bind
 * @param integer $ID
 * 
 */
public function updateMedia($sanitize, $bind, $ID)
{
  
  $id_sanitized = $this->filteringId($sanitize, $ID, 'sql');
 
  if(!empty($bind['media_filename'])) {

     $this->modify("tbl_media", [
        
         'media_filename' => $bind['media_filename'],
         'media_caption'  => $bind['media_caption'],
         'media_target'   => $bind['media_target'],
         'media_access'   => $bind['media_access'],
         'media_status'   => $bind['media_status']

     ], "ID = {$id_sanitized}");

  } else {
    
     $this->modify("tbl_media", [
        
        'media_caption' => $bind['media_caption'],
        'media_target'  => $bind['media_target'],
        'media_access'  => $bind['media_access'],
        'media_status'  => $bind['media_status']

     ], "ID = {$id_sanitized}");

  }

}

/**
 * Update media meta
 *
 * @param object $sanitize
 * @param array $bind
 * @param integer $ID
 * @return void
 * 
 */
public function updateMediaMeta($sanitize, $bind, $ID)
{
  $idsanitized = $this->filteringId($sanitize, $ID, 'sql');

  if (!empty($bind['meta_key'])) {

      $this->modify("tbl_mediameta", [
        'meta_key' => $bind['meta_key'],
        'meta_value' => $bind['meta_value']
      ], "media_id = {$idsanitized}");

  }

}

/**
 * Delete Media
 * 
 * @method public deleteMedia()
 * @param integer $ID
 * @param object $sanitize
 * 
 */
public function deleteMedia($ID, $sanitize)
{
  
  $id_sanitized = $this->filteringId($sanitize, $ID, 'sql');
  
  if($this->deleteRecord("tbl_media", "ID = {$id_sanitized}")) {

     $this->deleteRecord("tbl_mediameta", "media_id = {$id_sanitized}");

  }

}

/**
 * Check media's Id
 * 
 * @method public checkMediaId()
 * @param integer|numeric $id
 * @param object $sanitize
 * @return numeric
 * 
 */
public function checkMediaId($id, $sanitize)
{
 
   $sql = "SELECT ID from tbl_media WHERE ID = ?";
   $id_sanitized = $this->filteringId($sanitize, $id, 'sql');
   $this->setSQL($sql);
   $stmt = $this->checkCountValue([$id_sanitized]);
   return($stmt > 0);

}

/**
 * drop down media access
 * set media access
 * 
 * @param string $selected
 * @return string
 * 
 */
public function dropDownMediaAccess($selected = "")
{
  $name = 'media_access';

  $media_access = array('public' => 'Public', 'private' => 'Private');

  if($selected != '') {
    
    $selected = $selected;

  }

  return dropdown($name, $media_access, $selected);

}

/**
 * drop down media target
 * set media target
 * 
 * @param string $selected
 * @return string
 * 
 */
public function dropDownMediaTarget($selected = "")
{
 $name = 'media_target';

 $media_target = array('blog' => 'Blog', 'download' => 'Download', 'gallery' => 'Gallery', 'page' => 'Page');

 if($selected != '') {

    $selected = $selected;

 }

 return dropdown($name, $media_target, $selected);

}

/**
 * Drop down media status
 * 
 * @param int $selected
 * @return int
 * 
 */
public function dropDownMediaStatus($selected = "")
{
  $name = 'media_status';

  $media_status = array('Enabled', 'Disabled');

  if ($selected) {

     $selected = $selected;

  }

  return dropdown($name, $media_status, $selected);

}

/**
 * Total media records
 * 
 * @method public totalMediaRecords()
 * @param array $data = null
 * @return integer|numeric
 * 
 */
public function totalMediaRecords($data = null)
{

  if (!empty($data)) {

    $sql = "SELECT ID FROM tbl_media WHERE media_user = ? ";

  } else {

    $sql = "SELECT ID FROM tbl_media";
     
  }

   $this->setSQL($sql);
   return $this->checkCountValue($data);  
   
}

}