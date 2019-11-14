<?php
/**
 * CoreException Class extends Exception implements IException
 *
 * @package   SCRIPTLOG/LIB/CORE/CoreException
 * @category  Core Class
 * @link      https://secure.php.net/manual/en/language.exceptions.php#91159
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
abstract class CoreException extends Exception implements IException
{
  
  protected $message = 'Unknown Exception';

  protected $code = 0;

  protected $file;

  protected $line; 

  private $string;

  private $trace;

  public function __construct($message = null, $code = 0)
  {
    if (!$message) {
      throw new $this('Unknown'.get_class($this));
    }

    parent::__construct($message, $code);

  }

  public function __toString()
  {
    return get_class($this) . "'{$this->message}' in {$this->file}({$this->line})\n"
                            . "{$this->getTraceAsString()}";
  }
  
}