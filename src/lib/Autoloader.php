<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Autoloading Classes
 *
 * @package Autoloader
 * @category Autoloader
 * @author Shay Anderson 1.12
 * @link http://www.shayanderson.com/php/autoloading-classes-in-php-with-autoloader-class.htm
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * @example Here is the example directory structure:
 * 
 * /var/www/example
 * |-- index.php
 * |-- lib
 * |      `--Autoloader.php
 * |      `--controller
 * |            `--IndexController.php
 * |      `--model
 * |            `--Document
 * |                  `--Document.php
 * |                  `--TaskDocument.php
 * |            `--User
 * |                  `--User.php description
 * 
 * // include Autoloader class
 * require './lib/Autoloader.php';
 * // set the base directory where your project is located
 * Autoloader::setBaseDir('/var/www/example');
 * // add class directories (without the base directory)
 * Autoloader::addClassDir(array(
 *    'lib/controller',
 *     'lib/model/Document',
 *     'lib/model/User'
 * ));
 * 
 * ############can be useful when debugging ##################:
 * // set directories that Autoloader
 * // will load classes from
 * $load_dirs = Autoloader::getLoadDirs();
 * // print array
 * print_r($load_dirs); 
 * 
 * // set class files that Autoloader
 * // has successfully loaded
 * $loaded_files = Autoloader::getLoadedFiles();
 * // print array
 * print_r($loaded_files);
 *  
 */
final class Autoloader 
{
	/**
	 * Base directory
	 *
	 * @var string
	 */
	private static $_base_dir;

	/**
	 * Class directory
	 *
	 * @var string
	 */
	private $_class_dir;

	/**
	 * File extension
	 *
	 * @var string
	 */
	private static $_file_ext = '.php';

	/**
	 * Init flag
	 *
	 * @var bool
	 */
	private static $_is_init = false;

	/**
	 * Load class directories
	 *
	 * @var array
	 */
	private static $_load_dirs = array();

	/**
	 * Loaded files
	 *
	 * @var array
	 */
	private static $_loaded_files = array();

	/**
	 * Init class directory
	 *
	 * @param string $class_dir
	 */
	public function __construct($class_dir) 
	{
		$this->_class_dir = $class_dir;
	}

	/**
	 * Init
	 */
	private static function __init() {
		if (!self::$_is_init) {
			// flush existing autoloads
			(version_compare(PHP_VERSION, '8.0.0', '<=')) ? spl_autoload_register(null, false) : spl_autoload_register(null);
			self::$_is_init = true;
		}
	}

	/**
	 * Format directory name, ex: dir => /base_dir/dir/
	 *
	 * @param string $dir
	 * @param bool $use_base_dir
	 * @return string
	 */
	private static function _formatDir($dir, $use_base_dir = true) 
	{
		$dir = trim($dir);

		// add base dir
		if ($use_base_dir && self::$_base_dir && strpos($dir, self::$_base_dir) === false) {
			$dir = self::$_base_dir . $dir;
		}

		if (substr($dir, -1) !== '/') {
			$dir .= '/';
		}

		return $dir;
	}

	/**
	 * Class directory add (or multiple directories with array)
	 *
	 * @param mixed $class_dir (string|array)
	 */
	public static function addClassDir($class_dir) 
	{
		// multiple dirs
		if (is_array($class_dir)) {
			if (count($class_dir) > 0) {
				foreach ($class_dir as $v) {
					self::addClassDir($v);
				}
			}
		// add class dir
		} else {
			self::__init();
			$class_dir = self::_formatDir($class_dir);
			if ((!in_array($class_dir, self::$_load_dirs)) && (spl_autoload_register(array(new self($class_dir), 'autoload') ) )) {
				self::$_load_dirs[] = $class_dir;
			}
		}
	}

	/**
	 * Autoload class
	 *
	 * @param string $class_name
	 * @return bool
	 */
	public function autoload($class_name) 
	{
		$class_file = $this->_class_dir . $class_name . self::$_file_ext;
		if (!file_exists($class_file)) {
			return false;
		}
		include $class_file;
		self::$_loaded_files[] = $class_file;
		return true;
	}

	/**
	 * Load directories getter
	 *
	 * @return array
	 */
	public static function getLoadDirs() 
	{
		return self::$_load_dirs;
	}

	/**
	 * Loaded files getter
	 *
	 * @return array
	 */
	public static function getLoadedFiles() 
	{
		return self::$_loaded_files;
	}

	/**
	 * Base directory setter
	 *
	 * @param string $base_dir
	 */
	public static function setBaseDir($base_dir) 
	{
		self::$_base_dir = self::_formatDir($base_dir, false);
	}

	/**
	 * Class file extension setter
	 *
	 * @param string $class_file_extension
	 */
	public static function setClassExtension($class_file_extension = '.php') 
	{
		self::$_file_ext = $class_file_extension;
	}
}