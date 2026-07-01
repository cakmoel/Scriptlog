<?php

/**
 * generate_request
 *
 * build http query for requesting in order
 * to act CRUD functionality especially in administrator page.
 *
 * @category Function
 * @author M.Noermoehammad
 * @license https://mit-license.org/ MIT License
 * @link https://github.com/cakmoel/scriptlog
 */

declare(strict_types=1);

require_once __DIR__ . '/sanitize-urls.php';

/**
 * generate_request function
 *
 * @param string $base
 * @param string $type
 * @param array $data
 * @param bool $string_encoded
 * @return array
 */
function generate_request($base, $type, $data = array(), $string_encoded = true)
{

    $html = array();

    $load = (isset($data) && array_key_exists(0, $data)) ? rawurlencode((string)$data[0]) : null;
    $action = (isset($data) && array_key_exists(1, $data)) ? urlencode((string)$data[1]) : null;
    $id = (isset($data) && array_key_exists(2, $data)) ? urlencode((string)$data[2]) : null;
    $unique_id = (isset($data) && array_key_exists(3, $data)) ? urlencode((string)$data[3]) : null;

    switch ($type) {
        default:
        case 'get':
            check_request_generated();

            if ($string_encoded) {
                if ($load === 'users') {
                    $query_data = array(

                        'load' => sanitize_urls($load),
                        'action' => $action,
                        'Id' => abs((int)$id),
                        'sessionId' => sanitize_urls($unique_id)

                    );
                } elseif ($load === 'logout') {
                    $query_data = array(

                      'load' => sanitize_urls($load),
                      'action' => $action,
                      'logOutId' => $id,

                     );
                } else {
                    $query_data = array(

                      'load' => sanitize_urls($load),
                      'action' => $action,
                      'Id' => abs((int)$id)

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
                if ($load === 'users') {
                    $query_data = array(

                       'load' => $load,
                       'action' => $action,
                       'Id' => abs((int)$id),
                       'sessionId' => sanitize_urls($unique_id)

                    );
                } else {
                    $query_data = array(

                     'load' => $load,
                     'action' => $action,
                     'Id' => abs((int)$id)

                    );
                }
            } else {
                $query_data = array(

                  'load' => sanitize_urls($load)

                );
            }

            $html['link'] = build_query($base, $query_data);

            break;
    }

    return $html;
}
