<?php 
/**
 * Class Media extends Dao
 * 
 * @package  SCRIPTLOG/LIB/DAO/Media
 * @category Dao Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class Media extends Dao
{

/**
 * 
 */
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
public function findAllMedia($orderBy = 'ID')
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
         ORDER BY :orderBy DESC";

  $this->setSQL($sql);
  
  $medias = $this->findAll([':orderBy' => $orderBy]);

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
 
  if(!empty($bind['filename'])) {

     $this->modify("tbl_media", [
        
         'media_filename' => $bind['media_filename'],
         'media_caption'  => $bind['media_caption'],
         'media_target'   => $bind['media_target'],
         'media_access'   => $bind['media_access'],
         'media_status'   => $bind['media_status']

     ], "ID = {$id_sanitized}");

  } else {
    
     $this->modify("tbl_media", [
        
        'media_caption' => $bind['cmedia_aption'],
        'media_target'  => $bind['media_target'],
        'media_access'  => $bind['media_access'],
        'media_status'  => $bind['media_status']

     ], "ID = {$id_sanitized}");

  }

  // query Id
  $this->setSQL("SELECT ID from tbl_media WHERE ID = ?");
  $media_id = $this->findColumn([$id_sanitized]);

  // update media meta
  if(!empty($bind['filename'])) {

     $this->modify("tbl_mediameta", [

         'meta_value' => $bind['meta_value']

     ], "ID = {$media_id['ID']}");
     
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
  $sql = "SELECT ID FROM tbl_media";
  $this->setSQL($sql);
  return $this->checkCountValue($data);  
}

}