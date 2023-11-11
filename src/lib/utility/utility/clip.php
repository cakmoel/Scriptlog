<?php
/**
 * clip()
 * 
 * Attach or remove multiple callbacks to an clip and trigger those callbacks when that clip is called 
 * 
 * #### Example: ###
 * === Add a clip ===   
 *  clip('browser_name', null, function($browser){ return get_browser_name(); });
 * === OR like this ===
 *  clip('browser_name', null, 'get_browser_name');
 * === Then call it like this ===
 * $identify_browser = clip('browser_name', $_SERVER['HTTP_USER_AGENT']);
 * === Or remove all callbacks for that clip like this ===
 * clip('browser_name', null, false);
 * 
 *
 * @category Function
 * @author Xeoncross
 * @license MIT
 * @param string $clip
 * @param string $value
 * @param string $callback
 * @return void
 * 
 */
function clip($clip, $value = null, $callback = null)
{
    
static $clips;

if ($callback !== null) {

    if ($callback) {

         $clips[$clip][] = $callback;

    } else {

        unset($clips[$clip]);

    }

} elseif (isset($clips[$clip])) {

    foreach ($clips[$clip] as $function) {

        $value = call_user_func($function, $value);

    }

    return $value;

}

}