<?php
/**
 * create_directory function
 *
 * @category Function
 * @param string $path
 * @return void
 * 
 */
function create_directory($path)
{

if (!(is_readable($path) || file_exists($path))) {

    mkdir($path, 0755, true);

}

}