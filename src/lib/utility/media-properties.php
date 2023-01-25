<?php
/**
 * Function media properties
 *
 * @param string $json_data
 * @return mixed
 * 
 */
function media_properties($json_data = array())
{
 return json_decode($json_data, true);
}