<?php

namespace Scriptlog\Core;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class MedooInit
 *
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 *
 */

use Medoo\Medoo;

class MedooInit
{
    protected static $database;

    public static function connect(array $options)
    {
        self::$database = new Medoo($options);
        return self::$database;
    }
}
