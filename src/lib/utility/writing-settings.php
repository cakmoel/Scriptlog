<?php

/**
 * scheduled_post_enabled()
 *
 * Whether scheduled posting is globally enabled.
 *
 * Used by the admin post editor to decide whether the 'Scheduled' status
 * option is offered to administrators. Defaults to enabled when the setting
 * row is missing (new installs and existing blogs before first save).
 *
 * @category function
 * @author   Scriptlog
 * @license  MIT
 * @version  1.0
 * @return bool
 */
function scheduled_post_enabled()
{
    if (!class_exists('Scriptlog\Dao\ConfigurationDao') || !class_exists('Scriptlog\Core\Sanitize')) {
        return true;
    }

    try {
        $configDao = new Scriptlog\Dao\ConfigurationDao();
        $sanitize = new Scriptlog\Core\Sanitize();
        $setting = $configDao->findConfigByName('writing_scheduled_post_enabled', $sanitize);

        if (empty($setting) || !isset($setting['setting_value']) || $setting['setting_value'] === '') {
            return true;
        }

        return (string)$setting['setting_value'] === '1';
    } catch (\Throwable $e) {
        return true;
    }
}
