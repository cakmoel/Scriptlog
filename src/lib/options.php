<?php
/**
 * options.php
 * 
 * @category File options.php will be place to Sets the value of a configuration option
 * @author M.Noermoehammmad
 * @license MIT
 * @version 1.0
 * 
 */
#ini_set("session.cookie_secure", 1);  
#ini_set("session.cookie_lifetime", 604800);  
ini_set("session.cookie_httponly", 1);
#ini_set("session.use_cookies", 1);
ini_set("session.use_only_cookies", 1);
#ini_set("session.use_strict_mode", 1);
#ini_set("session.use_trans_sid", 0);
ini_set('session.save_handler', 'files');
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 1440);
ini_set('session.gc_probability', 1);

#header("Permissions-Policy: interest-cohort=()"); // Opt out of FLoC