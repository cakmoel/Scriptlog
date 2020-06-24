<?php
/**
 * get server load average 
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
    $load = array();

    $factor = 15780543;

    $threshold = number_cpus() * $factor . PHP_EOL;

    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {

        if(win_architecture() == 'x86') {

            $wmi = new COM("Winmgmts://");
            $server = $wmi->execquery("SELECT LoadPercentage FROM Win32_Processor");  
            $cpu_num = 0;
            $load_total = 0;

            foreach($server as $cpu) {
               $load_total += $cpu->loadpercentage;
               $cpu_num++;
               break;
            }

            $load[]= round($load_total/$cpu_num);

        }
       
    } else {

        $load = sys_getloadavg();
        
    }
     
    if ($load[0] >= $threshold) {
     
        header(APP_PROTOCOL.' 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 300');
        die("Server too busy. Please try again later.");

    }

}

/**
 * win_architecture
 * Get windows OS Architecture
 *
 * @see https://stackoverflow.com/questions/6303241/find-windows-32-or-64-bit-using-php
 * @return string
 * 
 */
function win_architecture()
{
 
    $wmi = new COM('winmgmts:{impersonationLevel=impersonate}//./root/cimv2');

    if (!is_object($wmi)) {
        throw new Exception('No access to WMI. Please enable DCOM in php.ini and allow the current user to access the WMI DCOM object.');
    }

    foreach($wmi->ExecQuery("SELECT Architecture FROM Win32_Processor") as $cpu) {
        # only need to check the first one (if there is more than one cpu at all)
        switch($cpu->Architecture) {
            case 0:
                return "x86";
            case 1:
                return "MIPS";
            case 2:
                return "Alpha";
            case 3:
                return "PowerPC";
            case 6:
                return "Itanium-based system";
            case 9:
                return "x64";

        }

    }
    
    return "Unknown";

}