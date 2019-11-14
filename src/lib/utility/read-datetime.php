<?php
/**
 * Read Datetime Function
 * Read datetime field from MySQL Database
 * 
 * @param string $datetime
 * @return string
 * 
 */
function read_datetime($datetime)
{
  
  $dateGenerator = new DateGenerator();

  return $dateGenerator -> getExternalDate($datetime);

}