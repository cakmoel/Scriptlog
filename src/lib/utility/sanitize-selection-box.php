<?php 
/**
 * Sanitize select dropdown list function
 *
 * @param array $values
 * @param array $white_args
 * @return void
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

        default:

            return true;

          break;
    
    }
    
}