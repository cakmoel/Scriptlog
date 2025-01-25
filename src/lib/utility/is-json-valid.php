<?php 
/**
 * is_json_valid()
 * 
 * @category function
 * @link https://www.php.net/manual/en/function.json-validate.php#129148
 *
 * @param string $string
 * @return boolean
 * 
 */
function is_json_valid(string $string): bool
{
    json_decode($string);

    return json_last_error() === JSON_ERROR_NONE;
}