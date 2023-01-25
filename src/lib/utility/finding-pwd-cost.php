<?php
/**
 * finding_pwd_cost()
 * 
 * This code will benchmark your server to determine how high of a cost you can
 * afford. You want to set the highest cost that you can without slowing down
 * you server too much. 8-10 is a good baseline, and more is good if your servers
 * are fast enough. The code below aims for ≤ 50 milliseconds stretching time,
 * which is a good baseline for systems handling interactive logins.
 *
 * @category function
 * @param float $target -- stretching time
 * @param int $cost 8-10 is a good baseline
 * @see https://www.php.net/manual/en/function.password-hash.php
 * @return numeric
 * 
 */
function finding_pwd_cost($target, $cost)
{

do {

  $cost++;
  $start = microtime(true);
  password_hash("scriptlog", PASSWORD_BCRYPT, ["cost" => $cost]);
  $end = microtime(true);

} while (($end - $start) < $target);

 return $cost;

}