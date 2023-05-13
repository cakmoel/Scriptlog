<?php
/**
 * create_directory function
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
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