<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * sanitize.class.php
 * Sanitizing input and output
 * 
 * @category   Core Class
 * @author     Khairu a.k.a wenkhairu
 * @author     M.Noermoehammad
 * @copyright  wenkhairu|M.Noermoehammad
 * @license    MIT
 *
 */
class Sanitize
{

	public function sanitasi($str, $tipe)
	{

		switch ($tipe) {

			default:

			case 'sql':

				$d = array('-', '/', '\\', ',', '#', ':', ';', '\'', '"', '[', ']', '{', '}', ')', '(', '|', '`', '~', '!', '%', '$', '^', '&', '*', '=', '?', '+');
				$str = str_replace($d, '', $str);
				$str = stripcslashes($str);
				$str = htmlspecialchars($str);
				$str = preg_replace('/[^A-Za-z0-9]/', '', $str);
				return intval($str);
				break;

			case 'xss':

				$d = array('\\', '#', ';', '\'', '"', '[', ']', '{', '}', ')', '(', '|', '`', '~', '!', '%', '$', '^', '&', '*', '=', '?', '+');
				$str = str_replace($d, '', $str);
				$str = stripcslashes($str);
				$str = htmlspecialchars($str);
				$str = preg_replace('/[\W]/', '', $str);
				$str = prevent_injection($str);
				return $str;
				break;
		}
	}

	/**
	 * mildSanitizer
	 *
	 * @param string $str
	 * @return string
	 * 
	 */
	public static function mildSanitizer($str)
	{
		return simple_remove_xss($str);
	}

	/**
	 * severeSanitizer
	 *
	 * @param string $str
	 * @return string
	 * 
	 */
	public static function severeSanitizer($str)
	{
		return remove_xss($str);
	}

	/**
	 * strictSanitizer
	 *
	 * @param string $str
	 * @return string
	 * 
	 */
	public static function strictSanitizer($str)
	{
		return purify_dirty_html($str);
	}
}
