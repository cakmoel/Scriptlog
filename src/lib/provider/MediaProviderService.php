<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class MediaProviderService
 * 
 * @category Provider class
 * 
 */
class MediaProviderService 
{

private $mediaProviderModel;

private $sanitizer;

public function __construct(MediaProviderModel $mediaProviderModel, Sanitize $sanitizer)
{
  $this->mediaProviderModel = $mediaProviderModel;

  $this->sanitizer = $sanitizer;

}

public function grabAllMediaDownload($orderBy = 'ID')
{
  return $this->mediaProviderModel->findAllMediaDownload($orderBy);
}

public function grabMediaDownload($mediaId)
{
  return $this->mediaProviderModel->findMediaDownload($mediaId, $this->sanitizer);
}

public function grabMediaDownloadURL($mediaId)
{
  return $this->mediaProviderModel->findMediaDownloadUrl($mediaId, $this->sanitizer);
}

}