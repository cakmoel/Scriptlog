<?php

/**
 * admin_query
 *
 * whitelist query allowed in admin directory
 *
 * @category function
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * @return array
 *
 */
function admin_query()
{

    return array(
        'dashboard' => 'dashboard.php',
        'posts' => 'posts.php',
        'medialib' => 'medialib.php',
        'media-upload' => 'media-upload.php',
        'downloads' => 'downloads.php',
        'pages' => 'pages.php',
        'topics' => 'topics.php',
        'comments' => 'comments.php',
        'reply' => 'reply.php',
        'import' => 'import.php',
        'export' => 'export.php',
        'templates' => 'templates.php',
        'menu' => 'menu.php',
        'users' => 'users.php',
        'option-general' => 'option-general.php',
        'option-permalink' => 'option-permalink.php',
        'option-reading' => 'option-reading.php',
        'option-timezone' => 'option-timezone.php',
        'option-memberships' => 'option-memberships.php',
        'option-downloads' => 'option-downloads.php',
        'option-mail' => 'option-mail.php',
        'option-api' => 'option-api.php',
        'option-language' => 'option-language.php',
        'plugins' => 'plugins.php',
        'privacy' => 'privacy.php',
        'privacy-policy' => 'privacy-policy.php',
        'languages' => 'languages.php',
        'translations' => 'translations.php',
        'logout' => 'logout.php',
        '403' => '403.php',
        '404' => '404.php'
    );
}
