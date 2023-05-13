<?php
/**
 * generate_media_identifier()
 *
 * generating Universally-Unique Identifier or UUID for media identifier
 * according to RFC 4122 UUIDs
 * 
 * @param  string $data default null
 * @see https://www.php.net/manual/en/function.uniqid.php
 * @see https://www.uuidgenerator.net/dev-corner/php
 * @see https://rommelsantor.com/clog/2012/02/23/generate-uuid-in-php/
 * @see https://stackoverflow.com/questions/2040240/php-function-to-generate-v4-uuid/15875555#15875555
 * @see https://stackoverflow.com/questions/105034/how-to-create-a-guid-uuid?rq=1
 * @see https://www.rfc-editor.org/rfc/rfc4122#section-4.4
 * @see https://stackoverflow.com/questions/105034/how-to-create-a-guid-uuid
 * @return string
 * 
 */
function generate_media_identifier($data = null)
{

  if (function_exists("random_bytes")) {

    $data = random_bytes(16);

  } elseif (function_exists("openssl_random_pseudo_bytes")) {

    $data = openssl_random_pseudo_bytes(16);

  } else {

    scriptlog_error("no cryptographically secure random function available");

  }
 
  assert(strlen($data) == 16);

  // Set version to 0100
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
  // Set bits 6-7 to 10
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
  
  // Output the 36 character UUID.
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  
}