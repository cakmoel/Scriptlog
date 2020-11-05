<?php
/**
 * Class FrontTopicDao extends Dao
 * 
 * @category Dao Class
 * @author M.Noermoehammad
 * @license MIT
 * 
 */
class FrontTopicDao extends Dao 
{

public function __construct()
{
 parent::__construct();
}

/**
 * showAllActiveTopics
 * retrieve all active topics and display it on sidebar themes
 * 
 * @return array
 * 
 */
public function showAllActiveTopics()
{
 
 $sql = "SELECT ID, topic_title, topic_slug, topic_status FROM  tbl_topics 
         WHERE topic_status = 'Y' ORDER BY ID ";

 $this->setSQL($sql);

 $active_topics = $this->findAll();

 return (empty($active_topics)) ?: $active_topics;

}

}