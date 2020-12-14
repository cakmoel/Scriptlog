<?php
/**
 * whoops_error()
 * 
 * @category function
 * @uses \Whoops\Run
 * @uses \Whoops\Handler\PrettyPageHandler
 * @return void
 * 
 */
function whoops_error()
{

if (APP_DEVELOPMENT === true) {

$whoops = new \Whoops\Run();

$errorPage = new \Whoops\Handler\PrettyPageHandler();
$errorPage->setPageTitle(APP_TITLE . " broken!");
$errorPage->addDataTable(APP_TITLE, array(
    "version" => APP_VERSION,
    "codename" => APP_CODENAME,
    "hostname" => APP_HOSTNAME
));

$whoops->pushHandler($errorPage);
$whoops->register();
   
} else {

set_exception_handler('LogError::exceptionHandler');
set_error_handler('LogError::errorHandler');

}

}