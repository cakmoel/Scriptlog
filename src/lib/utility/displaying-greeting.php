<?php
/**
 * displaying_greeting
 *
 * @category functions
 * @author M.Noermoehammad
 * @return string
 * 
 */
function displaying_greeting()
{
 
 $timezoneid = function_exists('timezone_identifier') ? timezone_identifier() : "";
 date_default_timezone_set($timezoneid);

 $h = date('G');

 if ($h >= 5 && $h <= 11) {

   return "<strong>Good morning</strong>";

 } elseif ($h >= 12 && $h <= 15) {

   return "<strong>Good afternoon</strong>";

 } else {
   return "<strong>Good evening</strong>";
 }

}