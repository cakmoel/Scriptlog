<?php
/**
 * get_server_load
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

        if ((win_architecture() == 'x86') && (class_exists('COM')))  {

            // Win CPU
            $wmi = new COM('WinMgmts:\\\\.');
            $server = $wmi->InstancesOf('Win32_Processor');
            $server += $wmi->execquery("SELECT LoadPercentage FROM Win32_Processor");  
            $cpu_num = 0;
            $load_total = 0;

            foreach ($server as $cpu) {
               $load_total += $cpu->loadpercentage;
               $cpu_num++;
               break;
            }

            $load[]= round($load_total/$cpu_num);

        } else {

          throw new InvalidArgumentException("class COM does not exists");

        }
       
    } else {

       $load = sys_getloadavg();
        
    }
     
    if ( isset($load[0]) && $load[0] >= $threshold ) {
     
      header(APP_PROTOCOL.' 503 Service Temporarily Unavailable', true, 503);
      header('Status: 503 Service Temporarily Unavailable');
      header('Retry-After: 300');
      die("Server too busy. Please try again later.");
  
    }

}

/**
 * win_architecture
 * 
 * Get windows OS architecture
 *
 * @see https://stackoverflow.com/questions/6303241/find-windows-32-or-64-bit-using-php
 * @return string
 * 
 */
function win_architecture()
{
 
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ) {

      $wmi = ( class_exists('COM') ? new COM('winmgmts:{impersonationLevel=impersonate}//./root/cimv2') : null );

      if (!is_object($wmi)) {

        throw new InvalidArgumentException('No access to WMI. Please enable DCOM in php.ini and allow the current user to access the WMI DCOM object.');

      }

      foreach ($wmi->ExecQuery("SELECT Architecture FROM Win32_Processor") as $cpu) {
         # only need to check the first one (if there is more than one cpu at all)
         switch ($cpu->Architecture) {
           case 0:
             return "x86";
             break;
           case 1:
             return "MIPS";
             break;
           case 2:
             return "Alpha";
             break;
           case 3:
             return "PowerPC";
             break;
           case 6:
             return "Itanium-based system";
             break;
           case 9:
            return "x64";
            break;
           default:
            scriptlog_error("Unknown architecture");
            break;

         }

      }

      return "Unknown";

    }
    
}