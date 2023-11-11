<?php
/**
 * delete_directory()
 * 
 * @category function
 * @author Contributors
 * @license MIT
 * @version 1.0
 * @param string $dirname
 * @return boolean
 * 
 */
function delete_directory($dirname)
{
    
 if (is_dir( $dirname ))
        
    $dir_handle = opendir( $dirname );

    if (!$dir_handle) return false;
            
        while ( $file = readdir( $dir_handle ) ) {

            if ($file != "." && $file != "..") {

                if (!is_dir( $dirname . "/" . $file )) {

                    unlink( $dirname . "/" . $file );
                        
                } else {

                    delete_directory( $dirname . '/' . $file );

                }

                            
            }
                
        }
            
    closedir( $dir_handle );
            
    rmdir( $dirname );
            
    return true;
            
}


/**
 * rm_from_folder
 * Deleting all files from a folder
 * 
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $path
 * @see https://stackoverflow.com/questions/4594180/deleting-all-files-from-a-folder-using-php
 * @see https://stackoverflow.com/questions/1334398/how-to-delete-a-folder-with-contents-using-php
 * @see https://stackoverflow.com/questions/3349753/delete-directory-with-files-in-it
 * @see https://stackoverflow.com/questions/4495091/delete-all-files-in-a-folder-with-php
 * @return bool
 * 
 */
function rm_from_folder($dir)
{
  $directory_iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
  $recursive_iterator = new RecursiveIteratorIterator($directory_iterator, RecursiveIteratorIterator::CHILD_FIRST);

  foreach ($recursive_iterator as $file) {

     $file->is_dir($dir) ? rmdir($file) : unlink($file);

  }

  return true;
  
}