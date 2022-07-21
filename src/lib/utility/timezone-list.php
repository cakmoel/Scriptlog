<?php
/**
 * timezone_list function
 *
 * @see https://stackoverflow.com/questions/1727077/generating-a-drop-down-list-of-timezones-with-php
 * @see https://www.php.net/manual/en/timezones.php
 * @see https://gist.github.com/pavellauko/3082580
 * @see https://stackoverflow.com/questions/44216857/php-filters-list-of-timezone-as-array
 * @see https://stackoverflow.com/questions/4755704/php-timezone-list
 * @return string
 * 
 */
function timezone_list()
{

 $timezoneIdentifiers = DateTimeZone::listIdentifiers();
 $utcTime = new DateTime('now', new DateTimeZone('UTC'));
 $tempTimezones = array();
    
 foreach($timezoneIdentifiers as $timezoneIdentifier){
        
    $currentTimezone = new DateTimeZone($timezoneIdentifier);
    $tempTimezones[] = array('offset' => (int)$currentTimezone->getOffset($utcTime), 'identifier' => $timezoneIdentifier);

 }

 function sort_list($a, $b){
        
    return ($a['offset'] == $b['offset']) 
            ? strcmp($a['identifier'], $b['identifier'])
            : $a['offset'] - $b['offset'];
 }

  usort($tempTimezones, "sort_list");
    
  $timezoneList = array();
    
  foreach($tempTimezones as $tz){
        
    $sign = ($tz['offset'] > 0) ? '+' : '-';
    $offset = gmdate('H:i', abs($tz['offset']));
    $timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset . ') ' . $tz['identifier'];
   
  }

  return $timezoneList;

}