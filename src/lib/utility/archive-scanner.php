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

$file = new SplFileObject($filename);

$scanner = new BombScanner();

$scanner->addEngine(new ZipBombEngine());

( version_compare(PHP_VERSION, '7.4', '>=') ) ? clearstatcache() : clearstatcache(true);

$result = $scanner->scanFile($file);

return ($result->isBomb()) ? true : false;

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

$file = new SplFileObject($filename);

$scanner = new BombScanner();

$scanner->addEngine(new RarBombEngine());

( version_compare(PHP_VERSION, '7.4', '>=') ) ? clearstatcache() : clearstatcache(true);

$result = $scanner->scanFile($file);

return ($result->isBomb()) ? true : false;

}