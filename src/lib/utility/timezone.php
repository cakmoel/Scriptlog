<?php
/**
 * timezone_list()
 *
 * @category function timezone_list
 * @see https://www.php.net/manual/en/function.date-default-timezone-get.php
 * @see https://www.php.net/manual/en/function.date-default-timezone-set.php
 * @see https://stackoverflow.com/questions/1727077/generating-a-drop-down-list-of-timezones-with-php
 * @see https://www.php.net/manual/en/timezones.php
 * @see https://www.php.net/manual/en/datetime.configuration.php#ini.date.timezone
 * @see https://gist.github.com/pavellauko/3082580
 * @see https://stackoverflow.com/questions/44216857/php-filters-list-of-timezone-as-array
 * @see https://stackoverflow.com/questions/4755704/php-timezone-list
 * @see https://stackoverflow.com/questions/851574/how-do-i-get-greenwich-mean-time-in-php/9328760#9328760
 * 
 * @return string
 * 
 */
function timezone_list()
{

 $timezoneIdentifiers = DateTimeZone::listIdentifiers();
 $utcTime = new DateTime('now', new DateTimeZone('UTC'));
 $tempTimezones = array();
    
 foreach ($timezoneIdentifiers as $timezoneIdentifier) {
        
    $currentTimezone = new DateTimeZone($timezoneIdentifier);
    $tempTimezones[] = array('offset' => (int)$currentTimezone->getOffset($utcTime), 'identifier' => $timezoneIdentifier);

 }

  usort($tempTimezones, function ($a, $b){
    return ($a['offset'] == $b['offset']) ? strcmp($a['identifier'], $b['identifier']) : $a['offset'] - $b['offset'];
  });
    
  $timezoneList = array();
    
  foreach ($tempTimezones as $tz) {
        
    $sign = ($tz['offset'] > 0) ? '+' : '-';
    $offset = gmdate('H:i', abs($tz['offset']));
    $timezoneList[$tz['identifier']] = '(UTC ' . $sign . $offset . ') ' . $tz['identifier'];
   
  }

  return $timezoneList;

}

/**
 * timezone_picker
 *
 * @category function timezone_picker()
 * @author Justin VIncent https://stackoverflow.com/users/112332/justin-vincent
 * @see https://www.php.net/manual/en/function.date-default-timezone-get.php
 * @see https://www.php.net/manual/en/function.date-default-timezone-set.php
 * @see https://stackoverflow.com/questions/1727077/generating-a-drop-down-list-of-timezones-with-php
 * @see https://www.php.net/manual/en/timezones.php
 * @see https://www.php.net/manual/en/datetime.configuration.php#ini.date.timezone
 * @see https://gist.github.com/pavellauko/3082580
 * @see https://stackoverflow.com/questions/44216857/php-filters-list-of-timezone-as-array
 * @see https://stackoverflow.com/questions/4755704/php-timezone-list
 * @see https://stackoverflow.com/questions/851574/how-do-i-get-greenwich-mean-time-in-php/9328760#9328760
 * 
 */
function timezone_picker() 
{
   static $timezones = null;
   if ($timezones === null) {
       $timezones = [];
       $offsets = [];
       $now = new \DateTime('now', new \DateTimeZone('UTC'));
       foreach (\DateTimeZone::listIdentifiers(\DateTimeZone::ALL) as $timezone) {

           // Calculate offset
           $now->setTimezone(new \DateTimeZone($timezone));
           $offsets[] = $offset = $now->getOffset();

           // Display text for UTC offset
           $hours = intval($offset / 3600);
           $minutes = abs(intval($offset % 3600 / 60));
           $utcDiff = 'UTC' . ($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');

           // Display text for name
           $name = str_replace('/', ', ', $timezone);
           $name = str_replace('_', ' ', $name);
           $name = str_replace('St ', 'St. ', $name);

           $timezones[$timezone] = "$name ($utcDiff)";
       }
   }

   return $timezones;
}

/**
 * print_timezone_list()
 *
 * @category function
 */
function print_timezone_list()
{
  $timezone = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
  return print_r($timezone);
}

/**
 * timezone_identifier
 *
 * retrieve timezone_identifier info and return it record
 * 
 * @category function
 */
function timezone_identifier()
{
  $timezone_identifier = json_decode(app_info()['timezone_setting'], true);
  return $timezone_identifier['timezone_identifier'];
}