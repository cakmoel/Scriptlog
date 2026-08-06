<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Shared pagination partial.
 *
 * Renders the Bootstrap pagination <nav>/<ul> wrapper around a
 * pre-built pagination HTML string. Emits nothing when the pagination
 * string is empty so list templates share one code path.
 *
 * Expected variables:
 *  - $pagination_html         string  pre-built pagination links HTML
 *  - $pagination_aria_label   string  accessible nav label (escaped here)
 *
 * @category Theme Partial
 * @package  Scriptlog
 * @author   Theme Remediation
 * @license  MIT
 * @version  1.0
 */

$pagination_html = isset($pagination_html) ? $pagination_html : '';
$pagination_aria_label = isset($pagination_aria_label) ? $pagination_aria_label : '';

if (!empty($pagination_html)) :
    ?>
    <nav aria-label="<?= theme_escape_html($pagination_aria_label); ?>">
        <ul class="pagination pagination-template d-flex justify-content-center">
            <?= $pagination_html; ?>
        </ul>
    </nav>
<?php endif; ?>
