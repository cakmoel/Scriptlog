<?php

/**
 * check_disabled_function
 *
 * @category functions
 * @author M.Noermoehammad
 * @param mixed $needle
 * @return bool
 * 
 */
function check_disabled_functions($needle)
{

    $disabled_functions = [
        "allow_url_fopen", "fsockopen", "pfsockopen", "getrusage", 
        "get_current_user", 
        "set_time_limit", "getmyuid", "getmypid", "dl", "leak", "listen", "chown", 
        "chgrp", "realpath", "link"
    ];

    return in_array($needle, $disabled_functions);
    
}