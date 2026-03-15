<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class RequestPath
 * 
 * Improved version with:
 * - Replaced deprecated strtok() with parse_url()
 * - Enhanced path sanitization
 * - Better parameter handling
 * - Type safety improvements
 * 
 * @category Core Class
 * @license MIT
 * @version 1.1
 * @since Since Release 1.0
 */
class RequestPath
{
    private array $parts = [];
    private const MAX_PARAMS = 4;

    public function __construct()
    {
        $path = $this->getSanitizedPath();
        $bits = $this->splitPath($path);
        $this->parts = $this->parsePathBits($bits);
    }

    /**
     * Get and sanitize the request path
     */
    private function getSanitizedPath(): string
    {
        $path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '';
        
        // Remove query string more reliably than strtok()
        $parsed = parse_url($path);
        $cleanPath = $parsed['path'] ?? '';
        
        return $this->sanitizePath($cleanPath);
    }

    /**
     * Split path into components
     */
    private function splitPath(string $path): array
    {
        $trimmed = trim($path, '/');
        return $trimmed !== '' ? explode('/', $trimmed) : [];
    }

    /**
     * Sanitize path components
     */
    private function sanitizePath(string $path): string
    {
        // Normalize path separators
        $path = str_replace(['../', './'], '', $path);
        
        // Remove unwanted characters (more strict version)
        $cleanPath = preg_replace('/[^a-zA-Z0-9\/\-_\.]/', '', $path);
        
        return rtrim($cleanPath, '/');
    }

    /**
     * Parse path segments into named and sequential parameters
     */
    private function parsePathBits(array $bits): array
    {
        $parsed = [];
        $bits = array_values(array_filter($bits)); // Remove empty segments
        
        if (empty($bits)) {
            $parsed['matched'] = '';
            return $parsed;
        }

        // First segment is always the matched route
        $parsed['matched'] = $this->sanitizeValue(array_shift($bits));
        $parsed[] = $parsed['matched'];

        // Extract numbered parameters (param1, param2, etc.)
        for ($i = 1; $i <= self::MAX_PARAMS; $i++) {
            $key = "param$i";
            $value = $this->sanitizeValue($bits[$i - 1] ?? '');
            $parsed[$key] = $value;
            
            // Only add to sequential array if not empty
            if ($value !== '') {
                $parsed[] = $value;
            }
        }

        // Handle remaining segments as key-value pairs
        $this->parseKeyValuePairs(array_slice($bits, self::MAX_PARAMS), $parsed);

        return $parsed;
    }

    /**
     * Parse remaining segments as key-value pairs
     */
    private function parseKeyValuePairs(array $bits, array &$parsed): void
    {
        $count = count($bits);
        
        for ($i = 0; $i < $count; $i += 2) {
            if (!isset($bits[$i + 1])) break;
            
            $key = $this->sanitizeValue($bits[$i]);
            $value = $this->sanitizeValue($bits[$i + 1]);
            
            $parsed[$key] = $value;
            $parsed[] = $value;
        }
    }

    /**
     * Sanitize individual values
     */
    private function sanitizeValue(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // In lib/core/RequestPath.php, add this method before __get (around line 140)

    /**
     * Sets extracted named route parameters.
     * * Uses the magic __set method for proper sanitization and storage.
     * * @param array $params The array of matches from Dispatcher's preg_match.
     */
    public function setParameters(array $params): void
    {
        // Filter out numbered matches (0, 1, 2...) and only process named matches (id, post, category).
        // This prevents overwriting sequential parameters like param1, param2.
        foreach ($params as $key => $value) {
            if (is_string($key) && !empty($key)) {
                // Use the magic __set to handle sanitization for the new key/value
                $this->__set($key, $value);
            }
        }
    }

    /**
     * Magic getter with null coalescing
     */
    public function __get(string $key): mixed
    {
        return $this->parts[$key] ?? null;
    }

    /**
     * Magic setter with sanitization
     */
    public function __set(string $key, mixed $value): void
    {
        $this->parts[$key] = is_string($value) ? $this->sanitizeValue($value) : $value;
    }

    /**
     * Check if parameter exists
     */
    public function __isset(string $key): bool
    {
        return isset($this->parts[$key]);
    }

    /**
     * Get all parameters
     */
    public function getAll(): array
    {
        return $this->parts;
    }
}