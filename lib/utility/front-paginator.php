<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * front_paginator()
 *
 * @category function
 * @author nirmalakhanza
 * @param int $perPage
 * @param  $instance
 * @return object|bool
 *
 */
function front_paginator($perPage, $instance)
{
    return (class_exists('Paginator')) ? new Paginator($perPage, $instance) : "";
}
