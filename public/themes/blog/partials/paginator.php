<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Shared pagination partial.
 *
 * Wraps the pre-built pagination HTML (Paginator::page_links() emits the
 * complete <ul class="pagination ..."> list) in a labelled <nav> landmark so
 * list templates share one code path and one accessible navigation region.
 * The list markup itself is never re-wrapped in a second <ul>: nesting a
 * pagination list inside another list is invalid HTML. Emits nothing when
 * the pagination string is empty so list templates share one code path.
 *
 * Expected variables:
 *  - $pagination_html         string  pre-built pagination list HTML (a <ul>)
 *  - $pagination_aria_label   string  accessible nav label (escaped here)
 *
 * @category Theme Partial
 * @package  Scriptlog
 * @author   Theme Remediation
 * @license  MIT
 * @version  1.1
 */

$pagination_html = isset($pagination_html) ? $pagination_html : '';
$pagination_aria_label = isset($pagination_aria_label) ? $pagination_aria_label : '';

if (!empty($pagination_html)) :
    ?>
    <nav aria-label="<?= theme_escape_html($pagination_aria_label); ?>">
        <?= $pagination_html; ?>
    </nav>
<?php endif; ?>
