<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

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

    (version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

    // The selective/archive-bomb scanner is an optional dependency. Skip
    // scanning (fail open with a log entry) when it is not installed so
    // uploads keep working instead of fataling on an unknown class.
    if (!class_exists(BombScanner::class)) {
        error_log('zip_file_scanner: BombScanner unavailable, skipping archive bomb scan.');
        return false;
    }

    $file = new SplFileObject($filename);

    $scanner = new BombScanner();

    $scanner->addEngine(new ZipBombEngine());

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

    (version_compare(PHP_VERSION, '7.4', '>=')) ? clearstatcache() : clearstatcache(true);

    // See zip_file_scanner(): optional dependency, fail open with a log entry.
    if (!class_exists(BombScanner::class)) {
        error_log('rar_file_scanner: BombScanner unavailable, skipping archive bomb scan.');
        return false;
    }

    $file = new SplFileObject($filename);

    $scanner = new BombScanner();

    $scanner->addEngine(new RarBombEngine());

    $result = $scanner->scanFile($file);

    return ($result->isBomb()) ? true : false;
}
