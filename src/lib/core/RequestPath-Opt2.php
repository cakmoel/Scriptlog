<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class RequestPath
 * 
 * Example:
 * $request = new RequestPath();
 * echo "Request matched: {$request->matched} <br>";
 * echo "Request param1: {$request->param1} <br>";
 * echo "Request param2: {$request->param2} <br>";
 * 
 * @category Core Class
 * @author Davey Shafik, Matthew Weier O’Phinney, Ligaya Turmelle, Harry Fuecks, and Ben Balbo
 * @author M.Noermoehammad, Nirmala Khanza
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
class RequestPath
{
    private $parts = [];

    public function __construct()
    {
        // Get and sanitize the request path
        $path = $this->getSanitizedPath();

        // Split the path into parts
        $bits = explode('/', trim($path, '/'));

        // Parse the path into named parameters and sequential values
        $this->parts = $this->parsePathBits($bits);
    }

    /**
     * Get the sanitized request path.
     *
     * @return string
     */
    private function getSanitizedPath(): string
    {
        $path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'];

        // Remove query string (if any)
        $path = strtok($path, '?');

        // Sanitize the path to prevent directory traversal and other attacks
        $path = $this->sanitizePath($path);

        // Remove trailing slashes
        return rtrim($path, '/');
    }

    /**
     * Sanitize the path to prevent security issues.
     *
     * @param string $path
     * @return string
     */
    private function sanitizePath(string $path): string
    {
        // Remove directory traversal sequences
        $path = str_replace(['../', './'], '', $path);

        // Allow only alphanumeric, slashes, hyphens, and underscores
        return preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $path);
    }

    /**
     * Parse the path bits into named parameters and sequential values.
     *
     * @param array $bits
     * @return array
     */
    private function parsePathBits(array $bits): array
    {
        $parsed = [];

        // Extract matched and parameters
        $parsed['matched'] = $this->sanitizeValue(array_shift($bits) ?? '');
        $parsed[] = $parsed['matched'];

        // Extract up to 4 parameters
        for ($i = 1; $i <= 4; $i++) {
            $key = "param$i";
            $parsed[$key] = $this->sanitizeValue(array_shift($bits) ?? '');
            $parsed[] = $parsed[$key];
        }

        // Parse remaining bits as key-value pairs
        $this->parseKeyValuePairs($bits, $parsed);

        // Add any remaining single value
        if (!empty($bits)) {
            $parsed[] = $this->sanitizeValue(array_shift($bits));
        }

        return $parsed;
    }

    /**
     * Parse key-value pairs from the remaining bits.
     *
     * @param array $bits
     * @param array &$parsed
     */
    private function parseKeyValuePairs(array $bits, array &$parsed): void
    {
        $partsSize = count($bits);

        // Ensure even number of elements for key-value pairs
        if ($partsSize % 2 != 0) {
            $partsSize -= 1;
        }

        // Add key-value pairs
        for ($i = 0; $i < $partsSize; $i += 2) {
            $key = $this->sanitizeValue($bits[$i]);
            $value = $this->sanitizeValue($bits[$i + 1]);
            $parsed[$key] = $value;
            $parsed[] = $value;
        }
    }

    /**
     * Sanitize a value to prevent XSS and other attacks.
     *
     * @param string $value
     * @return string
     */
    private function sanitizeValue(string $value): string
    {
        // Escape HTML special characters
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Magic getter for accessing parts.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->parts[$key] ?? null;
    }

    /**
     * Magic setter for setting parts.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->parts[$key] = $this->sanitizeValue($value);
    }

    /**
     * Magic isset for checking if a part exists.
     *
     * @param string $key
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->parts[$key]);
    }
}