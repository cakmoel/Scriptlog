<?php

/**
 * get_ip_address()
 * 
 * Get IP Address
 * 
 * @category function
 * @see https://stackoverflow.com/questions/1634782/what-is-the-most-accurate-way-to-retrieve-a-users-correct-ip-address-in-php
 * @see https://blog.ircmaxell.com/2012/11/anatomy-of-attack-how-i-hacked.html
 * @return string
 * 
 */
function get_ip_address()
{

  if (detect_proxy_by_headers() === true) {

    return request_ip_address();

  } else {

    return Util::get_client_ip();

  }
  
}

/**
 * zend_ip_address()
 * get ip address from proxy if it's provided
 * 
 * @uses RemoteAddress::getIpAddress
 * @see https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/RemoteAddress.php
 * @see https://framework.zend.com/apidoc/2.1/classes/Zend.Http.PhpEnvironment.RemoteAddress.html
 * @return void
 * 
 */
function zend_ip_address()
{

  $ip = new RemoteAddress();

  return $ip->getIpAddress();
}

/**
 * decbin32
 *
 * @param int $dec
 * 
 */
function decbin32($dec)
{
  return str_pad(decbin($dec), 32, '0', STR_PAD_LEFT);
}

/**
 * ip_range
 *
 * @category function
 * @see https://stackoverflow.com/questions/14985518/cloudflare-and-logging-visitor-ip-addresses-via-in-php
 * @see https://github.com/cloudflarearchive/Cloudflare-Tools/blob/master/cf-joomla/plgCloudFlare/ip_in_range.php
 * @see https://thisinterestsme.com/php-ip-address-cloudflare/
 * @param string $ip
 * @param string $range
 * 
 */
function ip_range($ip, $range)
{
  if (strpos($range, '/') === false) {
    $range .= '/32';
  }

  // $range is in IP/CIDR format eg 127.0.0.1/24
  list($range, $netmask) = explode('/', $range, 2);
  $range_decimal = ip2long($range);
  $ip_decimal = ip2long($ip);
  $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
  $netmask_decimal = ~ $wildcard_decimal;
  return ($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal);
}

/**
 * cloudflare_checkIP
 * @param string $ip
 * 
 */
function cloudflare_checkIP($ip)
{

  $is_cloudflare_ip = false;

  if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {

    foreach (cloudflare_ipranges() as $range) {
      if (ip_range($ip, $range)) {
        $is_cloudflare_ip = true;
        break;
      }
    }
  }

  return $is_cloudflare_ip;
}

/**
 * cloudflare_ipranges
 *
 * @see https://www.cloudflare.com/ips-v4
 * @return array
 * 
 */
function cloudflare_ipranges()
{
  return [
    '103.21.244.0/22',
    '103.22.200.0/22',
    '103.31.4.0/22',
    '104.16.0.0/13',
    '104.24.0.0/14',
    '108.162.192.0/18',
    '131.0.72.0/22',
    '141.101.64.0/18',
    '162.158.0.0/15',
    '172.64.0.0/13',
    '173.245.48.0/20',
    '188.114.96.0/20',
    '190.93.240.0/20',
    '197.234.240.0/22',
    '198.41.128.0/17'
  ];
}

/**
 * cloudflare_request_check
 * @see https://stackoverflow.com/questions/14985518/cloudflare-and-logging-visitor-ip-addresses-via-in-php
 * 
 */
function cloudflare_request_check()
{
  $flag = true;

  if (!isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $flag = false;
  }

  if (!isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
    $flag = false;
  }

  if (!isset($_SERVER['HTTP_CF_RAY'])) {
    $flag = false;
  }

  if (!isset($_SERVER['HTTP_CF_VISITOR'])) {
    $flag = false;
  }

  return $flag;
}

/**
 * is_cloudflare
 */
function is_cloudflare()
{
  $ip_check = cloudflare_checkIP($_SERVER['REMOTE_ADDR']);
  $request_check = cloudflare_request_check();
  return $ip_check && $request_check;
}

/**
 * request_ip_address
 */
function request_ip_address()
{
  
  $check = is_cloudflare();

  if ($check) {

    return $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
  } else {

    return zend_ip_address();
  }

}
