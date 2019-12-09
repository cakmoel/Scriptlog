
<?php 
/**
 * Class Uploader
 * Handling file upload beside file image
 *
 * @category  Core Class Class uploader
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class Uploader 
{
	/**
	 * Uploaded's filename
	 * @var string
	 */
	private $filename;

	/**
	 * Uploaded's fileLocation
	 * @var string
	 */
	private $filelocation;

	/**
	 * Uploaded file's destination
	 * @var string
	 */
	private $pathdestination;

	/**
	 * Uploaded file's error message
	 * @var string
	 */
	private $errorMessage;

	/**
	 * Uploaded file's error code
	 * @var string
	 */
	private $errorCode;

	/**
	 * Instantiate automatically
	 * object properties
	 * @param string $key
	 */
	public function __construct( $key )
	{
		$this->filename = $_FILES[$key]['name'];
		$this->filelocation = $_FILES[$key]['tmp_name'];
		$this->errorCode = ($_FILES[$key]['error']);
	}

	/**
	 * folder to keep file uploaded
	 * 
	 * @param string $folder
	 * 
	 */
	public function saveIn( $folder )
	{
		$this->pathdestination = $folder;
	}

	/**
	 * Moving file - save file uploaded
	 * @throws Exception
	 */
	public function save()
	{
		if ( $this->readyToUpload()) {

			move_uploaded_file($this->filelocation, "$this->pathdestination/$this->filename");
				
		} else {

			$exception = new Exception( $this->errorMessage );
			throw $exception;

		}

	}

	/**
	 * Checking file uploaded
	 * 
	 * @return boolean
	 * 
	 */
	private function readyToUpload()
	{
		$folderIsWriteAble = is_writable( $this->pathdestination );

		$tempName = is_uploaded_file($this->filelocation);

		if ( $folderIsWriteAble === false OR $tempName === false ) {

			$this->errorMessage = "Error: destination folder is ";
			$this->errorMessage .= "not writable or there is no file uploaded";
			$canUpload = false;

		} else if ( $this->errorCode === 1) {

			$maxSize = ini_get('upload_max_filesize');
			$this->errorMessage = "Error: File is too big";
			$this->errorMessage .= " Max file size is $maxSize";
			$canUpload = false;
				
		} else if ( $this->errorCode > 1) {

			$this->errorMessage = "Something went wrong!";
			$this->errorMessage .= "Error code: $this->errorCode ";

		} else {

			$canUpload = true;

		}

		return $canUpload;

	}

}