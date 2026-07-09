<?php

/**
 * distill_post_request()
 *
 * filtering optionally external variable
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param array $refine
 * @return mixed
 *
 */
function distill_post_request($refine)
{

    if (is_array($refine)) {

        static $validFilterIds = null;

        if ($validFilterIds === null) {
            $validFilterIds = array_map(function ($name) {
                return filter_id($name);
            }, filter_list());
            $validFilterIds = array_flip($validFilterIds);
        }

        $cleanFilters = [];
        foreach ($refine as $key => $value) {
            if (is_int($value) && isset($validFilterIds[$value])) {
                $cleanFilters[$key] = $value;
            } elseif (is_array($value)) {
                $cleanFilters[$key] = $value;
            } else {
                $cleanFilters[$key] = FILTER_UNSAFE_RAW;
            }
        }
        return filter_input_array(INPUT_POST, $cleanFilters);
    } else {
        scriptlog_error("can not retrieve external variables, please make sure it is an array!");
    }
}
