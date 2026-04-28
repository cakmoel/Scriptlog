<?php
/**
 * Simple Minifier Script for Scriptlog Theme
 * Minifies CSS and JS files in ALL theme directories
 * 
 * Usage: php tmp/minify.php
 * 
 * @category function to minify CSS and JS files in Scriptlog theme directories 
 * @author Nirmala Adiba Khanza <nirmala.adiba.khanza@gmail.com>
 * @license MIT 
 * @version 1.0.0
 * @since 1.0.0
 * 
 */

function minify_css($input) {
    if (empty($input)) return $input;
    // Remove comments
    $output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $input);
    // Remove whitespace
    $output = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $output);
    // Remove redundant semi-colons and spaces
    $output = preg_replace(['/((?:\s|;)+)/', '/\s*([{};:>+])\s*/'], ['$1', '$1'], $output);
    $output = str_replace(';}', '}', $output);
    return trim($output);
}

function minify_js($input) {
    if (empty($input)) return $input;
    // VERY basic JS minification - removing comments and redundant whitespace
    // Note: This is not a full compressor like Terser, but safe for simple scripts
    $output = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $input); // multi-line comments
    $output = preg_replace('!^\s*//.*$!m', '', $output); // single-line comments at start of line
    $output = preg_replace('/(\n|\r)+/', "\n", $output); // reduce multiple newlines
    $output = preg_replace('/[ \t]+/', ' ', $output); // reduce multiple spaces/tabs
    return trim($output);
}

$themes_dir = __DIR__ . '/../public/themes';
$theme_folders = array_diff(scandir($themes_dir), ['.', '..', 'index.php', 'maintenance.php']);

$total_css = 0;
$total_js = 0;

foreach ($theme_folders as $theme) {
    $theme_path = $themes_dir . '/' . $theme . '/assets';
    
    // Skip if assets directory doesn't exist
    if (!is_dir($theme_path)) {
        continue;
    }
    
    $css_dir = $theme_path . '/css';
    $js_dir = $theme_path . '/js';
    
    // Process CSS files
    if (is_dir($css_dir)) {
        echo "Minifying CSS files for theme: $theme\n";
        foreach (glob("$css_dir/*.css") as $file) {
            if (strpos($file, '.min.css') !== false) continue;
            $min_file = str_replace('.css', '.min.css', $file);
            echo "  Processing " . basename($file) . " -> " . basename($min_file) . "\n";
            file_put_contents($min_file, minify_css(file_get_contents($file)));
            $total_css++;
        }
    }
    
    // Process JS files  
    if (is_dir($js_dir)) {
        echo "Minifying JS files for theme: $theme\n";
        foreach (glob("$js_dir/*.js") as $file) {
            if (strpos($file, '.min.js') !== false) continue;
            $min_file = str_replace('.js', '.min.js', $file);
            echo "  Processing " . basename($file) . " -> " . basename($min_file) . "\n";
            file_put_contents($min_file, minify_js(file_get_contents($file)));
            $total_js++;
        }
    }
}

echo "\nMinification complete.\n";
echo "Total CSS files minified: $total_css\n";
echo "Total JS files minified: $total_js\n";