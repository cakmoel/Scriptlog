<?php

class RequestPath
{
    private $parts = [];

    public function __construct()
    {
        // Determine the path from PATH_INFO or REQUEST_URI
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

        // Remove trailing slashes
        return rtrim($path, '/');
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
        $parsed['matched'] = array_shift($bits) ?? null;
        $parsed[] = $parsed['matched'];

        // Extract up to 4 parameters
        for ($i = 1; $i <= 4; $i++) {
            $key = "param$i";
            $parsed[$key] = array_shift($bits) ?? null;
            $parsed[] = $parsed[$key];
        }

        // Parse remaining bits as key-value pairs
        $this->parseKeyValuePairs($bits, $parsed);

        // Add any remaining single value
        if (!empty($bits)) {
            $parsed[] = array_shift($bits);
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
            $parsed[$bits[$i]] = $bits[$i + 1];
            $parsed[] = $bits[$i + 1];
        }
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
        $this->parts[$key] = $value;
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