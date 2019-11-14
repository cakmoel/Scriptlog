<?php
/**
 * Class FIleCache
 * 
 * @package  SCRIPTLOG/LIB/CORE/FileCache
 * @category Core Class
 * @author   Evert Pot
 * @see      https://evertpot.com/107/
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class FileCache extends Cache
{

  public function readCache($key)
  {

    $filename = $this->getFileName($key);
    if (!file_exists($filename)) return false;
    $fetchCache = fopen($filename, r);
    
    if(!$fetchCache) return false;

    flock($fetchCache, LOCK_SH);

    $data = file_get_contents($filename);
    fclose($fetchCache);

    $data = unserialize($data);
    if (!$data) {
      unlink($filename);
      return false;
    }

    if (time() > $data[0]) {
      unlink($filename);
      return false;
    }

    return $data[1];

  }

  public function writeCache($key, $data, $expirationTimes)
  {
    
    $readCache = fopen($this->getFileName($key), 'a+');
    if (!$readCache) throw new CacheException('Could not write to cache');

    flock($readCache, LOCK_EX);

    fseek($readCache, 0);

    ftruncate($readCache, 0);

    $data = serialize(array(time()+$expirationTimes, $data));
    if (fwrite($readCache, $data) === false) {
       throw new CacheException('Could not write to cache');
    }

    fclose($readCache);

  }

  public function removeCache($key)
  {
    
    $filename = $this->getFileName($key);
    if (file_exists($filename)) {

       return unlink($filename);

    } else {

       return false;

    }

  }

  private function getFileName($key)
  {
    return ini_get('session.save_path').'/public/cache'.md5($key);
  }

}