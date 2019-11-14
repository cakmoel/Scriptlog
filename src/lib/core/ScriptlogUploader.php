<?php
/**
 * ScriptlogUploader Class
 * This class designed to handle file uploaded especially image or picture.
 * 
 * @package  SCRIPTLOG/LIB/CORE/ScriptlogUploader
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class ScriptlogUploader
{
  /**
   * file uploaded
   * 
   * @var array
   * 
   */
  protected $uploaded = [];

  /**
   * destination directory file uploaded
   * 
   * @var string
   * 
   */
  protected $destination;

  /**
   * Maximum file size allowed
   * 
   * @var integer|numeric
   * 
   */
  protected $max = 91200;

  /**
   * Messages taken from processing file uploaded
   * whether success or error
   * 
   * @var mixed
   * 
   */
  protected $messages = [];

  /**
   * Permitted file type
   * 
   * @var array
   * 
   */
  protected $permitted = [
      'image/gif',
      'image/jpeg',
      'image/pjpeg',
      'image/png'
  ];

  /**
   *  Message from file renamed
   * 
   * @var bool
   * 
   */
  protected $renamed = false;

  /**
   * Filename
   * 
   * @var array
   * 
   */
  protected $filenames = [];

  /**
   * Initialize object properties
   * 
   * @param string $path
   * @throws ScriptlogUploaderException
   * 
   */
  public function __construct($path)
  {
    if ((!is_dir($path)) || (!is_writable($path))) {

        throw new ScriptlogUploaderException("$path must be a valid, writable directory");

    }

    $this->destination = $path;
    $this->uploaded = $_FILES;

  }

  /**
   * Move file
   * 
   * @param bool $overwrite
   * 
   */
  public function move($overwrite = false)
  {
    $field = current($this->uploaded);

    if (is_array($field['name'])) {

        foreach ($field['name'] as $number => $filename) {

            // process multiple upload
            $this->renamed = false;
            
            $this->processFile($filename, $field['error'][$number], $field['size'][$number], $field['type'][$number], $field['tmp_name'][$number], $overwrite);

        }

    } else {

        $this->processFile( $field['name'], $field['error'], $field['size'], $field['type'], $field['tmp_name'], $overwrite);

    }

  }

  /**
   * get Alert
   * 
   * @return mixed
   * 
   */
  public function getAlert()
  {
    return $this->messages;
  }

  /**
   * get maximum size
   * 
   * @return mixed
   * 
   */
  public function getMaxSize()
  {
    return number_format($this->max/1024, 1) . 'kB';
  }

  /**
   * set maximum size
   * 
   * @throws ScriptlogUploaderException
   * @param numeric $num
   * 
   */
  public function setMaxSize($num)
  {
    if (!is_numeric($num)) {

        throw new ScriptlogUploaderException("Maximum size must be a number");

    }

    $this->max = (int)$num;

  }

  /**
   * Adding permitted file type
   * 
   * @param array $types
   * 
   */
  public function addPermittedTypes(array $types)
  {
    $this->isValidMime($types);
    $this->permitted = array_merge($this->permitted, $types);
  }

  /**
   * Set permitted mime type
   * 
   * @var  mixed $types
   * 
   */
  public function setPermittedTypes(array $types)
  {
    $this->isValidMime($types);
    $this->permitted = $types;
  }

  /**
   * get filename
   * @return string
   */
  public function getFilenames()
  {
    return $this->filenames;
  }

  /**
   * Processing file
   * 
   * @param string $filename
   * @param mixed $error
   * @param numeric|integer $size
   * @param string $type
   * 
   */
  protected function processFile($filename, $error, $size, $type, $tmp_name, $overwrite)
  {
    $no_problemo = $this->checkError($filename, $error);

    if($no_problemo) {

        $sizeNoProblem = $this->checkSize($filename, $size);
        $typeNoProblem = $this->checkType($filename, $type);
        $mimeNoProblem = $this->checkMimeType($filename, $tmp_name);

        if($sizeNoProblem && $typeNoProblem && $mimeNoProblem) {

            $name = $this->checkName($filename, $overwrite);
            
            $success = move_uploaded_file($tmp_name, $this->destination.$name);

            if($success) {

                $this->filenames[] = $name;
                $message = "$filename uploaded successfully";

                if($this->renamed) {

                    $message .= " and renamed $name";

                }

                $this->messages[] = $message;

            } else {

                $this->messages[] = "Could not upload $filename";

            }

        }

    }

  }

  /**
   * Checking error
   * 
   * @param string $filename
   * @param mixed $error
   * 
   */
  protected function checkError($filename, $error)
  {

    switch ($error) {

        case 0 :
            
            return true;
            
            break;
        
        case 1 :
        case 2 :

            $this->messages[] = "$filename exceeds maximum size: ".$this->getMaxSize();
            return true;

        case 3 :
           
            $this->messages[] = "Error uploading $filename. Please try again.";
            return true;
        
        case 4 :

            $this->messages[] = "There is no file selected";
            return false;

        default:
            
            $this->messages[] = "System error uploading $filename. Check your system.";
            return false;

    }

  }

  /**
   * Checking size
   * 
   * @param string $filename
   * @param numeric|integer $size
   * 
   */
  protected function checkSize($filename, $size)
  {
    if($size == 0) {

        return false;

    } elseif ($size > $this->max) {

        $this->messages[] = "$filename exceeds maximum size: " . $this->getMaxSize();
        return false;

    } else {

        return true;

    }

  }

  /**
   * Checking type
   * 
   * @param string $filename
   * @param string $type
   * 
   */
  protected function checkType($filename, $type)
  {
    
    if(empty($type)) {

        return false;

    } elseif(!in_array($type, $this->permitted)) {

        $this->messages[] = "$filename is not permitted type of file";
        return false;

    } else {

        return true;

    }

  }

  /**
   * Checking MIME type
   * 
   * @param mixed $types
   */
  protected function checkMimeType($filename, $tmp_name)
  {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $file_contents = file_get_contents($tmp_name);
    $mime_type = $finfo -> buffer($file_contents);

    $extension = array_search($mime_type, $this->permitted, true);

    if(false === $extension) {

        $this->messages[] = "Sorry, $filename invalid file format";
        return false;

    }

    return true;

  }

  /**
   * Whether MIME type valid or not
   * 
   * @param mixed $types
   * 
   */
  protected function isValidMime($types)
  {
    
    $alsoValid = array(
        'image/tiff',
		'application/pdf',
		'text/plain',
		'text/rtf'
    );

    $valid = array_merge($this->permitted, $alsoValid);
    
    foreach ($types as $type) {

        if(!in_array($type, $valid)) {

            throw new ScriptlogUploaderException("$type is not permitted MIME type");

        }

    }

  }
 
  /**
   * Checking name of file uploaded
   * 
   * @param string $name
   * @param bool $overwrite
   * 
   */
  protected function checkName($name, $overwrite)
  {
      
    $nospaces = str_replace(' ', '_', $name);
      
    if($nospaces != $name) {

        $this->renamed = true;

    }

    if(!$overwrite) {

        $existing = scandir($this->destination);
        if(in_array($nospaces, $existing)) {

            $dot = strrpos($nospaces, '.');
            if($dot) {

                $base = substr($nospaces, 0, $dot);
                $extension = substr($nospaces, $dot);

            } else {

                $base = $nospaces;
                $existing = '';

            }

            $i = 1;

            do {
                
                $nospaces = $base . '_' . $i++ . $extension;

            } while (in_array($nospaces, $existing));

            $this->renamed = true;

        }

    }
    
    return $nospaces;

  }

}