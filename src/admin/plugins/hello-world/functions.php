<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Hello World Plugin Functions
 *
 * Helper functions for the Hello World plugin
 *
 * @category Plugin Functions
 * @author Your Name
 * @license MIT
 * @version 1.0.0
 *
 */

/**
 * Get Hello World plugin instance
 *
 * @return HelloWorldPlugin
 */
function hello_world_plugin()
{
    static $instance = null;

    if (null === $instance) {
        $instance = new HelloWorldPlugin();
    }

    return $instance;
}

/**
 * Display hello world message
 *
 * @param string $message
 * @return string
 */
function hello_world_display($message = '')
{
    $plugin = hello_world_plugin();
    return $plugin->frontendDisplay($message);
}

/**
 * Get plugin info
 *
 * @return array
 */
function hello_world_get_info()
{
    $plugin = hello_world_plugin();
    return $plugin->getInfo();
}
