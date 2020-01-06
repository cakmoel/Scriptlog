<?php
/**
 * linux compatible php server load function
 * 
 * @category Function
 * @see https://stackoverflow.com/questions/5588616/how-do-you-calculate-server-load-in-php
 * @see https://stackoverflow.com/questions/4705759/how-to-get-cpu-usage-and-ram-usage-without-exec
 * @see https://helloacm.com/how-to-respond-with-503-service-busy-to-requests-when-server-load-average-is-high/
 * @see https://www.php.net/manual/en/function.sys-getloadavg.php
 * @return void
 * 
 */

function get_server_load()
{
    $load=array();

    $factor = 1.5;

    $threshold = number_cpus() * $factor . PHP_EOL;

    if (stristr(PHP_OS, 'win')) {

        $wmi = new COM("Winmgmts://");
        $server = $wmi->execquery("SELECT LoadPercentage FROM Win32_Processor");  
        $cpu_num = 0;
        $load_total = 0;
        foreach($server as $cpu)
        {
            $cpu_num++;
            $load_total += $cpu->loadpercentage;
        }

        $load[]= round($load_total/$cpu_num);
        
    } else {

        $load = sys_getloadavg();
        
    }
     
    if ($load[0] > $threshold) {
     
        header(APP_PROTOCOL.' 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 300');
        die("Server too busy. Please try again later.");

    }

}