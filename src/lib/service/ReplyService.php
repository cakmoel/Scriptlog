<?php
/**
 * class ReplyService
 * 
 * @category Class Service
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 * 
 */
class ReplyService
{

 private $reply_id;

 private $post_id;

 private $author_name;

 private $author_ip;

 private $author_email;

 private $content;

 private $status;

 private $created_at;

 private $replyDao;

 private $validator;

 private $sanitizer;

 public function __construct(ReplyDao $replyDao, FormValidator $validator, Sanitize $sanitize)
 {
    $this->replyDao = $replyDao;
    $this->validator = $validator;
    $this->sanitizer = $sanitize;
 }

 public function setReplyId($reply_id)
 {
    $this->reply_id = $reply_id;
 }

 public function setPostId($post_id)
 {
    $this->post_id = $post_id;
 }

 public function setAuthorName($author_name)
 {
    $this->author_name = $author_name;
 }

 public function setAuthorIP($author_ip)
 {
    $this->author_ip = $author_ip;
 }

 public function setAuthorEmail($author_email)
 {
    $this->author_email = $author_email;
 }

 public function setReplyContent($content)
 {
    $this->content = prevent_injection($content);
 }

 public function setReplyStatus($status)
 {
    $this->status = $status;
 }

}