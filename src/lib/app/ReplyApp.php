<?php
/**
 * Class ReplyApp extends Class BaseApp
 * 
 * @package  SCRIPTLOG/LIB/ReplyApp
 * @category App Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * 
 */
class ReplyApp extends BaseApp
{
  private $view;

  private $replyEvent;

  public function __construct(ReplyEvent $replyEvent)
  {
    $this->replyEvent = $replyEvent;
  }


}