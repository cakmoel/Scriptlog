<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * interface FrontProviderInterface
 * 
 * @category Core Class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @since Since Release 1.0
 * 
 */
interface FrontProviderInterface
{

public function setFrontTitle($frontTitle);

public function getFrontTitle();

}