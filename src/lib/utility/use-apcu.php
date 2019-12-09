<?php
/**
 * Functions apcu_read_cache
 * 
 * @category Function
 * @param string $key
 * 
 */
function apcu_read_cache($key)
{
    $apcu = new APCU();

    return $apcu->readCache($key);

}

function apcu_write_cache()
{
    
}

function apcu_removes_cache()
{

}