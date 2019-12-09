<?php
/**
 * Class FrontContent extends Class ContentGateway
 * 
 * @category Core Class
 * @author   M.Noermoehammad
 * @license  MIT
 * @version  1.0
 * @since    Since Release 1.0
 */
class FrontContent extends ContentGateway
{

  /**
   * Error
   * 
   * @var string
   */
  private $errors;

  /**
   * 
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * readPost
   * 
   * @param object $post
   * @param string  $param
   */
  public function readPost($post, $param = null)
  {

    try {

        if (is_object($post)) {

            if (is_null($param)) {

                return $this->grabPost($post);

            } else {

                return $this->grabPost($post, $param);

            }

        } else {
          
            throw new FrontException('Sorry, the variable requested is not an object');

        }

    } catch (FrontException $e) {

        $this->errors = LogError::newMessage($e);
        $this->errors = LogError::customErrorMessage();

    }

  }

  /**
   * readTopic
   * 
   * @param object $topic
   * @param string $param
   * 
   */
  public function readTopic($topic, $param)
  {

    try {

        if (is_object($topic)) {

            return $this->grabTopic($topic, $param);

        } else {

            throw new FrontException('Sorry, the variable requested is not an object');

        }

    } catch(FrontException $e) {

        $this->errors = LogError::newMessage($e);
        $this->errors = LogError::customErrorMessage();

    }

  }

  /**
   * readPage
   * 
   * @param object $page
   * @param string $param
   * 
   */
  public function readPage($page, $param)
  {

    try {
        
        if (is_object($page)) {

            return $this->grabPage($page, $param);

        } else {

            throw new FrontException('Sorry, the variable requested is not an object');

        }

    } catch (FrontException $e) {
        
        $this->errors = LogError::newMessage($e);
        $this->errors = LogError::customErrorMessage();

    }

  }

}