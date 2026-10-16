<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * scriptlog_password
 *
 * Encrypt user password
 *
 * @param string $password
 * @return string
 *
 */
function scriptlog_password($password)
{

    $cost = finding_pwd_cost(0.05, 10);

    $options = ['cost' => $cost];

    return password_hash(base64_encode(hash('sha384', $password, true)), PASSWORD_DEFAULT, $options);
}
