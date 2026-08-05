<?php

/**
 * Theme_dir()
 *
 * checking which is theme actived and return it directory with app's URL
 *
 * @category function theme_dir checking active theme and return it directory with app's URL
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return string
 *
 */
function theme_dir()
{

    $directory = &theme_dir_cache();

    if ($directory !== null) {
        return $directory;
    }

    $active_theme = theme_identifier();

    $directory = (isset($active_theme['theme_directory'])) ? app_url() . DIRECTORY_SEPARATOR . APP_THEME . $active_theme['theme_directory'] . DIRECTORY_SEPARATOR : "";

    return $directory;
}

/**
 * theme_dir_cache()
 *
 * Request-scoped holder for the memoized theme directory value.
 * Accessed by reference so tests can reset it between cases.
 *
 * @category functions
 * @author M.Noermoehammad
 * @return mixed
 *
 */
function &theme_dir_cache()
{
    static $directory = null;
    return $directory;
}

/**
 * reset_theme_dir_cache()
 *
 * Forget the memoized theme directory (used by tests and after theme changes).
 *
 * @category functions
 * @return void
 *
 */
function reset_theme_dir_cache()
{
    $cache = &theme_dir_cache();
    $cache = null;
}

/**
 * theme_identifier()
 *
 * initialize theme actived
 *
 * @category functions
 * @author M.Noermoehammad
 *
 */
function theme_identifier()
{

    $state = &theme_identifier_cache();

    if ($state['resolved']) {
        return $state['theme'];
    }

    $theme_init = class_exists('ThemeDao') ? new ThemeDao() : "";

    $loaded = ($theme_init instanceof ThemeDao) ? $theme_init->loadTheme('Y') : "";

    $state['theme'] = empty($loaded) ? "" : $loaded;
    $state['resolved'] = true;

    return $state['theme'];
}

/**
 * theme_identifier_cache()
 *
 * Request-scoped holder for the memoized active-theme row.
 * Accessed by reference so tests can reset it between cases.
 *
 * @category functions
 * @return array{theme: mixed, resolved: bool}
 *
 */
function &theme_identifier_cache()
{
    static $state = ['theme' => null, 'resolved' => false];
    return $state;
}

/**
 * reset_theme_identifier_cache()
 *
 * Forget the memoized active theme (used by tests and after theme changes).
 *
 * @category functions
 * @return void
 *
 */
function reset_theme_identifier_cache()
{
    $state = &theme_identifier_cache();
    $state['theme'] = null;
    $state['resolved'] = false;
}

/**
 * call_theme_header
 *
 * @category functions
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
function call_theme_header()
{

    if (file_exists(APP_ROOT . APP_THEME . theme_identifier()['theme_directory'] . DIRECTORY_SEPARATOR . 'header.php')) {
        include_once APP_ROOT . APP_THEME . theme_identifier()['theme_directory'] . DIRECTORY_SEPARATOR . 'header.php';
    } else {
        scriptlog_error("File header not found");
    }
}

/**
 * call_theme_content
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @param string $content
 *
 */
function call_theme_content($content = null)
{

    if (file_exists(APP_ROOT . APP_THEME . theme_identifier()['theme_directory'] . DIRECTORY_SEPARATOR . basename($content . '.php'))) {
        include_once APP_ROOT . APP_THEME . theme_identifier()['theme_directory'] . DIRECTORY_SEPARATOR . basename($content . '.php');
    } else {
        scriptlog_error("File content not found");
    }
}

/**
 * call_theme_footer
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 *
 */
function call_theme_footer()
{

    if (file_exists(APP_ROOT . APP_THEME . theme_identifier()['theme_directory'] . DIRECTORY_SEPARATOR . 'footer.php')) {
        include_once APP_ROOT . APP_THEME . theme_identifier()['theme_directory'] . DIRECTORY_SEPARATOR . 'footer.php';
    } else {
        scriptlog_error("File footer not found");
    }
}
