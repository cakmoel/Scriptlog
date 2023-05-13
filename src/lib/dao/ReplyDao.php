<?php

class ReplyDao extends Dao
{

private $selected;

public function __construct()
{
 parent::__construct();
}

public function replyComment($commmentId)
{

}

public function dropDownReplyStatement($selected = '')
{
  $name = 'reply_status';

  $reply_status = array('draft' => 'Draft', 'pending' => 'Pending', 'reviewed' => 'Reviewed', 'publish' => 'Publish');

  if ( $selected !== '') {

    $this->selected = $selected;

  }

  return dropdown($name, $reply_status, $this->selected);

}

}