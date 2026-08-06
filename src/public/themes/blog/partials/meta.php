<?php
defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * Shared post meta partial (author / date / comments row).
 *
 * Renders the post-footer metadata row used by every post card. All
 * string inputs are expected to be already escaped (see
 * prepare_post_card()); this partial only prints them. Escaping happens
 * exactly once, at the normalization boundary.
 *
 * Expected variables (already escaped):
 *  - $meta_author    string  post author display name
 *  - $meta_date      string  formatted, escaped post date
 *  - $meta_comments  int     approved comment count
 *
 * @category Theme Partial
 * @package  Scriptlog
 * @author   Theme Remediation
 * @license  MIT
 * @version  1.0
 */

$meta_author = isset($meta_author) ? $meta_author : '';
$meta_date = isset($meta_date) ? $meta_date : '';
$meta_comments = isset($meta_comments) ? (int)$meta_comments : 0;
?>
<footer class="post-footer d-flex align-items-center">
    <a href="javascript:void(0)" class="author d-flex align-items-center flex-wrap">
        <div class="title"><span><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $meta_author; ?></span></div>
    </a>
    <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i> <?= $meta_date; ?></div>
    <div class="comments meta-last"><i class="icon-comment" aria-hidden="true"></i><?= $meta_comments; ?></div>
</footer>
