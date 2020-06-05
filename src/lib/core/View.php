<?php 
/**
 * Class View
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class View
{
  
  /**
   * Directory
   * @var string
   */
  private $dir;
  
  /**
   * Action
   * @var string
   */
  private $file;
  
  /**
   * Data
   * @var array
   */
  private $data = [];
  
  /**
   * Error
   * 
   * @var string
   */
  private $errors;
  
  /**
   * Constructor
   * 
   * @param string $accessPath
   * @param string $dir
   * @param string $modulePath
   * @param string $file
   */
  public function __construct($eventPath, $uiPath, $modulePath, $file = null)
  {
    
    // make sure event path for administrator in admin folder
    if ($eventPath == 'admin') {
      $this->dir = APP_ROOT . APP_ADMIN . DS . $uiPath . DS .$modulePath . DS;
    }
   
    if (!is_null($file)) $this->file = $file;
    
  }
  
  /**
   * Set view
   * 
   * @param string $key
   * @param string $value
   * 
   */
  public function set($key, $value)
  {
     $this->data[$key] = $value;
  }
  
  /**
   * Get view
   * 
   * @param string $key
   * 
   */
  public function get($key)
  {
     return $this->data[$key];
  }
  
  /**
   * render
   * output view or content
   * 
   * @method public render()
   * 
   */
  public function render()
  {
     
    try {
        
        if (!is_dir($this->dir) && !file_exists($this->dir. $this->file . '.php')) {
            header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found");
            throw new ViewException('View '.$this->file.'.php'. ' does not exists');
        }
        
        unreg_globals();
        extract($this->data, EXTR_SKIP);
        ob_start();
        require $this->dir.basename($this->file).'.php';
        $render = ob_get_contents();
        ob_end_clean();
        echo $render;
        
    } catch (ViewException $e) {
        
        $this->errors = LogError::setStatusCode(http_response_code());
        $this->errors = LogError::newMessage($e);
        $this->errors = LogError::customErrorMessage('admin');
        
    } 
    
  }
   
}