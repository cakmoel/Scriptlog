<?php
/**
 * Class ScriptlogImageThumbnail
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class ScriptlogImageThumbnail
{
/**
 * Original file
 * 
 * @var  string
 * 
 */
  protected $original;

/**
 * original width
 * 
 * @var numeric
 * 
 */
  protected $originalWidth;

/**
 * scriptlog original height
 * 
 */
  protected $originalHeight;

  protected $thumbWidth;

  protected $thumbHeight;

  protected $maxSize = 320;

  protected $canProcess = false;

  protected $imageType;

  protected $destination;

  protected $name; 

  protected $suffix = '_thumb';

  protected $permitted = [
      'image/jpeg',
      'image/png',
      'image/gif'
  ];
  
  protected $messages = [];

  private $mimeType;

  public function __construct($image)
  {
    if(is_file($image) && is_readable($image)) {

        $details = getimagesize($image);

    } else {

        $details = null;
        $this->messages[] = "Cannot open $image";
    }

    if(is_array($details)) {

        $this->checkType($details['mime']);

    } else {

        $this->messages[] = "$image does not appear to be an image";

    }

  }

  public function setDestination($destination)
  {
    if(is_dir($destination) && is_writable($destination)) {

        $last = substr($destination, -1);

        if($last == '/' || $last == '\\') {

            $this->destination = $destination;

        } else {

            $this->destination = $destination . DIRECTORY_SEPARATOR;
        }

    } else {

        $this->messages[] = "Cannot write to $destination";

    }

  }

  public function setMaxSize($size)
  {
    if(is_numeric($size) && $size > 0) {

        $this->maxSize = abs($size);

    } else {

        $this->messages[] = "The value of parameter of method setMaxSize() must be a positive number";
        
        $this->canProcess = false;

    }

  }

  public function setSuffix($suffix)
  {
    if(preg_match('/^\w+$/', $suffix)) {

        if(strpos($suffix, '_') !== 0) {

            $this->suffix = '_' . $suffix;

        } else {

            $this->suffix = $suffix;

        }

    }

  }

  public function generateImageThumb()
  {
      
   if($this->canProcess && $this->originalWidth != 0) {
     
     $this->measureSize($this->originalWidth, $this->originalHeight);

     $this->getName();

     $this->createImageThumb();

   } elseif($this->originalWidth == 0) {

     $this->messages[] = "cannot determine size of ".$this->original;

   }

  }

  public function getAlert()
  {
    return $this->messages;
  }

  protected function checkType($mime)
  {
      $mimetypes = array (
				'image/jpeg',
				'image/png',
				'image/gif'
		);
        
      if (in_array ( $mime, $mimetypes )) {
			$this->canProcess = true;
			// ekstrak karakter setelah 'image/
			$this->imageType = substr ( $mime, 6 );
      }
        
  }

  protected function measureSize($width, $height)
  {
    if(($width <= $this->maxSize) && ($height <= $this->maxSize)) {

        $ratio = 1;

    } elseif($width > $height) {

        $ratio = $this->maxSize / $width;

    } else {

        $ratio = $this->maxSize / $height;

    }

    $this->thumbWidth  = round($width * $ratio);
    $this->thumbHeight = round($height * $ratio);

  }

  protected function getName()
  {
    $extension = array(
        '/\.jpg$/i',
		'/\.jpeg$/i',
		'/\.png$/i',
		'/\.gif$/i'
    );

    $this->name = preg_replace($extension, '', basename($this->original));

  }

  protected function createImageResource()
  {
    if($this->imageType == 'jpeg') {

        return imagecreatefromjpeg($this->original);

    } elseif($this->imageType == 'png') {

        return imagecreatefrompng($this->original);

    } elseif($this->imageType == 'gif') {

        return imagecreatefromgif($this->original);

    }

  }

  protected function createImageThumb()
  {
    $resource = $this->createImageResource();

    $thumb = imagecreatetruecolor($this->thumbWidth, $this->thumbHeight);

    imagecopyresampled($thumb, $resource, 0, 0, 0, 0, $this->thumbWidth, $this->thumbHeight, $this->originalWidth, $this->originalHeight);
    
    $newname = $this->name . $this->suffix;

    if($this->imageType == 'jpeg') {

        $newname .= '.jpeg';

        $success = imagejpeg($thumb, $this->destination . $newname, 100);

    } elseif($this->imageType == 'png') {

        $newname .= '.png';

        $success = imagepng($thumb, $this->destination . $newname);
        
    } elseif($this->imageType == 'gif') {

        $newname .= '.gif';

        $success = imagegif($thumb, $this->destination . $newname);

    } 

    if($success) {

        $this->messages[] = "$newname created successfully";

    } else {

        $this->messages[] = "Could not create a thumbnail for " . basename($this->original);

    }

    imagedestroy($resource);
    imagedestroy($thumb);
    
  }

  private function checkMimeType($tmp_name)
  {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $tmp_name);
    $this->mimeType = $mime_type;

    if(strpos($mime_type, 'image/') === 0) {

        return true;

    } else {

        return false;

    }

    finfo_close($finfo);

  }




}