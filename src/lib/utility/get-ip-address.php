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

  if (true === detect_proxy_by_headers()) {

    return request_ip_address();

  } else {

    return (class_exists('Util')) ? Util::get_client_ip() : "";

  }
  
}

/**
 * zend_ip_address()
 * 
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

  $ip = class_exists('RemoteAddress') ? new RemoteAddress() : "";
  return (method_exists($ip, 'getIpAddress')) ? $ip->getIpAddress() : "";
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
  if (strpos($range, '/') !== false) {
    // $range is in IP/NETMASK format
    list($range, $netmask) = explode('/', $range, 2);

    if (strpos($netmask, '.') !== false) {
      // $netmask is a 255.255.0.0 format
      $netmask = str_replace('*', '0', $netmask);
      $netmask_dec = ip2long($netmask);

      return (ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec);

    } else {
      // $netmask is a CIDR size block
      // fix the range argument
      $x = explode('.', $range);
      while (count($x) < 4) {
        $x[] = '0';
      }
        list($a, $b, $c, $d) = $x;
        $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c, empty($d) ? '0' : $d);
        $range_dec = ip2long($range);
        $ip_dec = ip2long($ip);

      # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
      #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

      # Strategy 2 - Use math to create it
        $wildcard_dec = pow(2, (32 - $netmask)) - 1;
        $netmask_dec = ~$wildcard_dec;

      return ($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec);

    }
  } else {
    // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
    if (strpos($range, '*') !== false) { // a.b.*.* format
      // Just convert to A-B format by setting * to 0 for A and 255 for B
      $lower = str_replace('*', '0', $range);
      $upper = str_replace('*', '255', $range);
      $range = "$lower-$upper";
    }

    if (strpos($range, '-') !== false) { // A-B format
      list($lower, $upper) = explode('-', $range, 2);
      $lower_dec = (float)sprintf("%u", ip2long($lower));
      $upper_dec = (float)sprintf("%u", ip2long($upper));
      $ip_dec = (float)sprintf("%u", ip2long($ip));
      
      return ($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec);
    }
    return false;
  }
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
