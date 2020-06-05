<?php
/**
 * a function to validate the domain name
 * @see https://www.w3schools.in/php-script/check-domain-name-is-valid/
 * @param string $url
 * @return boolean
 * 
 */
function is_valid_domain($url)
{

 $validation = FALSE;
    
 $urlparts = parse_url(filter_var($url, FILTER_SANITIZE_URL));
    
  if(!isset($urlparts['host'])){
        
    $urlparts['host'] = $urlparts['path'];

  }

  if($urlparts['host']!=''){
    
    if (!isset($urlparts['scheme'])){
         
        $urlparts['scheme'] = 'http';
    
    }
    
    if(checkdnsrr($urlparts['host'], 'A') && in_array($urlparts['scheme'],array('http','https')) && ip2long($urlparts['host']) === FALSE){ 
         
       $urlparts['host'] = preg_replace('/^www\./', '', $urlparts['host']);
       $url = $urlparts['scheme'].'://'.$urlparts['host']. "/";            
         
        if (filter_var($url, FILTER_VALIDATE_URL) !== false && @get_headers($url)) {
             $validation = TRUE;
        }

    }

  }

  if(!$validation){

    return false;

  } else {

    return true;

  }

}

/**
 * domain name function
 * returning server http host where website hosted
 *
 * @return string
 * 
 */
function domain_name()
{
  $domain = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] != 'localhost') ? $_SERVER['HTTP_HOST'] : "";

  return $domain;
}