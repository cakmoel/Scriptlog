<?php

/**
 * front_permalink
 *
 * @param integer $id
 * @return object if true else return false
 *
 */
function front_permalinks($id)
{

  return FrontHelper::frontPermalinks($id);

}

/**
 * front_galleries
 *
 * @param int $start
 * @param int $limit
 * @return void
 * 
 */
function front_galleries($start, $limit)
{

return FrontHelper::frontGalleris($start, $limit);

}
