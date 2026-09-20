<?php

/**
 * post_status_dropdown()
 *
 * Renders a post status select element.
 *
 * @category function
 * @param  string $selected
 * @param  bool   $includeScheduled Whether the 'Scheduled' option is included (admin only)
 * @return string
 */
function post_status_dropdown($selected = '', $includeScheduled = false)
{
    $posts_status = ['publish' => 'Publish', 'draft' => 'Draft'];

    if ($includeScheduled === true) {
        $posts_status['scheduled'] = 'Scheduled';
    }

    return dropdown('post_status', $posts_status, $selected);
}

/**
 * post_status_label()
 *
 * Human-readable label for a post status.
 *
 * @category function
 * @param  string $status
 * @return string
 */
function post_status_label($status)
{
    $labels = ['publish' => 'Publish', 'draft' => 'Draft', 'scheduled' => 'Scheduled'];

    return isset($labels[$status]) ? $labels[$status] : ucfirst((string)$status);
}

/**
 * comment_status_dropdown()
 *
 * Renders a comment status select element.
 *
 * @category function
 * @param  string $selected
 * @return string
 */
function comment_status_dropdown($selected = '')
{
    $comment_status = ['open' => 'Open', 'closed' => 'Closed'];

    return dropdown('comment_status', $comment_status, $selected);
}

/**
 * post_visibility_dropdown()
 *
 * Renders the post visibility select together with the password field
 * shown when the "protected" option is chosen.
 *
 * The toggling behaviour is provided by checkVisibilitySelection()
 * defined in the admin edit-post template.
 *
 * @category function
 * @param  string|null $selected
 * @return string
 */
function post_visibility_dropdown($selected = null)
{
    $name = 'visibility';

    $dropdown = '';
    $dropdown .= '<div class="form-group">';
    $dropdown .= '<label for="visibility">Post visibility</label>';
    $dropdown .= '<select name="' . $name . '" class="form-control" data-change="checkVisibilitySelection" id="visibility.system">' . PHP_EOL;

    $visibility_list = ['public' => 'Public', 'private' => 'Private', 'protected' => 'Protected'];

    foreach ($visibility_list as $key => $visibility) {
        $select = $selected === $key ? ' selected' : '';

        $dropdown .= '<option value="' . $key . '"' . $select . '>' . $visibility . '</option>' . PHP_EOL;
    }

    $dropdown .= '</select>' . PHP_EOL;

    $dropdown .= '<div id="protected" style="display:none">';
    $dropdown .= '<br />';
    $dropdown .= '<label for="protected">Password:</label>';
    $dropdown .= '<input type="password" class="form-control" name="post_password" value="" placeholder="Use a secure password">';
    $dropdown .= '<p class="help-block">Protected with a password you choose. Only those with the password can view this post.</p>';
    $dropdown .= '</div>';
    $dropdown .= '</div>';

    return $dropdown;
}

/**
 * post_locale_dropdown()
 *
 * Renders a post locale select element.
 *
 * @category function
 * @param  string $selected
 * @return string
 */
function post_locale_dropdown($selected = '')
{
    $locales = [
      'en' => 'English',
      'es' => 'Spanish',
      'fr' => 'French',
      'de' => 'German',
      'it' => 'Italian',
      'pt' => 'Portuguese',
      'ru' => 'Russian',
      'zh' => 'Chinese',
      'ja' => 'Japanese',
      'ko' => 'Korean',
      'ar' => 'Arabic',
      'hi' => 'Hindi',
      'id' => 'Indonesian',
      'ms' => 'Malay',
      'tr' => 'Turkish',
      'nl' => 'Dutch',
      'pl' => 'Polish',
      'vi' => 'Vietnamese',
      'th' => 'Thai',
      'he' => 'Hebrew'
    ];

    return dropdown('post_locale', $locales, $selected);
}
