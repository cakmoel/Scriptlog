<?php

/**
 * terminator()
 *
 * terminating user's related content (ex: post, comment)
 * 
 * @category function
 * @author Nirmala Adiba Khanza
 * @param int|numeric $userID
 * @return bool
 * 
 */
function terminator($userID)
{

    $post_id = null;

    $privilege = ['administrator', 'manager', 'editor', 'author', 'contributor', 'subscriber'];

    if (!in_array(user_privilege(), $privilege)) {

        header($_SERVER["SERVER_PROTOCOL"] . " 400 Bad Request", true, 400);
        header("Status: 400 Bad Request");
        exit("Sorry, user termination failed");
    } else {

        // grabbing post ID
        $grab_post_id = function_exists('medoo_get_where') ? medoo_get_where('tbl_posts', 'ID', ['post_author' => $userID]) : "";
        $post_id = isset($grab_post_id['ID']) ? abs((int)$grab_post_id['ID']) : 0;

        if (function_exists('medoo_delete')) {

            // removing comment
            $remove_comments = medoo_delete('tbl_comments', ['comment_post_id' => $post_id]);

            // remove post
            $remove_post = medoo_delete('tbl_posts', ['post_author' => $userID]);
        }

        return $remove_comments && $remove_post ? true : false;
    }
}
