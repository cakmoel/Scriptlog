<?php 
/**
 * sanitize.class.php
 * Sanitizing  input and output
 * 
 * @category   Core Class
 * @author     Khairu a.k.a wenkhairu
 * @copyright  wenkhairu
 *
 */
class Sanitize 
{

 public function __construct(){}

 public function sanitasi($str, $tipe)
 {
		switch($tipe){
			
		   default:
			
		   case'sql':

				$d = array('-','/','\\',',','#',':',';','\'','"','[',']','{','}',')','(','|','`','~','!','%','$','^','&','*','=','?','+');
				$str = str_replace($d, '', $str);
				$str = stripcslashes($str);
				$str = htmlspecialchars($str);
				$str = preg_replace('/[^A-Za-z0-9]/','',$str);
				return intval($str);
				break;
			
			case'xss':
				
				$d = array('\\','#',';','\'','"','[',']','{','}',')','(','|','`','~','!','%','$','^','&','*','=','?','+');
				$str = str_replace($d, '', $str);
				$str = stripcslashes($str);
				$str = htmlspecialchars($str);
				$str = preg_replace('/[\W]/','', $str);
				$str = remove_xss($str);
				return $str;
				break;
		}
	}

 public static function mildSanitizer($str)
 {
   return simple_remove_xss($str);
 }

 public static function severeSanitizer($str)
 {
   return remove_xss($str);
 }

 public static function strictSanitizer($str)
 {
   return purify_dirty_html($str);
 }

 private static function extension($path)
 {
	 $file = pathinfo($path);
	 if(file_exists($file['dirname'].'/'.$file['basename'])){
		 return $file['basename'];
	 }

 }

}