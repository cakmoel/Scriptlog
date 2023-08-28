<?php
/**
 * form_action
 *
 * @category Function
 * @author M.Noermoehammad
 * @param string $base
 * @param array $data
 * @param string $type
 *
 */
function form_action($base, array $data, $type = "ordinary")
{
  $form = [];

  $action = (is_array($data) && array_key_exists(0, $data) ? rawurlencode($data[0]) : '');
  $id = (is_array($data) && array_key_exists(1, $data) ? urlencode($data[1]) : null);
  $uniqueKey =  (is_array($data) && array_key_exists(2, $data) ? urlencode($data[2]) : null);

  $query_data = array(
    
       'action' => $action,
       'Id' => abs((int)$id),
       'uniqueKey'=> sanitize_urls($uniqueKey),
    
    );
    
if ($type === 'login') {

    $form['doLogin'] = build_query($base, $query_data);

} else {

    $form['ordinary'] = isset($_SERVER["PHP_SELF"]) ? purify_dirty_html($_SERVER["PHP_SELF"]) : "";

}

return $form;

}