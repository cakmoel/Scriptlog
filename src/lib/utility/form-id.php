<?php
/**
 * form_id()
 * 
 * generating random number for form id
 *
 * @category function
 * @author M.Noermoehammad
 * @param string|null $type
 * @return void
 */
function form_id($type = null)
{

 $form_id = null;

 if ( $type == "login" ) {

    $form_id = ircmaxell_generator_numbers(0, 999);

    $_SESSION['human_login_id'] = $form_id;

 } elseif ( $type == "comment") {

    $form_id = ircmaxell_generator_numbers(5, 797);

    $_SESSION['human_comment_id'] = $form_id;

 } else {

    $form_id = ircmaxell_generator_numbers(1, 100);
    
    $_SESSION['human_form_id'] = $form_id;

 }

 return $form_id;

}