<?php  
/**
 * Class Template
 * 
 * @category Core Class
 * @author Nuno Freitas <nunofreitas@gmail.com>
 * @see https://www.broculos.net/2008/03/how-to-make-simple-html-template-engine.html
 * @see https://stackoverflow.com/questions/5540828/how-to-make-a-php-template-engine
 * 
 * @version 1.0
 * 
 */
class Template
{

protected $file;

protected $values = [];

protected $errors;

public function __construct($file)
{
  $this->file = basename($file);
}

public function set($key, $value)
{
  $this->values[$key] = $value;
}

public function output()
{

try {
    
  if (!file_exists($this->file)) {
    return "Error loading template file ($this->file).";
  }
   $output = file_get_contents($this->file);

   $keys = array_keys($this->values);
   $pattern = '$\[@(' . implode('|', array_map('preg_quote', $keys)) . ')\]$';

    $output = preg_replace_callback($pattern, function($match) {
    
       return $this->values[$match[1]];

    }, $output);

    return safe_html($output);


} catch (ThemeException $e) {
   
   $this->errors = LogError::setStatusCode(http_response_code(404));
   $this->errors = LogError::newMessage($e);
   $this->errors = LogError::customErrorMessage();

}
 
}

public static function merge($templates, $separator = "\n")
{

  $output = "";

  foreach( $templates as $template ) {

     $content = (get_class($templates) !== "Template") ? scriptlog_error("Error incorrect type - expected Template") : $template->output();

     $output .= $content . $separator;

  }

  return safe_html($output);

}

}