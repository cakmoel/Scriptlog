<?php
function sidebar_navigation($module, $url, $user_id = null, $user_session = null)
{
?>
<aside class="main-sidebar">
    <section class="sidebar">
        <div class="user-panel"></div>

        <ul class="sidebar-menu" data-widget="tree">
            <li class="header"><?= admin_translate('nav'); ?></li>

            <li <?= ($module === 'dashboard') ? 'class="active"' : ''; ?>>
                <a href="<?= $url . '/' . generate_request('index.php', 'get', ['dashboard'], false)['link']; ?>">
                    <i class="fa fa-dashboard fa-fw"></i>
                    <span><?= admin_translate('dashboard'); ?></span>
                </a>
            </li>

            <?php if (access_control_list(ActionConst::POSTS)) : ?>
            <li <?= ($module === 'posts' || $module === 'topics') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-thumb-tack fa-fw"></i>
                    <span><?= admin_translate('posts'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['posts'], false)['link']; ?>">
                            <?= admin_translate('allPosts'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['posts', ActionConst::NEWPOST, 0])['link']; ?>">
                            <?= admin_translate('addNew'); ?>
                        </a>
                    </li>
                    <?php if (access_control_list(ActionConst::TOPICS)) : ?>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['topics'], false)['link']; ?>">
                            <?= admin_translate('categories'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::MEDIALIB)) : ?>
            <li <?= ($module === 'medialib' || $module === 'downloads') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-image fa-fw"></i>
                    <span><?= admin_translate('media'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['medialib', ActionConst::MEDIALIB], false)['link']; ?>">
                            <?= admin_translate('library'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['medialib', ActionConst::NEWMEDIA, 0])['link']; ?>">
                            <?= admin_translate('addNew'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['downloads', ActionConst::DOWNLOADS], false)['link']; ?>">
                            <?= admin_translate('downloads'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::PAGES)) : ?>
            <li <?= ($module === 'pages') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-file fa-fw"></i>
                    <span><?= admin_translate('pages'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['pages', ActionConst::PAGES], false)['link']; ?>">
                            <?= admin_translate('allPages'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['pages', ActionConst::NEWPAGE, 0])['link']; ?>">
                            <?= admin_translate('addNew'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::COMMENTS)) : ?>
            <li <?= ($module === 'comments') ? 'class="active"' : ''; ?>>
                <a href="<?= $url . '/' . generate_request('index.php', 'get', ['comments', ActionConst::COMMENTS], false)['link']; ?>">
                    <i class="fa fa-comments"></i>
                    <span><?= admin_translate('comments'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::IMPORT) || access_control_list(ActionConst::PRIVACY)) : ?>
            <li <?= ($module === 'import' || $module === 'export') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-wrench fa-fw"></i>
                    <span><?= admin_translate('tools'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <?php if (access_control_list(ActionConst::IMPORT)) : ?>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['import'], false)['link']; ?>">
                            <span><?= admin_translate('import'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (access_control_list(ActionConst::PRIVACY)) : ?>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['export'], false)['link']; ?>">
                            <span><?= admin_translate('export'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::USERS)) : ?>
            <li <?= ($module === 'users') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-user fa-fw"></i>
                    <span><?= admin_translate('users'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['users'], false)['link']; ?>">
                            <?= admin_translate('allUsers'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['users', ActionConst::NEWUSER, 0, sha1(app_key())])['link']; ?>">
                            <?= admin_translate('addNew'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php else : ?>
            <li <?= ($module === 'users') ? 'class="active"' : ''; ?>>
                <a href="<?= generate_request('index.php', 'get', ['users', 'editUser', $user_id, $user_session])['link']; ?>">
                    <i class="fa fa-user"></i>
                    <span><?= admin_translate('myProfile'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::THEMES)) : ?>
            <li <?= ($module === 'templates' || $module === 'menu') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-paint-brush fa-fw"></i>
                    <span><?= admin_translate('appearance'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['templates'], false)['link']; ?>">
                            <?= admin_translate('themes'); ?>
                        </a>
                    </li>
                    <?php if (access_control_list(ActionConst::NAVIGATION)) : ?>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['menu'], false)['link']; ?>">
                            <?= admin_translate('menus'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::CONFIGURATION)) : ?>
            <li <?= ($module === 'option-general' || $module === 'option-permalink' || $module === 'option-reading' || $module === 'option-timezone' || $module === 'option-memberships') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-sliders fa-fw"></i>
                    <span><?= admin_translate('settings'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li><a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-general', ActionConst::GENERAL_CONFIG, 0])['link']; ?>"><?= admin_translate('general'); ?></a></li>
                    <li><a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-reading', ActionConst::READING_CONFIG, 0])['link']; ?>"><?= admin_translate('reading'); ?></a></li>
                    <li><a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-permalink', ActionConst::PERMALINK_CONFIG, 0])['link']; ?>"><?= admin_translate('permalink'); ?></a></li>
                    <li><a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-timezone', ActionConst::TIMEZONE_CONFIG, 0])['link']; ?>"><?= admin_translate('timezone'); ?></a></li>
                    <li><a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-memberships', ActionConst::MEMBERSHIP_CONFIG, 0])['link']; ?>"><?= admin_translate('membership'); ?></a></li>
                    <li><a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-mail', ActionConst::MAIL_CONFIG, 0])['link']; ?>"><?= admin_translate('mailSettings'); ?></a></li>
                    <li><a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-downloads', ActionConst::DOWNLOAD_CONFIG, 0])['link']; ?>"><?= admin_translate('downloadSettings'); ?></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::PLUGINS)) : ?>
            <li <?= ($module === 'plugins') ? 'class="active"' : ''; ?>>
                <a href="<?= $url . '/' . generate_request('index.php', 'get', ['plugins', ActionConst::PLUGINS], false)['link']; ?>">
                    <i class="fa fa-plug fa-fw"></i>
                    <span><?= admin_translate('plugins'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::PRIVACY)) : ?>
            <li <?= ($module === 'privacy') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-shield fa-fw"></i>
                    <span><?= admin_translate('privacy'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li><a href="<?= $url . '/' . generate_request('index.php', 'get', ['privacy'], false)['link']; ?>"><?= admin_translate('privacySettings'); ?></a></li>
                    <li><a href="<?= $url; ?>/index.php?load=privacy&p=data-requests"><?= admin_translate('dataRequests'); ?></a></li>
                    <li><a href="<?= $url; ?>/index.php?load=privacy&p=audit-logs"><?= admin_translate('auditLogs'); ?></a></li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::CONFIGURATION)) : ?>
            <li <?= ($module === 'languages' || $module === 'translations' || $module === 'option-language') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#">
                    <i class="fa fa-globe fa-fw"></i>
                    <span><?= admin_translate('languages'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['languages'], false)['link']; ?>">
                            <?= admin_translate('allLanguages'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['translations'], false)['link']; ?>">
                            <?= admin_translate('translations'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-language'], false)['link']; ?>">
                            <?= admin_translate('languageConfig'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
    </section>
</aside>
<?php
}
