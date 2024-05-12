<?php
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class DebugRoute
 *
 * This class provides methods for debugging URL routes in PHP applications.
 *
 * @category Core class
 * @author Nirmala Khanza
 * @license  MIT
 * @version  1.0
 * @since Since Release 0.1
 */
class DebugRoute
{
    private static $routes = [
        'home'     => "/",
        'category' => "/category/(?'category'[\w\-]+)",
        'archive'  => "/archive/[0-9]{2}/[0-9]{4}",
        'blog'     => "/blog([^/]*)",
        'page'     => "/page/(?'page'[^/]+)",
        'single'   => "/post/(?'id'\d+)/(?'post'[\w\-]+)",
        'search'   => "(?'search'[\w\-]+)",
        'tag'      => "/tag/(?'tag'[\w\-]+)",
        'comment'  => "/post/(?'id'\d+)/(?'post'[\w\-]+)/comment/(?'comment'\d+)",
    ];

    protected static function requestURI()
    {
        $script_name = rtrim(dirname($_SERVER["SCRIPT_NAME"]), DIRECTORY_SEPARATOR);
        $request_uri = DIRECTORY_SEPARATOR . trim(str_replace($script_name, '', $_SERVER['REQUEST_URI']), DIRECTORY_SEPARATOR);
        return urldecode($request_uri);
    }

    public static function debugging()
    {
        error_reporting(E_ALL); // Set error reporting to show all errors and warnings

        $requestURI = self::requestURI();

        foreach (self::$routes as $key => $value) {
  
            // Add delimiters to the regex pattern
            $pattern = '~^' . $value . '$~i';

            // Try to match the URL against the pattern
            if (@preg_match($pattern, $requestURI, $matches)) {
                
                echo "<div style='padding: 10px; border: 1px solid #ccc; margin-bottom: 20px;'>";
                echo "<h3 style='margin: 0;'>Debugging Results</h3>";
                echo "<hr style='margin-top: 5px; margin-bottom: 10px;'>";

                echo "<strong>Route Pattern:</strong> $pattern<br>";
                echo "<strong>Matched Route:</strong> $key<br>";
                echo "<strong>Request URI:</strong> $requestURI<br>";
                
                // Print captured parameters
                if (!empty($matches)) {
                    echo "<strong style='color: #008000;'>Captured parameters:</strong><br>";
                    echo "<pre>";
                    print_r($matches);
                    echo "</pre>";
                } else {
                    echo "<strong style='color: #008000;'>No captured parameters</strong>";
                }
                
                // Print execution time
                $executionTime = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
                echo "<strong style='color: #008000;'>Page executed in:</strong> <span style='color: #FF0000;'>" . number_format($executionTime, 4) . " seconds</span>";
                
                echo "</div>";
                
                return; // Stop after first match
            }
        }

        // If no match is found
        echo "<div style='padding: 10px; border: 1px solid #ccc; margin-bottom: 20px;'>";
        echo "<h3 style='margin: 0;'>Debugging Results</h3>";
        echo "<hr style='margin-top: 5px; margin-bottom: 10px;'>";
        echo "<strong>No Match Found</strong>";
        echo "</div>";
        
    }
}
