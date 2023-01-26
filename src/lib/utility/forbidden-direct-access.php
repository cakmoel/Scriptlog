<?php
/**
 * forbidden_direct_access
 *
 * @author Name <email@email.com>
 * @param string $file
 * 
 */
function forbidden_direct_access($file)
{
    $self = getcwd()."/".trim(escape_html($_SERVER["PHP_SELF"]), "/");
    (substr_compare($file, $self, -strlen($self)) != 0) or die('Restricted access');
}