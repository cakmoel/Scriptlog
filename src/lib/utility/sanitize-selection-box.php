<?php 
/**
 * Sanitize select dropdown list function
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param array $values
 * @param array $white_args
 * 
 */
function sanitize_selection_box($values, $white_args)
{

    switch ($values) {

        case 'post_status':

              if (empty($values['post_status']) || !in_array($values['post_status'], $white_args)) {

                return false;

              }

            break;

        case 'comment_status':

            if (empty($values['comment_status']) || !in_array($values['comment_status'], $white_args)) {

                return false;

            }

            break;

        case 'permalinks':

            if (empty($values['permalinks']) || !in_array($values['permalinks'], $white_args)) {

                return false;
            }
 
            break;

        case 'media_access':

            if (empty($values['media_access']) || !in_array($values['media_access'], array_keys($white_args))) {

                return false;

            }

            break;

        case 'media_target':

            if (empty($values['media_target']) || !in_array($values['media_target'], array_keys($white_args))) {

                return false;

            }

            break;

        case 'media_status':

            if (empty($values['media_status']) || !in_array($values['media_status'], $white_args)) {

                return false;

            }

            break;
    
        case 'menu_status':

            if (empty($values['menu_status']) || !in_array($values['menu_status'], $white_args)) {

                return false;

            }

            break;

        case 'topic_status':

            if (empty($values['topic_status']) || !in_array($values['topic_status'], $white_args)) {

                return false;

            }

            break;

        case 'plugin_level':

            if (empty($values['plugin_level']) || !in_array($values['plugin_level'], $white_args)) {

                return false;

            }
            
           break;

        case 'user_level':

            if ( empty($values['user_level']) || !in_array($values['user_level'], $white_args) ) {

                return false;

            }

            break;

        default:

            return true;

          break;
    
    }
    
}