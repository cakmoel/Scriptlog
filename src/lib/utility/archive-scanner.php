<?php

use Selective\ArchiveBomb\Scanner\BombScanner;
use Selective\ArchiveBomb\Engine\ZipBombEngine;
use Selective\ArchiveBomb\Engine\RarBombEngine;


/**
 * zip_file_scanner
 *
 * @see https://github.com/selective-php/archive-bomb-scanner
 * @param string $filename
 * @return boolean
 * 
 */
function zip_file_scanner($filename)
{

$is_bomb = false;

$file = new SplFileObject($filename);

$scanner = new BombScanner();

$scanner->addEngine(new ZipBombEngine());

$result = $scanner->scanFile($file);

if (version_compare(PHP_VERSION, '5.6', '>=')) {

   clearstatcache();

} else {

   clearstatcache(true);
   
}

if ($result->isBomb()) {

    $is_bomb = true;

} else {

    $is_bomb = false;

}

return $is_bomb;

}

/**
 * rar_file_scanner
 *
 * @param string $filename
 * @return boolean
 * 
 */
function rar_file_scanner($filename)
{

$is_bomb = false;

$file = new SplFileObject($filename);

$scanner = new BombScanner();

$scanner->addEngine(new RarBombEngine());

$result = $scanner->scanFile($file);

if ( $result->isBomb()) {

   $is_bomb = true;

} else {

   $is_bomb = false;

}

return $is_bomb;

}

/**
 * get_zip_size
 *
 * @category Function
 * @see https://www.php.net/manual/en/function.zip-entry-filesize.php
 * @param string $filename
 * @return int|numeric|number
 * 
 */
function get_zip_size($filename)
{

 $size = 0;
 
 $resource = zip_open($filename);
 
 if (is_resource($resource)) {

   while ($dir_resource = zip_read($resource)) {
    
      $size += zip_entry_filesize($dir_resource);
      
   }
      
  zip_close($resource);  

 } 

 return $size;

}