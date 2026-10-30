<?php

namespace Scriptlog\Core;

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * class LogError
 *
 * Error Handling and Logging
 *
 * @category  Core Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */

class LogError
{
    private static $logFilePath = APP_ROOT . APP_PUBLIC . '/log/';

    private static $httpResponseCode;

    public static function logPath()
    {
        return self::$logFilePath;
    }

    /**
     *
     * @param string $privilege
     *
     */
    public static function customErrorMessage($privilege = null)
    {

        if ($privilege === 'admin') {
            switch (self::getStatusCode()) {
                case 400:
                    echo MessageLog::contentMessage(self::$httpResponseCode, 'Bad Request', $privilege);

                    break;

                case 403:
                    echo MessageLog::contentMessage(self::$httpResponseCode, 'Forbidden', $privilege);

                    break;

                case 404:
                    echo MessageLog::contentMessage(self::$httpResponseCode, 'Not Found', $privilege);

                    break;

                case 405:
                    echo MessageLog::contentMessage(self::$httpResponseCode, 'Method Not Allowed', $privilege);

                    break;

                case 413:
                    echo MessageLog::contentMessage(self::$httpResponseCode, 'Payload Too Large', $privilege);

                    break;

                case 500:
                    echo MessageLog::contentMessage(self::$httpResponseCode, 'Internal Server Error', $privilege);

                    break;
            }
            return;
        }

        echo MessageLog::contentMessage(self::$httpResponseCode);
    }

    /**
     * Determine whether a PHP error type is fatal
     *
     * Only fatal errors must be promoted to an exception by the shutdown
     * handler. Warnings, notices and deprecated diagnostics are already
     * handled by errorHandler() and must never be re-raised as an
     * exception - doing so turns harmless conditions (for example an
     * unwritable cache file) into a 301 redirect when APP_DEVELOPMENT is
     * false.
     *
     * @param int $type PHP error type constant (E_*)
     * @return bool
     */
    public static function isFatalErrorType($type): bool
    {
        $fatalTypes = [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR,
        ];

        return in_array((int) $type, $fatalTypes, true);
    }

    /**
     * exceptionHandler
     *
     * @param object $e
     */
    public static function exceptionHandler($e)
    {

        self::newMessage($e);

        if (APP_DEVELOPMENT == true) {
            return;
        }

        self::customErrorMessage('admin');
    }

    /**
     *
     * errorHandler
     *
     * @param integer $code
     * @param string $description
     * @param string $file
     * @param string $line
     * @return void|bool
     *
     */
    public static function errorHandler($code, $description, $file = null, $line = null)
    {
        return MessageLog::messageError($code, $description, self::logPath() . 'error.log', $file, $line);
    }

    /**
     *
     * @static method newMessage
     * @param \Exception|\Throwable $exception
     */
    public static function newMessage($exception)
    {

        return MessageLog::messageException($exception, self::logPath() . 'exceptions.log');
    }

    public static function setStatusCode($statusCode): int
    {
        self::$httpResponseCode = $statusCode;
        return $statusCode;
    }

    private static function getStatusCode()
    {
        return self::$httpResponseCode;
    }
}
