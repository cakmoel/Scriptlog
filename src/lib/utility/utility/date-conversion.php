<?php
/**
 * date_conversion()
 *
 * convert Indonesian date format to English date format or vice versa
 * 
 * @category function
 * @param string $date
 * @param string $locale
 * @return string
 * 
 */
function date_conversion($date, $locale = 'en')
{
  
  if (!$locale) {

    $date_format = substr($date, 8, 2) . "-" . substr($date, 5, 2) . "-" . substr( $date, 0, 4);

  } else {

    $date_format = substr($date, 6, 4) . "-" . substr($date, 3, 2) . "-" . substr($date, 0, 2);

  }

  return $date_format;

}

/**
 * grab_month()
 *
 * @category function
 * @param string $month
 * @return string
 */
function grab_month($month = null)
{

 switch ($month) {

  case '01':   
    return "January";
    break;
  case '02':
    return "February";
    break;
  case '03':
    return "March";
    break;
  case '04':
    return "April";
    break;
  case '05':
    return "May";
    break;
  case '06':
    return "June";
    break;
  case '07':
    return "July";
    break;
  case '08':
    return "August";
    break;
  case '09':
    return "September";
    break;
  case '10':
    return "October";
    break;
  case '11':
    return "November";
    break;
  case '12':
    return "December";
    break;
  default:
   
   if (is_null($month)) {

    scriptlog_error("Can not grab month from your request");

   }
   
   break;

 }
 
}

/**
 * convert_to_timestamp
 *
 * converting date to timestamp
 * 
 * @category function convert_to_timestamp
 * @see https://stackoverflow.com/questions/113829/how-to-convert-date-to-timestamp-in-php
 * @see https://stackoverflow.com/questions/7950854/convert-datetime-to-timestamp-php
 * @param string $datetime
 * 
 */
function convert_to_timestamp($datetime)
{
  $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime, new DateTimeZone(timezone_identifier()));

  if (false === $d) {
    
    scriptlog_error("Incorrect date string");

  } else {

    return $d->getTimestamp();

  }

}

/**
 * convert_to_atom
 *
 * @category function convert_to_atom
 * @see https://stackoverflow.com/questions/1677373/how-to-format-atom-date-time
 * @param ?int $timestamp
 * 
 */
function convert_to_atom($timestamp)
{
  return date(DATE_ATOM, $timestamp);
}