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
         // Zero-padded to a constant 3 digits so the login/signup form-action
         // URL length does not vary with human_login_id()/signup_id() (e.g.
         // "7" vs "742"). Combined with the fixed-length CSRF token this keeps
         // the rendered page byte-stable for load-testing (see
         // report/WP-VS-SCRIPTLOG-LOAD-TESTING-ANALYSIS-REPORT.md §9.2). All
         // consumers cast the incoming 'Id' to int, so leading zeros are safe.
         'Id' => str_pad((string)abs((int)$id), 3, '0', STR_PAD_LEFT),
         'uniqueKey' => sanitize_urls($uniqueKey),

      );

    if ($type === 'login') {
        $form['doLogin'] = build_query($base, $query_data);
    } elseif ($type == 'signup') {
        $form['doSignup'] = build_query($base, $query_data);
    } else {
        $form['ordinary'] = isset($_SERVER["PHP_SELF"]) ? purify_dirty_html($_SERVER["PHP_SELF"]) : "";
    }

    return $form;
}
