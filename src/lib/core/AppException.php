<?php
/**
 * Class AppException extends Exception implements IAppThrowable
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class AppException extends Exception implements IAppThrowable
{
    
protected $message = '';

public function __construct($message = null, $code = 0, Exception $previous)
{

 $code = $this->getCode();
 
 if (!$message) {
      
    throw new $this('Unknown'.get_class($this));
    
 }

 parent::__construct($message, $code, $previous);

 if (!is_null($previous))
 {
   $this->previous = $previous;
 }
 
}

public function __toString()
{
    return get_class($this) . "'{$this->message}' in {$this->getFile()}({$this->getLine()})\n"
                            . "{$this->getTraceAsString()}";
}

}