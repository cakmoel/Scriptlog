<?php

/**
 * is_registration_unable
 *
 * @category function
 * @author Nirmala Khanza <nirmala.adiba.khanza@email.com>
 * @param int|num $id
 * @return bool|true|false
 *
 */
function is_registration_unable()
{

    $membership_data = is_array(is_membership_setting_available()) ? is_membership_setting_available() : "";

    $id_setting = isset($membership_data['ID']) ? $membership_data['ID'] : "";

    if (function_exists('sanitizer') && !empty($id_setting)) {
        $idsanitized = sanitizer($id_setting, 'sql');

        $is_registration_unabled = medoo_get_where("tbl_settings", ["setting_value"], ["ID" => $idsanitized]);

        if (empty($is_registration_unabled)) {
            return false;
        }

        $can_register = json_decode($is_registration_unabled['setting_value'], true);

        return (isset($can_register['user_can_register']) && $can_register['user_can_register'] == '1') ? true : false;
    }

    return false;
}

/**
 * membership_default_role
 *
 * @category function
 * @author Nirmala Khanza <nirmala.adiba.khanza@email.com>
 * @return mixed
 *
 */
function membership_default_role()
{
    $membership_data = is_array(is_membership_setting_available()) ? is_membership_setting_available() : "";

    $id_setting = isset($membership_data['ID']) ? $membership_data['ID'] : "";

    if (function_exists('sanitizer') && !empty($id_setting)) {
        $idsanitized = sanitizer($id_setting, 'sql');

        $retrieve_default_role = medoo_get_where("tbl_settings", ["setting_value"], ["ID" => $idsanitized]);

        if (empty($retrieve_default_role)) {
            return "";
        }

        $default_role = json_decode($retrieve_default_role['setting_value'], true);

        return isset($default_role['default_role']) ? $default_role['default_role'] : "";
    }

    return "";
}

/**
 * is_membership_setting_available
 *
 * @category function
 * @author Nirmala Khanza <nirmala.adiba.khanza@gmail.com>
 * @return mixed
 *
 */
function is_membership_setting_available()
{
    if (function_exists('medoo_get_where')) {
        $grab_setting = medoo_get_where("tbl_settings", ["ID", "setting_name", "setting_value"], ["setting_name" => "membership_setting"]);

        return is_iterable($grab_setting) ? $grab_setting : array();
    }
}
