<?php
/**
 * PHProject - Display PHP Project Statistics
 *
 * @package PHProject
 * @version 0.0.r4 Beta
 * @copyright 2011 Shay Anderson
 * @license http://www.gnu.org/licenses/gpl.html GPL License
 * @author Shay Anderson
 * @link http://www.shayanderson.com/
 *
 * @TODO test unreadable files
 */

/**
 * Basic config settings
 */
ini_set('xdebug.max_nesting_level', -1);

// base project directory, can make dynamic with: dirname(__FILE__)
$config["dir_base"] = dirname(__FILE__). '/';

// directories to ignore
$config["dir_ignore"] = array("tests", "install", "admin", "public");

// project title/name
$config["project_title"] = "Scriptlog Project";


/**
 * Advanced config settings
 */

// known files types
$config["file_types"] = array(
	".htaccess" => array("htaccess"),
	"Bash script" => array("sh"),
	"Cascading Style Sheet (css)" => array("css"),
	"Compressed file (rar)" => array("rar"),
	"Compressed file (zip)" => array("zip"),
	"Configuration file (ini)" => array("ini"),
	"Data file (dat)" => array("dat"),
	"Data file (db)" => array("db"),
	"Flash video" => array("flv"),
	"Flash object (swf)" => array("swf"),
	"GIF image" => array("gif"),
	"HTML" => array("html", "htm", "shtml"),
	"Icon" => array("ico"),
	"JPEG image" => array("jpg", "jpeg"),
	"JavaScript" => array("js"),
	"Log file" => array("log"),
	"PHP" => array("php", "php5", "php4"),
	"PNG image" => array("png"),
	"Template file" => array("tpl"),
	"Text file" => array("txt"),
	"TIFF image" => array("tiff"),
	"TrueType font" => array("ttf")
);

// number of recently modified files displayed (zero for none)
$config["file_recent"] = 20;


/*************************************************************
 * Core code - do not edit
 *************************************************************/
