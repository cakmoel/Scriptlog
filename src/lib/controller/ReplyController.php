<?php
/**
 * class ReplyController extends BaseApp
 * 
 * @category Class ReplyApp extends BaseApp
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 * 
 */
class ReplyController extends BaseApp
{
 private $view;

 private $replyService;

 public function __construct(ReplyService $replyService)
 {
    $this->replyService = $replyService;
 }

 public function listItems()
 {
    
 }

 public function insert()
 {

 }

 public function update($id)
 {

 }

 public function remove($id)
 {
    
 }

}