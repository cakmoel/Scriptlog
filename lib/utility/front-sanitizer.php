<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * front_sanitizer()
 *
 * @category function
 * @author Nirmalakhanza
 * @return object|string
 *
 */
function front_sanitizer()
{
    return (class_exists('Sanitize')) ? new Sanitize() : "";
}
