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

	function __construct(){}

	function sanitasi($str, $tipe)
	{
		switch($tipe){
			
		   default:
			
		   case'sql':
				$d = array ('-','/','\\',',','#',':',';','\'','"','[',']','{','}',')','(','|','`','~','!','%','$','^','&','*','=','?','+');
				$str = str_replace($d, '', $str);
				$str = stripcslashes($str);
				$str = htmlspecialchars($str);
				$str = preg_replace('/[^A-Za-z0-9]/','',$str);
				return intval($str);
				break;
			
			case'xss':
				$d = array ('\\','#',';','\'','"','[',']','{','}',')','(','|','`','~','!','%','$','^','&','*','=','?','+');
				$str = str_replace($d, '', $str);
				$str = stripcslashes($str);
				$str = htmlspecialchars($str);
				return $str;
				break;
		}
	}

	private static function extension($path)
	{
		$file = pathinfo($path);
		if(file_exists($file['dirname'].'/'.$file['basename'])){
			return $file['basename'];
		}
	}
}