<?php 
/**
 * Write a to an INI file
 * 
 * ===== Example usage: =====
 * // load ini file values into array
 * $theme = parse_ini_file('theme.ini', true);
 * // add some additional values
 * $theme['main']['foobar'] = 'baz';
 * $theme['main']['const']['a'] = 'UPPERCASE';
 * $theme['main']['const']['b'] = 'UPPER_CASE WITH SPACE';
 * $theme['main']['array'][] = 'Some Value';
 * $theme['main']['array'][] = 'ADD';
 * $theme['third_section']['urls']['docs'] = 'http://php.net';
 * 
 * ===== write ini file =====
 * call function: 
 * write_ini('config.ini', $config);
 * 
 * @author  Lawrence Cherone
 * @link https://stackoverflow.com/questions/5695145/how-to-read-and-write-to-an-ini-file-with-php
 * @param string $file
 * @param array $data
 * 
 */
function write_ini($file, $array = []) 
{
  if (!is_string($file)) {
      scriptlog_error("Function argument 1 must be a string");
  }

  if (!is_array($array)) {
     scriptlog_error("Function argument 2 must be an array");
  }

  // process array
  $data = array();
  foreach ($array as $key => $val) {
      if (is_array($val)) {
          $data[] = "[$key]";
          foreach ($val as $skey => $sval) {
              if (is_array($sval)) {
                  foreach ($sval as $_skey => $_sval) {
                      if (is_numeric($_skey)) {
                          $data[] = $skey.'[] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                      } else {
                          $data[] = $skey.'['.$_skey.'] = '.(is_numeric($_sval) ? $_sval : (ctype_upper($_sval) ? $_sval : '"'.$_sval.'"'));
                      }
                  }
              } else {
                  $data[] = $skey.' = '.(is_numeric($sval) ? $sval : (ctype_upper($sval) ? $sval : '"'.$sval.'"'));
              }
          }
      } else {
          $data[] = $key.' = '.(is_numeric($val) ? $val : (ctype_upper($val) ? $val : '"'.$val.'"'));
      }
      // empty line
      $data[] = null;
  }

  // open file pointer, init flock options
  $fp = fopen($file, 'w');
  $retries = 0;
  $max_retries = 100;

  if (!$fp) {
      return false;
  }

  // loop until get lock, or reach max retries
  do {
      if ($retries > 0) {
          usleep(rand(1, 5000));
      }
      $retries += 1;
  } while (!flock($fp, LOCK_EX) && $retries <= $max_retries);

  // couldn't get the lock
  if ($retries == $max_retries) {
      return false;
  }

  // got lock, write data
  fwrite($fp, implode(PHP_EOL, $data).PHP_EOL);

  // release lock
  flock($fp, LOCK_UN);
  fclose($fp);

  return true;

}
    
