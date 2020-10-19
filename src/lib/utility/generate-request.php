<?php
/**
 * generate_request function
 * build http query for requesting in order 
 * to act CRUD functionality especially in administrator page.
 * 
 * @category Function
 * @param string $value
 * @param string $type
 * @return array
 * 
 */
function generate_request($base, $type, $data = array(), $string_encoded = true )
{
 
 $html = array();

 $load = (is_array($data) && array_key_exists(0, $data)) ? rawurlencode($data[0]) : '';
 $action = (is_array($data) && array_key_exists(1, $data)) ? urlencode($data[1]) : null;
 $id = (is_array($data) && array_key_exists(2, $data)) ? urlencode($data[2]) : 0;
 $unique_id = (is_array($data) && array_key_exists(3, $data)) ? urlencode($data[3]) : '';

 switch ($type) {

   case 'get':

      check_request_generated();

        if ($string_encoded) {

           if ($load === 'users') {

               $query_data = array(
              
                   'load' => sanitize_urls($load),
                   'action'=> $action,
                   'Id'=> abs((int)$id),
                   'sessionId' => sanitize_urls($unique_id)

               );

           } elseif($load === 'logout') {

             $query_data = array(
              
               'load' => sanitize_urls($load),
               'action'=> $action,
               'logOutId'=> $id,
               
              );
                  
           } else {

              $query_data = array(
              
                'load' => sanitize_urls($load),
                'action'=> $action,
                'Id'=> abs((int)$id)
         
               );
           }

        } else {

           $query_data = array(

             'load' => sanitize_urls($load)

           );

        } 

      $html['link'] = build_query($base, $query_data);
      
      break;

   case 'post':

      check_request_generated();

         if ($string_encoded) {

            if($load === 'users') {

               $query_data = array(
  
                  'load' => $load,
                  'action' => $action,
                  'Id' => abs((int)$id),
                  'sessionId' => sanitize_urls($unique_id)

               );
  
            } else {
  
              $query_data = array(
                
                 'load' => $load,
                 'action'=> $action,
                 'Id'=> abs((int)$id)
            
              );
  
            }
  
        }

      $html['link'] = build_query($base, $query_data);
  
      break;
       
 }

   return $html;

}

/**
 * check_request_generated
 *
 * @category Function
 * @return void
 * 
 */
function check_request_generated()
{

$method = ['GET', 'POST'];

if(true === block_request_type(current_request_method(), $method)) {

   http_response_code(405);
   scriptlog_error("405 - Method Not Allowed");

} else {

    unset($method);

}

}