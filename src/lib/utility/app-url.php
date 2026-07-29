<?php

/**
 * app_url()
 *
 * Retrieving URL configuration from database
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 *
 */
function app_url()
{
    return function_exists('app_setting') ? app_setting('app_url') : "";
}
