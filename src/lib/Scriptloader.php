<?php
/**
 * Class Scriptloader
 * Load all class files in any directories selected
 * 
 * @package     SCRIPTLOG
 * @category    library\Scriptloader 
 * @author      M.Noermoehammad 
 * @license     MIT
 * @version     1.0
 * @since       Since Release 1.0
 *
 */
class Scriptloader
{

 /**
  * Library Path
  * 
  * @var array
  */
 protected static $librayPaths = [];
 
 /**
  * File extension
  * 
  * @var string
  */
 protected static $fileExtensionName = '.php';
 
 /**
  * exclude directory
  * 
  * @var string
  */
 protected static $excludeDirName = '/^etc|passwd|cgi-bin|\..*$/';
 
 /**
  * set library path
  * @param string $paths
  */
 public function setLibraryPaths($paths)
 {
   self::$librayPaths = $paths;
 }
 
 /**
  * add library path to load
  * @param string $path
  */
 public function addLibraryPath($path)
 {
   self::$librayPaths[] = $path;
 }
 
 /**
  * set file extension name
  * @param string $extension
  */
 public function setFileExtension($extension)
 {
    self::$fileExtensionName = $extension;
 }
 
 /**
  * load library
  * @param string $class
  * @return boolean
  */
 public function loadLibrary($class)
 {
     $libraryPath = '';
     
     foreach (self::$librayPaths as $path) {
         
         if ($libraryPath = self::isLibraryFile($class, $path)) {

          include($libraryPath);

          return true;
            
         }
     }
     
     return false;
 }
 
 /**
  * checking library file and it's directory
  *
  * @param string $class
  * @param string $directory
  * @return boolean|string|string|boolean
  *
  */
 protected static function isLibraryFile($class, $directory)
 {
     if ((is_dir($directory)) && (is_readable($directory))) {
         
        $directoryIterator = dir($directory);
         
        while ($filename = $directoryIterator->read()) {
            
            $subLibrary = $directory . $filename;
            
            if (is_dir($subLibrary)) {
                
                if (!preg_match(self::$excludeDirName, $filename)) {
                    
                    if ($fileLibraryPath = self::isLibraryFile($class, $subLibrary . DIRECTORY_SEPARATOR)) {
                        
                        return $fileLibraryPath;
                        
                    }
                    
                }
                
            } else {
                
                if ($filename == $class . self::$fileExtensionName) {
                    
                    return $subLibrary;
                    
                }
                
            }
            
        }
        
     }
     
     return false;
     
 }
 
/**
 * running loader to load all classes needed by the system
 * @method public runLoader()
 * @uses Scriptloader::loadLibrary 
 * @uses SPL Autoload Register
 * 
 */
 public function runLoader()
 {
    spl_autoload_register(null, false);
    spl_autoload_register(array('Scriptloader', 'loadLibrary'));

 }
 
}