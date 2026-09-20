<?php

/**
 * app_sitename()
 *
 * @category function
 * @author nirmala khanza
 * @license MIT
 * @version 1.0
 *
 */
function app_sitename()
{
    return function_exists('app_setting') ? app_setting('site_name') : "";
}
