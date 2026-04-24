<?php

/**
 * Generate OpenAPI Spec
 *
 * Generates OpenAPI specification with runtime server URLs
 * instead of hardcoded values.
 *
 * @category utility
 * @author Blogware Team
 * @license MIT
 * @version 1.0
 *
 */

function generate_openapi_spec(): void
{
    $specFile = __DIR__ . '/../openapi.json';
    
    if (!file_exists($specFile)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'status' => 404,
            'error' => 'OpenAPI specification not found'
        ], JSON_PRETTY_PRINT);
        return;
    }
    
    $specContent = file_get_contents($specFile);
    $spec = json_decode($specContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'status' => 500,
            'error' => 'Invalid OpenAPI specification JSON'
        ], JSON_PRETTY_PRINT);
        return;
    }
    
    // Get actual app URL from config
    $appUrl = 'http://localhost';
    $config = [];
    
    if (file_exists(__DIR__ . '/../config.php')) {
        $config = require __DIR__ . '/../config.php';
    }
    
    if (!empty($config['app']['url'])) {
        $appUrl = rtrim($config['app']['url'], '/');
    } elseif (!empty($_SERVER['HTTP_HOST'])) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $appUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
    }
    
    // Build API URL from app URL
    $apiUrl = $appUrl . '/api/v1';
    
    // Replace hardcoded URLs in servers array
    if (isset($spec['servers']) && is_array($spec['servers'])) {
        foreach ($spec['servers'] as &$server) {
            if (!empty($server['url'])) {
                // Only replace if it contains blogware.site or localhost
                if (strpos($server['url'], 'blogware.site') !== false || 
                    strpos($server['url'], 'localhost') !== false) {
                    $server['url'] = $apiUrl;
                }
            }
        }
    }
    
    // Replace hardcoded x-logo URL (use actual logo file)
    if (isset($spec['info']['x-logo']['url'])) {
        $spec['info']['x-logo']['url'] = $appUrl . '/public/files/pictures/scriptlog-1200x630.jpg';
    }
    
    // Output the modified spec
    header('Content-Type: application/json');
    header('X-API-Version: v1');
    echo json_encode($spec, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}