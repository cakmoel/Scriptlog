<?php

/**
 * detect_web_server()
 *
 * Detects the current web server software reliably.
 * Checks multiple server variables for accuracy.
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 *
 */
function detect_web_server()
{

    # Check SERVER_SOFTWARE first
    if (isset($_SERVER['SERVER_SOFTWARE']) && !empty($_SERVER['SERVER_SOFTWARE'])) {
        $server = strtolower($_SERVER['SERVER_SOFTWARE']);

        if (strpos($server, 'nginx') !== false) {
            return 'Nginx';
        }

        if (strpos($server, 'apache') !== false) {
            return 'Apache';
        }

        if (strpos($server, 'litespeed') !== false) {
            return 'LiteSpeed';
        }

        # Return the detected server name
        $parts = explode('/', $server);
        return ucfirst($parts[0]);
    }

    # Fallback: Check for Nginx-specific CGI variables
    if (isset($_SERVER['NGINX']) || isset($_SERVER['nginx'])) {
        return 'Nginx';
    }

    # Default to Apache as it's the most common
    return 'Apache';
}
