<?php
/**
 * Class MediaProviderModel 
 * 
 * @category Provider Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class GalleryProviderModel extends Dao
{

public function __construct()
{
  parent::__construct();
}

public function getGalleries($start, $limit)
{

 $sql = "SELECT ID, media_filename, media_caption FROM tbl_media WHERE media_target = 'gallery'
 ORDER BY ID LIMIT :start, :limit ";

 $this->setSQL($sql);

 $galleries = $this->findAll([':start' => $start, ':limit' => $limit]);

 return (empty($galleries)) ?: $galleries;

}

}