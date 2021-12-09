<?php
/**
 * get_directory_size_scandir()
 *
 * @param string $directory
 * @see https://fullstackgeek.blogspot.com/2019/08/calculate-directory-size-in-php.html
 * @return mixed
 * 
 */
function get_directory_size_scandir($directory)
{

$total_size = 0;

$directories_scanned = scandir($directory);

foreach ($directories_scanned as $key => $file_name) {
    
    if ($file_name != ".." && $file_name != ".") {

        if (is_dir($directory.DIRECTORY_SEPARATOR.$file_name)) {

            $total_size = $total_size + get_directory_size_scandir($directory.DIRECTORY_SEPARATOR.$file_name);

        } elseif ( is_file($directory.DIRECTORY_SEPARATOR.$file_name)) {

            $total_size = $total_size + filesize($directory.DIRECTORY_SEPARATOR.$file_name);

        }

    }

}

return $total_size;

}

/**
 * get_directory_size_spl()
 *
 * @param string $path
 * @see https://stackoverflow.com/a/21409562/6667699
 * @return int
 * 
 */
function get_directory_size_spl($path)
{

$bytes_total = 0;

if (!function_exists('realpath')) {

    $path = absolute_path($path);

} else {

    $path = realpath($path);

}

if ($path !== false && $path != '' && file_exists($path)) {

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
        
        $bytes_total += $object->getSize(); 

    }

}

return $bytes_total;

}

/**
 * get_directory_size_glob()
 *
 * @param string $dir
 * @see https://gist.github.com/eusonlito/5099936
 * @return int
 * 
 */
function get_directory_size_glob($dir)
{

 $size = 0;

 foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
        
    $size += is_file($each) ? filesize($each) : get_directory_size_glob($each);
    
 }

 return $size;

}