<?php defined('SCRIPTLOG') || die("Direct access not permitted");
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
class ProviderException extends Exception implements IProviderThrowable
{
    
protected $message = 'Unknown Exception';

protected $previous;

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