<?php
/**
 * write_log()
 * 
 * hack logging
 * 
 * @category function
 * @param string $where
 * @see https://css-tricks.com/serious-form-security/
 * @return void
 * 
 */
function write_log($ip, $where)
{
  
  $host = gethostbyaddr($ip);
  $date_attacked = date("d M Y");
  $time_attacked = date("H:i:s");
  $os = get_os();

$logging = <<<LOG
<< Start of Message >>\n
	There was a hacking attempt on your login form:
  Date of Attack: {$date_attacked}
  Time of Attack: {$time_attacked}
  IP-Adress: {$ip} 
  Operating System: {$os}
	Host of Attacker: {$host}
	Point of Attack: {$where}\n
<< End of Message >>\n
LOG;

  $logfile = __DIR__ . '/../../public/log/hacklog.log';

  if (is_readable($logfile)) {

    if ($handle = fopen($logfile, 'a')) {

        fputs($handle, $logging);
        fclose($handle);

    } else {

        $to = 'scriptlog@yandex.com';
        $subject = 'Hack Attempt';
        $header = 'From:'. sanitize_email(app_info()['site_email']);
        
        if (mail($to, $subject, $logging, $header)) {

            echo "Email sent to author of scriptlog";

        }
         
    }

  } else {

      scriptlog_error("Permission denied. Check your permission for writing on {$logfile} ");

  }
  
}