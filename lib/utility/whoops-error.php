<?php

defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * whoops_error()
 *
 * @category function
 * @author M.Noermoehammad
 * @version 1.0
 * @license MIT
 * @uses \Whoops\Run
 * @uses \Whoops\Handler\PrettyPageHandler
 * @since Since Release 1.0
 * @return void
 *
 */
function whoops_error()
{

    // Never install global error handlers under test mode: PHPUnit manages
    // its own error-to-exception conversion and a persistent handler would
    // leak into every subsequent test class.
    if (defined('SCRIPTLOG_TEST_MODE') && SCRIPTLOG_TEST_MODE) {
        return;
    }

    /**
     * @psalm-suppress TypeDoesNotContainType
     */
    if (defined('APP_DEVELOPMENT') && APP_DEVELOPMENT) {
        // Never expose secrets via Whoops in any environment
        $blacklisted = array(
            'DB_PASS',
            'DB_PASSWORD',
            'DB_USER',
            'DB_USERNAME',
            'DB_HOST',
            'DB_NAME',
            'DB_DATABASE',
            'DB_PREFIX',
            'APP_KEY',
            'DEFUSE_KEY_PATH',
            'MAIL_PASSWORD',
            'SMTP_PASSWORD',
            'MAILER_PASSWORD',
            'REDIS_PASSWORD',
            'SESSION_SECRET'
        );

        $whoops = new \Whoops\Run();

        $errorPage = new \Whoops\Handler\PrettyPageHandler();
        $errorPage->setPageTitle(APP_TITLE . " broken!");
        $errorPage->addDataTable(APP_TITLE, array(
            "version" => APP_VERSION,
            "codename" => APP_CODENAME,
            "hostname" => function_exists('app_url') ? app_url() : ''
        ));

        /**
         * @psalm-suppress RedundantCondition
         */
        if (method_exists($errorPage, 'blacklist')) { // @phpstan-ignore method.alreadyKnown
            foreach ($blacklisted as $secretKey) {
                $errorPage->blacklist('_SERVER', $secretKey);
                $errorPage->blacklist('_ENV', $secretKey);
                $errorPage->blacklist('_GET', $secretKey);
                $errorPage->blacklist('_POST', $secretKey);
                $errorPage->blacklist('_COOKIE', $secretKey);
            }
            // Also blacklist common patterns
            $errorPage->blacklist('_SERVER', 'PASS');
            $errorPage->blacklist('_SERVER', 'PASSWORD');
            $errorPage->blacklist('_SERVER', 'SECRET');
            $errorPage->blacklist('_SERVER', 'KEY');
            $errorPage->blacklist('_SERVER', 'TOKEN');
        }

        $whoops->pushHandler($errorPage);
        $whoops->register();
    } else {
        set_exception_handler('LogError::exceptionHandler');

        set_error_handler('LogError::errorHandler');

        register_shutdown_function(static function () {
            $error = error_get_last();

            if ($error === null) {
                return;
            }

            // Only genuine fatal errors need this safety net. Warnings,
            // notices and deprecated diagnostics are already routed through
            // set_error_handler('LogError::errorHandler') above, and a
            // suppressed (@) diagnostic - for example an unwritable cache
            // file - must never turn an otherwise successful request into
            // the 301 redirect that MessageLog::messageException() performs
            // when APP_DEVELOPMENT is false. Promoting those here produced
            // bogus "301 Moved Permanently" responses for every API call
            // whose rate-limit counter file could not be written.
            if (!class_exists('LogError') || !LogError::isFatalErrorType($error['type'])) {
                return;
            }

            $e = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);

            LogError::exceptionHandler($e);
        });
    }
}
