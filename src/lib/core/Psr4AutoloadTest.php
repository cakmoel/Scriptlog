<?php
namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * PSR-4 Autoload verification class.
 *
 * This class verifies that the PSR-4 autoloading configuration
 * in composer.json is working correctly. It should be autoloaded
 * from lib/core/Psr4AutoloadTest.php via the Scriptlog\Core namespace.
 *
 * @category Core
 * @author   Scriptlog
 * @license  MIT License
 * @version  1.0
 * @since    1.0
 */
class Psr4AutoloadTest
{
    /**
     * Return a constant string to confirm autoloading succeeded.
     *
     * @return string
     */
    public static function hello(): string
    {
        return 'PSR-4 autoloading is working correctly';
    }

    /**
     * Return the fully qualified class name for verification.
     *
     * @return string
     */
    public static function fqcn(): string
    {
        return __CLASS__;
    }
}
