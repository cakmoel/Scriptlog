<?php
/**
 * check_weblink
 * ฀check฀ all ฀links ฀on a ฀web ฀page฀ 
 * and฀ test ฀whether ฀the ฀pages ฀they ฀refer ฀to ฀actually฀ load ฀or not
 * Example:
 * $page = "http://flickr.com";
 * $results = check_weblink($page, 2, 240);
 *    if(isset($results[0])) {
 *          if($results[0] == 0) echo "URL is Live !";
 *    }
 * 
 * @category function฀
 * @author Contributors
 * @license MIT
 * @version 1.0
 * @param string $page A ฀web฀ page ฀URL,฀including ฀the ฀http://฀ preface ฀and ฀domain ฀name
 * @param int|number $timeout 
 * @param int|number $runtime
 * @return array
 * 
 */
function check_weblink($page, $timeout, $runtime)
{
    
ini_set('max_execution_time', $runtime);

$contents = file_get_contents($page);
if (!$contents) { return array(1, array($page) ) ; }

$checked = array();
$failed = array();
$fail   = 0;
$urls = catch_weblink($page);

$context = stream_context_create(array('http'=>array('timeout'=>$timeout)));

for ($i=0; $i < count($urls); $i++) {

     if (!in_array($urls[$i], $checked)) {

          $checked[] = $urls[$i];

          if (!file_get_contents($urls[$i], 0, $context, 0, 256)) { $failed[$fail++] = $urls[$i]; }

     }

} 

return array($fail, $failed);

}