set_time_limit(0);$PHProject = new PHProject($config);$PHProject->display();final class PHProject {const FILE_TYPE_UNKNOWN = "UNKNOWN";private $_config;private $_dirs = array();private $_file_types = array();private $_files = array();private $_files_recent = array();private $_files_unreadable = 0;private $_is_done_reading = false;private $_part_ids = array();public function __construct($config = array()) {$this->_config = $config;$this->_setFileTypes();$this->_setParts();}private function _addFileRecent(PHProjectPart $part) {if((int)$this->getConfig("file_recent")) {static $recent_files = array();static $file_count = 0;if($part->getDatetime() && !in_array($part->getPath() . $part->getName(), $recent_files)) {if(count($this->_files_recent) > (int)$this->getConfig("file_recent")) {foreach($this->_files_recent as $k => $v) {if($part->getDatetime() > $v["datetime"] && !in_array($part->getPath() . $part->getName(), $recent_files)) {unset($this->_files_recent[$k]);$this->_files_recent[$part->getDatetime() . "-" . $file_count] = array("file" => str_replace($this->getConfig("dir_base"), null, $part->getPath()) . $part->getName(),"datetime" => $part->getDatetime());$recent_files[] = $part->getPath() . $part->getName();}}} else {$this->_files_recent[$part->getDatetime() . "-" . $file_count] = array("file" => str_replace($this->getConfig("dir_base"), null, $part->getPath()) . $part->getName(),"datetime" => $part->getDatetime());$recent_files[] = $part->getPath() . $part->getName();}}$file_count++;}}private function _addPart(PHProjectPart $part) {if(in_array($part->getId(), $this->_part_ids)) {return false;}$added = false;if($part->isDir()) {$this->_dirs[] = $part;$added = true;} else {$this->_files[] = $part;$this->_addFileRecent($part);$added = true;}$this->_part_ids[] = $part->getId();return $added;}private function _formatDatetime($datetime = null) {return substr($datetime, 4, 2) . "/" . substr($datetime, 6, 2) . "/" . substr($datetime, 0, 4). " &nbsp;" . substr($datetime, 8, 2) . ":" . substr($datetime, 10, 2) . ":" . substr($datetime, 12, 2);}private function _formatFileSize($size = 0) {$size = (float)$size;$label = "KB";if($size > 1000) {$size = (float)$size / 1024;$label = "MB";}return number_format($size, 2) . " {$label}";}private function _formatNum($num = 0) {return number_format((int)$num, 0);}private function _getFileExt($filename = null) {return strtolower(substr($filename, (strrpos($filename, ".") + 1), strlen($filename)));}private function _getFileLines($filename = null) {if(!file_exists($filename)) {return array();}if(!is_readable($filename)) {$this->_files_unreadable++;return array();}return file($filename);}private function _getFileLinesPHP($filename = null) {$code_lines = array("code" => 0, "blank" => 0, "comment" => 0);$lines = $this->_getFileLines($filename);if(count($lines)) {$comment_block = false;foreach($lines as $line) {$line = trim($line);if($comment_block) {$code_lines["comment"]++;if(strpos($line, "*/") !== false) {$comment_block = false;}} elseif(!$line) {$code_lines["blank"]++;} elseif(substr($line, 0, 2) == "//" || substr($line, 0, 2) == "/*" || substr($line, 0, 1) == "*"|| substr($line, 0, 2) == "*/" || substr($line, 0, 1) == "#") {$code_lines["comment"]++;if(strpos($line, "/*") !== false && strpos($line, "*/") === false) {$comment_block = true;}} else {$code_lines["code"]++;if(strpos($line, "/*") !== false) {$comment_block = true;}}}}return $code_lines;}private function _getFileType($filename = null) {$ext = $this->_getFileExt($filename);if(!$ext) {return self::FILE_TYPE_UNKNOWN;}if(array_key_exists($ext, $this->_file_types) && $this->_file_types[$ext]) {return $this->_file_types[$ext];}return strtoupper($ext);}private function _getParts($dir = null) {if(!$dir) {return;}$parts = array();$dh = opendir($dir);while($part = readdir($dh)) {if($part == "." || $part == ".." || is_array($this->getConfig("dir_ignore"))&& in_array($part, $this->getConfig("dir_ignore"))) {continue;}$parts[] = new PHProjectPart($part, $dir, $this->_getFileType($part));}closedir($dh);return $parts;}private function _readDir(PHProjectPart $dir) {if($this->_is_done_reading) {return;}if(!$dir->isDir()) {return;}if(!$dir->isRead()) {$parts = $this->_getParts($dir->getId());$dir->setIsRead(true);if(count($parts)) {foreach($parts as $part) {$this->_addPart($part);}}}unset($dir);foreach($this->_dirs as $dir) {if(!$dir->isRead()) {$this->_readDir($dir);}}$this->_is_done_reading = true;}private function _setFileTypes() {foreach($this->getConfig("file_types") as $type => $exts) {if(is_array($exts) && count($exts)) {foreach($exts as $ext) {if($ext) {$this->_file_types[strtolower($ext)] = $type;}}}}}private function _setParts() {if(!$this->getConfig("dir_base")) {throw new PHProjectException("Invalid dir_base");}if(!is_readable($this->getConfig("dir_base"))) {throw new PHProjectException("Failed to read base_dir");}$base_parts = $this->_getParts($this->getConfig("dir_base"));if(!is_array($base_parts) || !count($base_parts)) {throw new PHProjectException("Invalid base parts");}foreach($base_parts as $part) {$this->_addPart($part);}foreach($this->_dirs as $dir) {$this->_readDir($dir);}}public function display() {$title = "PHProject Overview" . ( $this->getConfig("project_title")? ": " . $this->getConfig("project_title") : null );$html = "<html><head><title>{$title}</title><style type=\"text/css\">html { font-family:arial; }html, body { margin:0 auto; }body { padding:20px 40px 20px 40px; color:#365f91; font-size:13px; }a { color:#0066cc; }#container { margin:0 auto; width:600px; }h1 { margin:0 0 16px 0; padding:0 0 4px 0; font-size:26px; color:#365f91; border-bottom:1px solid #c8e1ff; }h2 { margin:12px 0 6px 4px; padding:0; font-size:20px; color:#4f81bd; }table { width:100%; background:#fff799; }table tr th { font-size:11px; color:#4f81bd; border-bottom:1px solid #f6e900; }table tr th,table tr td { padding:3px 10px 3px 10px; }table tr td { background:#fffef2; border-bottom:1px solid #f6e900; }table tr td.label { background:#fffcd3; font-weight:bold; width:180px; }#container #footer { margin-top:20px; padding:10px 0 10px 0; border-top:1px solid #c8e1ff; font-size:12px; text-align:center; }</style></head><body><div id=\"container\"><h1>{$title}</h1>";$lines_of_code = array();foreach($this->_files as $file) {switch($file->getType()) {case "PHP":if(!array_key_exists("PHP", $lines_of_code)) {$lines_of_code["PHP"] = array("code" => 0, "blank" => 0, "comment" => 0);}$get_lines = $this->_getFileLinesPHP($file->getId());$lines_of_code["PHP"]["code"] += $get_lines["code"];$lines_of_code["PHP"]["blank"] += $get_lines["blank"];$lines_of_code["PHP"]["comment"] += $get_lines["comment"];break;}}unset($file);$total_file_size = 0;foreach($this->_files as $file) {$total_file_size += $file->getSize();}unset($file);$html .= "<h2>File System Summary</h2><table><tr><td class=\"label\">Project Directory</td><td>{$this->getConfig("dir_base")}</td></tr><tr><td class=\"label\">Directories</td><td>" . $this->_formatNum(count($this->_dirs)) . "</td></tr><tr><td class=\"label\">Files</td><td>" .  $this->_formatNum(count($this->_files)) . "</td></tr><tr><td class=\"label\">File Size</td><td>{$this->_formatFileSize($total_file_size)}</td></tr>". ( $this->_files_unreadable > 0? "<tr><td>Unreadable Files</td><td>{$this->_files_unreadable}</td></tr>" : null ) . "</table>";$html .= "<h2>Project Files</h2>";$file_types = array();foreach($this->_files as $file) {if(!array_key_exists($file->getType(), $file_types)) {$file_types[$file->getType()]["count"] = 0;$file_types[$file->getType()]["size"] = 0;}$file_types[$file->getType()]["count"] += 1;$file_types[$file->getType()]["size"] += (float)$file->getSize();}unset($file);ksort($file_types);$html .= "<table><thead><tr><th align=\"left\">Type</th><th>Files</th><th>Size</th></tr></thead>";foreach($file_types as $type => $data) {$html .= "<tr><td class=\"label\">{$type}</td><td align=\"right\">{$this->_formatNum($data["count"])}</td><td align=\"right\">{$this->_formatFileSize($data["size"])}</td></tr>";}unset($data);$html .= "</table>";$html .= "<h2>Code Summary</h2>";if(count($lines_of_code) > 0) {$html .= "<table><thead><tr><th align=\"left\">Language</th><th>Files</th><th>Blank</th><th>Comment</th><th>Code</th><th>Total</th></tr></thead>";foreach($lines_of_code as $language => $lines) {$html .= "<tr><td class=\"label\">{$language}</td><td align=\"right\">" . ( isset($file_types[$language]["count"])? $this->_formatNum($file_types[$language]["count"]) : "N/A" ) . "</td><td align=\"right\">{$this->_formatNum($lines["blank"])}</td><td align=\"right\">{$this->_formatNum($lines["comment"])}</td><td align=\"right\">{$this->_formatNum($lines["code"])}</td><td align=\"right\">" . $this->_formatNum((int)$lines["blank"] + (int)$lines["comment"] + (int)$lines["code"]) . "</td></tr>";}$html .= "</table>";} else {$html .= "No lines of code found";}if(count($this->_files_recent)) {$html .= "<h2>Recently Modified Files</h2>";krsort($this->_files_recent);$html .= "<table><thead><tr><th align=\"left\">File</th><th>Date modified</th></tr></thead>";foreach($this->_files_recent as $file) {$html .= sprintf("<tr><td>%s</td><td align=\"right\">%s</td></tr>", $file["file"], $this->_formatDatetime($file["datetime"]));}unset($file);$html .= "</table>";}print $html . "<div id=\"footer\">Copyright &copy; " . date("Y"). " <a href=\"http://www.shayanderson.com/\">Shay Anderson</a></div></div></body></html>";}public function getConfig($key = null) {if(!is_array($this->_config)) {return;}if(array_key_exists($key, $this->_config)) {return $this->_config[$key];}}}final class PHProjectPart {private $_part = array("datetime" => null,"id" => null,"is_dir" => false,"is_read" => false,"name" => null,"path" => null,"size" => 0,"type" => null);public function  __construct($name = null, $path = null, $type = null) {$this->_part["name"] = $name;$this->_part["path"] = $path . "/";$this->_part["id"] = $this->getPath() . $this->getName();$this->_part["is_dir"] = is_dir($this->getPath() . $this->getName());$this->_part["size"] = @!$this->isDir() ? filesize($this->getPath() . $this->getName()) / 1024 : 0;$this->_part["type"] = !$this->isDir() ? $type : null;$this->_part["datetime"] = @!$this->isDir() ? date("YmdHis", filemtime($this->getPath() . $this->getName())) : null;}public function getDatetime() {return $this->_part["datetime"];}public function getId() {return $this->_part["id"];}public function getName() {return $this->_part["name"];}public function getPath() {return $this->_part["path"];}public function getSize() {return $this->_part["size"];}public function getType() {return $this->_part["type"];}public function isDir() {return $this->_part["is_dir"];}public function isRead() {return $this->_part["is_read"];}public function setIsRead($is_read = false) {$this->_part["is_read"] = (bool)$is_read;}}final class PHProjectException extends Exception {}
?>