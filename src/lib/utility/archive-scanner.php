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

if (version_compare(PHP_VERSION, '7.4', '>=')) {

   clearstatcache();

} else {

   clearstatcache(true);
   
}

$result = $scanner->scanFile($file);

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

if (version_compare(PHP_VERSION, '7.4', '>=')) {

   clearstatcache();

} else {

   clearstatcache(true);
   
}

$result = $scanner->scanFile($file);

if ( $result->isBomb()) {

   $is_bomb = true;

} else {

   $is_bomb = false;

}

return $is_bomb;

}