<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Autoloader Class
 *
 * Supports both namespace-based (PSR-4) and directory-based class loading.
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
     * File extension
     *
     * @var string
     */
    private static $_file_ext = '.php';

    /**
     * Load class directories
     *
     * @var array
     */
    private static $_load_dirs = [];

    /**
     * Loaded files
     *
     * @var array
     */
    private static $_loaded_files = [];

    /**
     * Namespace prefix mappings
     *
     * @var array
     */
    private static $_namespace_mappings = [];

    /**
     * Initialize the autoloader
     */
    private static function init()
    {
        static $initialized = false;
        if (!$initialized) {
            spl_autoload_register([__CLASS__, 'loadClass']);
            $initialized = true;
        }
    }

    /**
     * Format directory name
     *
     * @param string $dir
     * @param bool $use_base_dir
     * @return string
     */
    private static function _formatDir($dir, $use_base_dir = true)
    {
        $dir = trim($dir);

        // Add base directory if required
        if ($use_base_dir && self::$_base_dir && strpos($dir, self::$_base_dir) === false) {
            $dir = self::$_base_dir . $dir;
        }

        // Ensure the directory ends with a slash
        if (substr($dir, -1) !== '/') {
            $dir .= '/';
        }

        return $dir;
    }

    /**
     * Add a class directory (or multiple directories)
     *
     * @param mixed $class_dir (string|array)
     */
    public static function addClassDir($class_dir)
    {
        if (is_array($class_dir)) {
            foreach ($class_dir as $dir) {
                self::addClassDir($dir);
            }
        } else {
            self::init();
            $formatted_dir = self::_formatDir($class_dir);
            if (!in_array($formatted_dir, self::$_load_dirs)) {
                self::$_load_dirs[] = $formatted_dir;
            }
        }
    }

    /**
     * Add a namespace prefix mapping
     *
     * @param string $prefix The namespace prefix
     * @param string $base_dir The base directory for the namespace
     */
    public static function addNamespace($prefix, $base_dir)
    {
        self::init();
        $prefix = trim($prefix, '\\') . '\\';
        $base_dir = self::_formatDir($base_dir);
        self::$_namespace_mappings[$prefix] = $base_dir;
    }

    /**
     * Autoload a class
     *
     * @param string $class_name
     * @return bool
     */
    public static function loadClass($class_name)
    {
        // Try to load the class using namespace mapping
        foreach (self::$_namespace_mappings as $prefix => $base_dir) {
            if (strpos($class_name, $prefix) === 0) {
                $relative_class = substr($class_name, strlen($prefix));
                $file = $base_dir . str_replace('\\', '/', $relative_class) . self::$_file_ext;

                if (file_exists($file)) {
                    include $file;
                    self::$_loaded_files[] = $file;
                    return true;
                }
            }
        }

        // Try to load the class using directory-based lookup
        foreach (self::$_load_dirs as $dir) {
            $file = $dir . $class_name . self::$_file_ext;
            if (file_exists($file)) {
                include $file;
                self::$_loaded_files[] = $file;
                return true;
            }
        }

        return false;
    }

    /**
     * Set the base directory
     *
     * @param string $base_dir
     */
    public static function setBaseDir($base_dir)
    {
        self::$_base_dir = self::_formatDir($base_dir, false);
    }

    /**
     * Set the class file extension
     *
     * @param string $class_file_extension
     */
    public static function setClassExtension($class_file_extension = '.php')
    {
        self::$_file_ext = $class_file_extension;
    }

    /**
     * Get the list of loaded files
     *
     * @return array
     */
    public static function getLoadedFiles()
    {
        return self::$_loaded_files;
    }

    /**
     * Get the list of load directories
     *
     * @return array
     */
    public static function getLoadDirs()
    {
        return self::$_load_dirs;
    }

    /**
     * Get the list of namespace mappings
     *
     * @return array
     */
    public static function getNamespaceMappings()
    {
        return self::$_namespace_mappings;
    }
}
