<?php
/**
 * remove_dir_recursive
 *
 * @category function
 * @author Johan van de Merwe
 * @see https://snipplr.com/view/69947/script-for-uploading-zip-file-and-unzip-it-on-the-server
 * @param string $dir
 * @return void
 */
function remove_dir_recursive($dir)
{

foreach(scandir($dir) as $file) {
        
 if ('.' === $file || '..' === $file) continue;
    
 if (is_dir("$dir/$file")) {
  
    remove_dir_recursive("$dir/$file");
 
 } else {

    unlink("$dir/$file");

 }

}
 
rmdir($dir);

}