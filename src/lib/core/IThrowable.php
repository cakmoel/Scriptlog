<?php
/**
 * IThrowable
 * 
 * @category Core Class
 * @see https://www.php.net/manual/en/language.errors.php7.php
 * @see https://www.php.net/manual/en/migration70.incompatible.php
 * @see https://www.php.net/manual/en/class.error.php
 * @see https://www.php.net/manual/en/class.throwable.php
 * @see https://netgen.io/blog/modern-error-handling-in-php
 * @see https://trowski.com/2015/06/24/throwable-exceptions-and-errors-in-php7/
 * @see https://www.php.net/manual/en/language.exceptions.php
 * @see https://www.php.net/manual/en/language.exceptions.extending.php
 * @see https://stackoverflow.com/questions/628408/custom-exception-messages-best-practices
 * @license MIT
 * @version 1.0
 * 
 */
interface IThrowable
{

public function getMessage();

public function getCode();

public function getFile();

public function getLine();

public function getTrace();

public function getTraceAsString();

public function getPrevious();

public function __toString();

}