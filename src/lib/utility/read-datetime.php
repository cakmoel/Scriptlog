<?php

/**
 * Read Datetime Function
 * Read datetime field from MySQL Database
 *
 * @param string $datetime
 * @uses DateGenerator::getExternalData
 * @return string
 *
 */
function read_datetime($datetime)
{
    $dateGenerator = class_exists('DateGenerator') ? new \Scriptlog\Core\DateGenerator() : "";
    return (method_exists($dateGenerator, 'getExternalDate')) ? $dateGenerator->getExternalDate($datetime) : "";
}
