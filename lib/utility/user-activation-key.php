<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * user_activation_key
 *
 * Generating user activation key
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $value
 * @return string
 *
 */
function user_activation_key($value)
{
    $salt = simple_salt(64);
    // random_int() is a CSPRNG-backed source for token entropy; never
    // silently downgrade token generation to a weak source.
    return sha1(random_int(10000, 99999) . time() . $value . $salt);
}
