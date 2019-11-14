<?php
/**
 * Escape output function
 * 
 * @category Function
 * @package  SCRIPTLOG/LIB/UTILITY
 * @param string $value
 * @param string $type
 * 
 */
function escape_request($base, $query_data, $type, $string_encoded = array())
{
 
 $html = array();

 $load = (is_array($string_encoded) && array_key_exists(0, $string_encoded)) ? rawurlencode($string_encoded[0]) : '';
 $action = (is_array($string_encoded) && array_key_exists(1, $string_encoded)) ? urlencode($string_encoded[1]) : '';
 $id = (is_array($string_encoded) && array_key_exists(2, $string_encoded)) ? urlencode($string_encoded[2]) : '';
 $user_session = (is_array($string_encoded) && array_key_exists(3, $string_encoded)) ? urlencode($string_encoded[3]) : '';

 switch ($type) {

   case 'get':

      if (!empty($string_encoded)) {

         if ($load === 'users') {

            $query_data = array(
              
              'load' => $load,
              'action'=> $action,
              'userId'=> sanitize_urls($id),
              'sessionId' => $user_session

            );

         } else {

            $query_data = array(
              
              'load' => $load,
              'action'=> $action,
              'Id'=> $id,
         
           );

         }

         $html['link'] = build_query($base, $query_data);

      } else {

         $query_data = array(

             'load' => sanitize_urls($load)

         );

         $html['link'] = build_query($base, $query_data);
          
      } 

      break;


   case 'post':

      
      break;

       
 }

 return $html;
   
}