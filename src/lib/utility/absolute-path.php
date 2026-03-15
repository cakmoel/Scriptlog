<?php
/**
 * absolute_path
 *
 * @category function
 * @author Contributors
 * @license MIT
 * @version 1.0
 * @see https://www.php.net/manual/en/function.realpath.php#84012
 * @param string $path
 * @return string
 * 
 */
function absolute_path($path)
{

$path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
$parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
    
$absolutes = array();
    
foreach ($parts as $part) {
        
  if ('.' === $part) { 
    
    continue; 
  }
        
  if ('..' === $part) {
            
    array_pop($absolutes);
        
  } else {
            
    $absolutes[] = $part;
        
  }
    
}
    
 return implode(DIRECTORY_SEPARATOR, $absolutes);

}