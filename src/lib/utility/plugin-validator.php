<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Plugin Validator
 * 
 * Validates plugin structure and required files
 * 
 * @category Utility
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0.0
 * 
 */

/**
 * Required fields in plugin.ini
 */
define('PLUGIN_REQUIRED_FIELDS', [
    'plugin_name',
    'plugin_description',
    'plugin_level',
    'plugin_version',
    'plugin_author',
    'plugin_loader',
    'plugin_action'
]);

/**
 * Validate plugin directory structure
 * 
 * @param string $pluginDir Path to plugin directory
 * @return array ['valid' => bool, 'errors' => array, 'info' => array]
 */
function validate_plugin_structure($pluginDir)
{
    $errors = [];
    $info = [];
    
    $pluginDir = rtrim($pluginDir, '/\\');
    
    if (!is_dir($pluginDir)) {
        return [
            'valid' => false,
            'errors' => ['Plugin directory does not exist'],
            'info' => []
        ];
    }
    
    $iniFile = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.ini';
    
    if (!file_exists($iniFile)) {
        $errors[] = 'Missing required file: plugin.ini';
        
        return [
            'valid' => false,
            'errors' => $errors,
            'info' => []
        ];
    }
    
    $iniContent = parse_ini_file($iniFile);
    
    if ($iniContent === false) {
        $errors[] = 'Invalid plugin.ini format';
        
        return [
            'valid' => false,
            'errors' => $errors,
            'info' => []
        ];
    }
    
    foreach (PLUGIN_REQUIRED_FIELDS as $field) {
        if (!isset($iniContent[$field]) || empty(trim($iniContent[$field]))) {
            $errors[] = "Missing required field in plugin.ini: {$field}";
        }
    }
    
    if (!empty($errors)) {
        return [
            'valid' => false,
            'errors' => $errors,
            'info' => []
        ];
    }
    
    $info = [
        'plugin_name' => $iniContent['plugin_name'] ?? '',
        'plugin_description' => $iniContent['plugin_description'] ?? '',
        'plugin_level' => $iniContent['plugin_level'] ?? '',
        'plugin_version' => $iniContent['plugin_version'] ?? '',
        'plugin_author' => $iniContent['plugin_author'] ?? '',
        'plugin_loader' => $iniContent['plugin_loader'] ?? '',
        'plugin_action' => $iniContent['plugin_action'] ?? ''
    ];
    
    $phpFiles = glob($pluginDir . DIRECTORY_SEPARATOR . '*.php');
    
    if (empty($phpFiles)) {
        $errors[] = 'No PHP files found in plugin directory';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'info' => $info
    ];
}

/**
 * Validate plugin ZIP before installation
 * 
 * @param string $zipPath Path to uploaded ZIP file
 * @return array ['valid' => bool, 'errors' => array, 'plugin_name' => string]
 */
function validate_plugin_zip($zipPath)
{
    $errors = [];
    $pluginName = '';
    
    $zip = new ZipArchive();
    
    if ($zip->open($zipPath) !== true) {
        return [
            'valid' => false,
            'errors' => ['Cannot open ZIP file'],
            'plugin_name' => ''
        ];
    }
    
    $hasIniFile = false;
    $hasPhpFile = false;
    $iniContent = [];
    
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $fileName = $zip->getNameIndex($i);
        
        if (strtolower(basename($fileName)) === 'plugin.ini') {
            $hasIniFile = true;
            
            $iniData = $zip->getFromName($fileName);
            
            if ($iniData !== false) {
                $tempIni = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'plugin_temp.ini';
                file_put_contents($tempIni, $iniData);
                $iniContent = parse_ini_file($tempIni);
                unlink($tempIni);
                
                foreach (PLUGIN_REQUIRED_FIELDS as $field) {
                    if (!isset($iniContent[$field]) || empty(trim($iniContent[$field]))) {
                        $errors[] = "Missing required field in plugin.ini: {$field}";
                    }
                }
                
                $pluginName = $iniContent['plugin_name'] ?? '';
            }
        }
        
        if (pathinfo($fileName, PATHINFO_EXTENSION) === 'php') {
            $hasPhpFile = true;
        }
    }
    
    $zip->close();
    
    if (!$hasIniFile) {
        $errors[] = 'Missing required file: plugin.ini';
    }
    
    if (!$hasPhpFile) {
        $errors[] = 'No PHP files found in plugin package';
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'plugin_name' => $pluginName
    ];
}

/**
 * Get plugin info from directory
 * 
 * @param string $pluginDir Path to plugin directory
 * @return array|false
 */
function get_plugin_info($pluginDir)
{
    $pluginDir = rtrim($pluginDir, '/\\');
    $iniFile = $pluginDir . DIRECTORY_SEPARATOR . 'plugin.ini';
    
    if (!file_exists($iniFile)) {
        return false;
    }
    
    return parse_ini_file($iniFile);
}

/**
 * Check if plugin has SQL file
 * 
 * @param string $pluginDir Path to plugin directory
 * @return string|false
 */
function get_plugin_sql_file($pluginDir)
{
    $pluginDir = rtrim($pluginDir, '/\\');
    
    $sqlFiles = glob($pluginDir . DIRECTORY_SEPARATOR . '*.sql');
    
    return !empty($sqlFiles) ? $sqlFiles[0] : false;
}

/**
 * Check if plugin has functions file
 * 
 * @param string $pluginDir Path to plugin directory
 * @return string|false
 */
function get_plugin_functions_file($pluginDir)
{
    $pluginDir = rtrim($pluginDir, '/\\');
    $functionsFile = $pluginDir . DIRECTORY_SEPARATOR . 'functions.php';
    
    return file_exists($functionsFile) ? $functionsFile : false;
}
