<?php
/**
 * Write log function
 * hack logging
 * 
 * @param string $where
 * @see https://css-tricks.com/serious-form-security/
 * @return void
 * 
 */
function write_log($ip, $where)
{
  
  $host = gethostbyaddr($ip);
  $date_attacked = date("d M Y");

  $logging = <<<LOG
      \n
        << Start of Message >>
		There was a hacking attempt on your form. \n 
		Date of Attack: {$date_attacked}
		IP-Adress: {$ip} \n
		Host of Attacker: {$host}
		Point of Attack: {$where}
        << End of Message >>
LOG;

  $logfile = __DIR__ . '/../../public/log/hacklog.log';

  if ((file_exists($logging)) && (is_readable($logfile))) {

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