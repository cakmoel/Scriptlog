<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * EventException Class extends Exception implements IEventThrowable
 *
 * @category  Core Class
 * @link      https://secure.php.net/manual/en/language.exceptions.php#91159
 * @see       https://www.php.net/manual/en/language.exceptions.extending.php
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class EventException extends Exception implements IEventThrowable
{
  
  
protected $message = 'Unknown Exception';

public function __construct($message = null, $code = 0, Exception $previous = null)
{

  $code = $this->getCode();

  if (!$message) {

    throw new $this('Unknown'.get_class($this));
    
  }

  parent::__construct($message, $code, $previous);

  if (!is_null($previous)) {

    $this->previous = $previous;
    
  }
  
}

public function __toString()
{
  return get_class($this) . "'{$this->message}' in {$this->getFile()}({$this->getLine()})\n"
                            . "{$this->getTraceAsString()}";
}
  
}