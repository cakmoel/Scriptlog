<?php

/**
 * app_tagline
 *
 * retrieving tagline info from tbl_settings
 *
 * @category functions
 * @author Nirmala Khanza
 * @license MIT
 * @version 1.0
 *
 */
function app_tagline()
{
    return function_exists('app_setting') ? app_setting('site_tagline') : "";
}
