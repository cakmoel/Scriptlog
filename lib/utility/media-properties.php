<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Function media properties
 *
 * @param string|array $json_data JSON-encoded metadata string
 * @return mixed
 *
 */
function media_properties($json_data = array())
{
    if (!is_string($json_data)) {
        return [];
    }

    return json_decode($json_data, true);
}
