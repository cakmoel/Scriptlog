<?php
/**
 * date_for_database()
 *
 * @category function
 * @see https://stackoverflow.com/questions/2215354/php-date-format-when-inserting-into-datetime-in-mysql
 * @param string $date
 * @return string
 * 
 */
function date_for_database($date)
{

$timestamp = strtotime($date);
$date_formated = date('Y-m-d H:i:s', $timestamp);
return $date_formated;

}