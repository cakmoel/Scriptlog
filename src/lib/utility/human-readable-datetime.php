<?php
/**
 * Human Readable Datetime Function
 * Convert datetime field MySQL Database to human readable string
 * 
 * 
 * @category Function
 * @param string $date
 * @param string $format
 * @return string
 * 
 */
function human_readable_datetime($date, $format)
{
  return date_format($date, $format);
}