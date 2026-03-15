<?php
/**
 * number of cpu core function
 * detecting number of processor cores
 *
 * @copyright 2011 Erin Millard
 * @see https://stackoverflow.com/questions/36970270/how-to-calculate-number-of-processor-cores-in-php-script-linux
 * @see https://gist.github.com/ezzatron/1321581
 * @see https://helloacm.com/how-to-get-number-of-cpu-cores-using-php-function-platform-independent/
 * @return null|bool|int|float|string
 * 
 */
function number_cpus()
{
  
$cpu_core = 1;

if (is_file('/proc/cpuinfo')) {

   $cpuinfo = file_get_contents('/proc/cpuinfo');
   
   preg_match_all('/^processor/m', $cpuinfo, $matches);

   $cpu_core = count($matches[0]);

} elseif ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) {

   $process = function_exists('popen') ? @popen('wmic cpu get NumberOfCores', 'rb') : "";
   
   if (false !== $process) {

     fgets($process);
     
     $cpu_core = intval(fgets($process));
     
     pclose($process);

   }

} else {

   $process = function_exists('popen') ? @popen('sysctl -a', 'rb') : "";

   if (false !== $process) {

       $output = stream_get_contents($process);

       preg_match('/hw.ncpu: (\d+)/', $output, $matches);

       if ($matches) {

           $cpu_core = intval($matches[1][0]);
       }

       if (is_resource($process)) {

        pclose($process);
        
       }
       
   }

}

return $cpu_core;

}