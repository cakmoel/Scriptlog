<?php

/**
 * get_table_prefix
 *
 * @category utility function
 * @author Nirmala Khanza <nirmala.adiba.khanza@gmail.com>
 * @license MIT
 * @version 1.0.0
 * @return string
 */
function get_table_prefix()
{
    // Check if we're in test environment
    if (defined('SCRIPTLOG_TEST_MODE') || isset($GLOBALS['__test_prefix'])) {
        return ''; // No prefix in test database
    }
    
    static $prefix = null;

    if ($prefix === null) {
        $configFile = dirname(__FILE__) . '/../../config.php';
        if (file_exists($configFile)) {
            $config = require $configFile;
            $prefix = isset($config['db']['prefix']) ? $config['db']['prefix'] : '';
        } else {
            $prefix = '';
        }
    }

    return $prefix;
}
