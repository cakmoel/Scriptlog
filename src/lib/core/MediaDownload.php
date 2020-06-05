<?php
/**
 * class MediaDownload
 * 
 * @category Core Class
 * @author  M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class MediaDownload
{

 private $mediaDao;

 public function __construct(MediaDao $mediaDao)
 {
   $this->mediaDao = $mediaDao;
 }

 protected function grabAllMediaDownload($orderBy = 'ID')
 {
   return $this->mediaDao->findAllMediaDownload($orderBy);
 }

 protected function grabMediaDownload($mediaId)
 {
   return $this->mediaDao->findMediaDownload($mediaId);
 }

 protected function grabMediaDownloadUrl($mediaId)
 {
   return $this->mediaDao->findMediaDownloadUrl($mediaId);
 }
 
 protected function grabMediaDownloadByIdentifier($media_identifier)
 {
   return $this->mediaDao->findMediaDownloadByIdentifier($media_identifier);
 }

 protected function checkMediaId()
 {
   if ((!isset($_GET['id'])) || (!check_integer($_GET['id'])) || (gettype($_GET['id']) !== "integer")) {
      
      exit('<div class="alert alert-danger">missing file id</div>');

   }

   $media = $this->grabMediaDownload($_GET['id']);

   if (!$media) {

      exit('<div class="alert alert-danger">file not found, may be it\'s deleted!</div>');

   }

   return $media;

 }

}