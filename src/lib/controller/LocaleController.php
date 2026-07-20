<?php

namespace Scriptlog\Controller;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class LocaleController
 *
 * Handles frontend language switching with HTMX support.
 *
 * @category Controller
 * @author   Scriptlog
 * @license  MIT
 * @version  1.0
 */

class LocaleController
{
    /**
     * Switch locale — handles POST from language switcher.
     */
    public function switch(): void
    {
        $locale = isset($_POST['locale']) ? trim($_POST['locale']) : (isset($_GET['locale']) ? trim($_GET['locale']) : '');
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_GET['redirect']) ? $_GET['redirect'] : '/');

        $available = function_exists('available_locales') ? available_locales() : ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];

        if (!in_array($locale, $available, true)) {
            $locale = 'en';
        }

        if (function_exists('set_locale')) {
            set_locale($locale);
        } else {
            $_SESSION['scriptlog_locale'] = $locale;
            setcookie('scriptlog_locale', $locale, time() + 86400 * 365, '/', '', false, true);
        }

        if (is_htmx_request()) {
            http_response_code(200);
            header('HX-Trigger: {"localeChanged": "' . $locale . '"}');
            echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') . '">';
            return;
        }

        direct_page($redirect, 302);
    }
}
