<?php
/**
 * Class ScriptlogImageUploader extends Class ScriptlogUploader
 * 
 * @package  SCRIPTLOG/LIB/CORE/ScriptlogImageUploader
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class ScriptlogImageUploader extends ScriptlogUploader
{
  /**
   * thumbnail directory
   * @var  string 
   */
  protected $thumbDestination;

  /**
   * Remove original file
   * 
   * @var bool
   * 
   */
  protected $deleteOriginal;

  /**
   * Suffix
   * 
   * @var string
   */
  protected $suffix;

/**
 * filenames
 * 
 * @var array
 * 
 */
  protected $filenames = [];

  /**
   * Initialize instance of object properties and method
   * 
   * Constructors
   * 
   * @param string $path
   * @param bool $deleteOriginal
   * 
   */
  public function __construct($path, $deleteOriginal = false)
  {
    parent::__construct($path);

    $this->thumbDestination = $path;

    $this->deleteOriginal = $deleteOriginal;

  }

  /**
   * Set image thumbsize destination
   * 
   * @param string $path
   * 
   */
  public function setThumbDestination($path)
  {
    if((!is_dir($path)) || (!is_writable($path))) {

        throw new ScriptlogUploaderException("$path must be a valid, writable directory");
        
    }

    $this->thumbDestination = $path;

  }

  public function setThumbSuffix($suffix)
  {
    if(preg_match('/\w+/', $suffix)) {

      if(strpos($suffix, '_') !== 0) {

        $this->suffix = '_' . $suffix;

      } else {

        $this->suffix = $suffix;

      }
      
    } else {

      $this->suffix = '';

    }

  }

  public function getFilenames()
  {
    return $this->filenames;
  }

  protected function createThumb($image)
  {
    
    $thumb = new ScriptlogImageThumbnail($image);

    $thumb -> setDestination($this->thumbDestination);

    $thumb -> setSuffix($this->suffix);

    $thumb -> generateImageThumb();

    $messages = $thumb -> getAlert();

    $this->messages = array_merge($this->messages, $messages);

  }

  protected function processFile($filename, $error, $size, $type, $tmp_name, $overwrite)
  {
    $no_problemo = $this->checkError($filename, $error);

    if($no_problemo) {

      $sizeNoProblemo = $this->checkSize($filename, $size);
      $typeNoProblemo = $this->checkType($filename, $type);
      $mimeNoProblemo = $this->checkMimeType($filename, $tmp_name);

      if($sizeNoProblemo && $typeNoProblemo && $mimeNoProblemo) {

         $name = $this->checkName($filename, $overwrite);

         $success = move_uploaded_file($tmp_name, $this->destination . $name);

         if($success) {

            $this->filenames[] = $name;

            if(!$this->deleteOriginal) {
               
                $message = "$filename uploaded successfully";

                if($this->renamed) {

                   $message .= " and renamed $name";

                }

                $this->messages[] = $message;

            }

            $this->createThumb($this->destination . $name);

            if($this->deleteOriginal) {

               unlink($this->destination . $name);

            }

         }

      }

    }

  }
  
}