<?php
/**
 * is_valid_domain()
 * 
 * a function to validate the domain name
 * 
 * @category Function
 * @param string $url
 * @see https://www.w3schools.in/php-script/check-domain-name-is-valid/
 * @param string $url
 * @return boolean
 * 
 */
function is_valid_domain($url)
{

 $validation = false;
    
 $urlparts = parse_url(filter_var($url, FILTER_SANITIZE_URL));
    
  if (!isset($urlparts['host'])) {
        
    $urlparts['host'] = $urlparts['path'];

  }

  if ($urlparts['host']!='') {
    
    if (!isset($urlparts['scheme'])) {
         
      $urlparts['scheme'] = 'http';
    
    }
    
    if (checkdnsrr($urlparts['host'], 'A') && in_array($urlparts['scheme'],array('http','https')) && ip2long($urlparts['host']) === FALSE){ 
         
       $urlparts['host'] = preg_replace('/^www\./', '', $urlparts['host']);
       $url = $urlparts['scheme'].'://'.$urlparts['host']. "/";            
         
        if (filter_var($url, FILTER_VALIDATE_URL) !== false && @get_headers($url)) {
             $validation = true;
        }

    }

  }

  return (!$validation) ? false : true;

}

/**
 * domain_name
 * returning server http host where website hosted
 *
 * @category Function
 * @return string
 * 
 */
function domain_name()
{
  return (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : "";
}