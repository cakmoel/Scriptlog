<?php
/**
 * Autoloading Classes
 *
 * @package Autoloader
 * @category Autoloader
 * @author Shay Anderson 1.12
 * @link http://www.shayanderson.com/php/autoloading-classes-in-php-with-autoloader-class.htm
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 * 
 */
final class Autoloader {
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
	public function __construct($class_dir) {
		$this->_class_dir = $class_dir;
	}

	/**
	 * Init
	 */
	private static function __init() {
		if(!self::$_is_init) {
			// flush existing autoloads
			spl_autoload_register(null, false);
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
	private static function _formatDir($dir = null, $use_base_dir = true) {
		$dir = trim($dir);

		// add base dir
		if($use_base_dir && self::$_base_dir && strpos($dir, self::$_base_dir) === false) {
			$dir = self::$_base_dir . $dir;
		}

		if(substr($dir, -1) !== '/') {
			$dir .= '/';
		}

		return $dir;
	}

	/**
	 * Class directory add (or multiple directories with array)
	 *
	 * @param mixed $class_dir (string|array)
	 */
	public static function addClassDir($class_dir) {
		// multiple dirs
		if(is_array($class_dir)) {
			if(count($class_dir) > 0) {
				foreach($class_dir as $v) {
					self::addClassDir($v);
				}
			}
		// add class dir
		} else {
			self::__init();
			$class_dir = self::_formatDir($class_dir);
			if(!in_array($class_dir, self::$_load_dirs)) {
				if(spl_autoload_register(array(new self($class_dir), 'autoload'))) {
					self::$_load_dirs[] = $class_dir;
				}
			}
		}
	}

	/**
	 * Autoload class
	 *
	 * @param string $class_name
	 * @return bool
	 */
	public function autoload($class_name) {
		$class_file = $this->_class_dir . $class_name . self::$_file_ext;
		if(!file_exists($class_file)) {
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
	public static function getLoadDirs() {
		return self::$_load_dirs;
	}

	/**
	 * Loaded files getter
	 *
	 * @return array
	 */
	public static function getLoadedFiles() {
		return self::$_loaded_files;
	}

	/**
	 * Base directory setter
	 *
	 * @param string $base_dir
	 */
	public static function setBaseDir($base_dir) {
		self::$_base_dir = self::_formatDir($base_dir, false);
	}

	/**
	 * Class file extension setter
	 *
	 * @param string $class_file_extension
	 */
	public static function setClassExtension($class_file_extension = '.php') {
		self::$_file_ext = $class_file_extension;
	}
}