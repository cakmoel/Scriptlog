<?php
function sidebar_navigation($module, $url, $user_id = null, $user_session = null)
{
    // Bootstrap admin locale from database default language setting (tbl_settings.lang_default)
    // Uses static cache to query DB at most once per request for performance
    static $localeBootstrapped = false;
    if (!$localeBootstrapped) {
        if (!isset($_SESSION['admin_locale']) && !isset($_COOKIE['admin_locale'])) {
            try {
                $configDao = new ConfigurationDao();
                $defaultLang = $configDao->findConfigByName('lang_default', new Sanitize());
                if (!empty($defaultLang['setting_value'])) {
                    $langCode = $defaultLang['setting_value'];
                    $availableLocales = ['en', 'ar', 'zh', 'fr', 'ru', 'es', 'id'];
                    if (in_array($langCode, $availableLocales)) {
                        admin_set_locale($langCode);
                    }
                }
            } catch (Throwable $e) {
                // Silently fall back to English
            }
        }
        $localeBootstrapped = true;
    }

    $currentLocale = admin_get_locale();
    $locales = admin_get_available_locales();
    $currentLangName = $locales[$currentLocale] ?? 'English';
    ?>
<aside class="main-sidebar" role="navigation" aria-label="<?= admin_translate('nav.navigation'); ?>">
    <section class="sidebar">
        <div class="user-panel"></div>

        <ul class="sidebar-menu" data-widget="tree">
            <li class="header"><?= admin_translate('nav.navigation'); ?></li>

            <li <?= ($module === 'dashboard') ? 'class="active"' : ''; ?>>
                <a href="<?= $url . '/' . generate_request('index.php', 'get', ['dashboard'], false)['link']; ?>"
                    <?= ($module === 'dashboard') ? 'aria-current="page"' : ''; ?>>
                    <i class="fa fa-dashboard fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.dashboard'); ?></span>
                </a>
            </li>

            <?php if (access_control_list(ActionConst::POSTS)) : ?>
            <li <?= ($module === 'posts' || $module === 'topics') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= ($module === 'posts' || $module === 'topics') ? 'true' : 'false'; ?>">
                    <i class="fa fa-thumb-tack fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.posts'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['posts'], false)['link']; ?>">
                            <?= admin_translate('nav.all_posts'); ?>
                        </a>
                    </li>
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['posts', ActionConst::NEWPOST, 0])['link']; ?>">
                            <?= admin_translate('nav.add_new'); ?>
                        </a>
                    </li>
                    <?php if (access_control_list(ActionConst::TOPICS)) : ?>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['topics'], false)['link']; ?>">
                            <?= admin_translate('nav.categories'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::MEDIALIB)) : ?>
            <li
                <?= ($module === 'medialib' || $module === 'downloads') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= ($module === 'medialib' || $module === 'downloads') ? 'true' : 'false'; ?>">
                    <i class="fa fa-image fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.media'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['medialib', ActionConst::MEDIALIB], false)['link']; ?>">
                            <?= admin_translate('nav.library'); ?>
                        </a>
                    </li>
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['medialib', ActionConst::NEWMEDIA, 0])['link']; ?>">
                            <?= admin_translate('nav.add_new'); ?>
                        </a>
                    </li>
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['downloads', ActionConst::DOWNLOADS], false)['link']; ?>">
                            <?= admin_translate('nav.downloads'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::PAGES)) : ?>
            <li <?= ($module === 'pages') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= ($module === 'pages') ? 'true' : 'false'; ?>">
                    <i class="fa fa-file fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.pages'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['pages', ActionConst::PAGES], false)['link']; ?>">
                            <?= admin_translate('nav.all_pages'); ?>
                        </a>
                    </li>
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['pages', ActionConst::NEWPAGE, 0])['link']; ?>">
                            <?= admin_translate('nav.add_new'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::COMMENTS)) : ?>
            <li <?= ($module === 'comments') ? 'class="active"' : ''; ?>>
                <a
                    href="<?= $url . '/' . generate_request('index.php', 'get', ['comments', ActionConst::COMMENTS], false)['link']; ?>"
                    <?= ($module === 'comments') ? 'aria-current="page"' : ''; ?>>
                    <i class="fa fa-comments" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.comments'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::IMPORT) || access_control_list(ActionConst::PRIVACY)) : ?>
            <li <?= ($module === 'import' || $module === 'export') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= ($module === 'import' || $module === 'export') ? 'true' : 'false'; ?>">
                    <i class="fa fa-wrench fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.tools'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <?php if (access_control_list(ActionConst::IMPORT)) : ?>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['import'], false)['link']; ?>">
                            <span><?= admin_translate('nav.import'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if (access_control_list(ActionConst::PRIVACY)) : ?>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['export'], false)['link']; ?>">
                            <span><?= admin_translate('nav.export'); ?></span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::USERS)) : ?>
            <li <?= ($module === 'users') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= ($module === 'users') ? 'true' : 'false'; ?>">
                    <i class="fa fa-user fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.users'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['users'], false)['link']; ?>">
                            <?= admin_translate('nav.all_users'); ?>
                        </a>
                    </li>
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['users', ActionConst::NEWUSER, 0, sha1(app_key())])['link']; ?>">
                            <?= admin_translate('nav.add_new'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php else : ?>
            <li <?= ($module === 'users') ? 'class="active"' : ''; ?>>
                <a
                    href="<?= generate_request('index.php', 'get', ['users', 'editUser', $user_id, $user_session])['link']; ?>"
                    <?= ($module === 'users') ? 'aria-current="page"' : ''; ?>>
                    <i class="fa fa-user" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.your_profile'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::THEMES)) : ?>
            <li
                <?= ($module === 'templates' || $module === 'menu') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= ($module === 'templates' || $module === 'menu') ? 'true' : 'false'; ?>">
                    <i class="fa fa-paint-brush fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.appearance'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['templates'], false)['link']; ?>">
                            <?= admin_translate('nav.themes'); ?>
                        </a>
                    </li>
                    <?php if (access_control_list(ActionConst::NAVIGATION)) : ?>
                    <li>
                        <a href="<?= $url . '/' . generate_request('index.php', 'get', ['menu'], false)['link']; ?>">
                            <?= admin_translate('nav.menus'); ?>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::CONFIGURATION)) : ?>
            <li <?= ($module === 'option-general' || $module === 'option-permalink' 
                || $module === 'option-reading' 
                || $module === 'option-timezone' 
                || $module === 'option-memberships' 
                || $module === 'option-api'
                || $module === 'option-mail'
                || $module === 'option-downloads') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= (in_array($module, ['option-general', 'option-permalink', 'option-reading', 'option-timezone', 'option-memberships', 'option-api', 'option-mail', 'option-downloads'])) ? 'true' : 'false'; ?>">
                    <i class="fa fa-sliders fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.settings'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-general', ActionConst::GENERAL_CONFIG, 0])['link']; ?>"><?= admin_translate('nav.general'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-reading', ActionConst::READING_CONFIG, 0])['link']; ?>"><?= admin_translate('nav.reading'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-permalink', ActionConst::PERMALINK_CONFIG, 0])['link']; ?>"><?= admin_translate('nav.permalink'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-timezone', ActionConst::TIMEZONE_CONFIG, 0])['link']; ?>"><?= admin_translate('nav.timezone'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-memberships', ActionConst::MEMBERSHIP_CONFIG, 0])['link']; ?>"><?= admin_translate('nav.membership'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-mail', ActionConst::MAIL_CONFIG, 0])['link']; ?>"><?= admin_translate('nav.mail_settings'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-downloads', ActionConst::DOWNLOAD_CONFIG, 0])['link']; ?>"><?= admin_translate('nav.download_settings'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-api', ActionConst::API_CONFIG, 0])['link']; ?>"><?= admin_translate('nav.api'); ?></a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::PLUGINS)) : ?>
            <li <?= ($module === 'plugins') ? 'class="active"' : ''; ?>>
                <a
                    href="<?= $url . '/' . generate_request('index.php', 'get', ['plugins', ActionConst::PLUGINS], false)['link']; ?>"
                    <?= ($module === 'plugins') ? 'aria-current="page"' : ''; ?>>
                    <i class="fa fa-plug fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.plugins'); ?></span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::PRIVACY)) : ?>
            <li
                <?= ($module === 'privacy' || $module === 'privacy-policy') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= ($module === 'privacy' || $module === 'privacy-policy') ? 'true' : 'false'; ?>">
                    <i class="fa fa-shield fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.privacy'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['privacy'], false)['link']; ?>"><?= admin_translate('nav.privacy_settings'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url; ?>/index.php?load=privacy&p=data-requests"><?= admin_translate('nav.data_requests'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url; ?>/index.php?load=privacy&p=audit-logs"><?= admin_translate('nav.audit_logs'); ?></a>
                    </li>
                    <li><a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['privacy-policy'], false)['link']; ?>"><?= admin_translate('nav.privacy_policy'); ?></a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>

            <?php if (access_control_list(ActionConst::CONFIGURATION)) : ?>
            <li
                <?= ($module === 'languages' || $module === 'translations' || $module === 'option-language') ? 'class="treeview active"' : 'class="treeview"'; ?>>
                <a href="#" aria-expanded="<?= ($module === 'languages' || $module === 'translations' || $module === 'option-language') ? 'true' : 'false'; ?>">
                    <i class="fa fa-globe fa-fw" aria-hidden="true"></i>
                    <span><?= admin_translate('nav.languages'); ?></span>
                    <span class="pull-right-container">
                        <i class="fa fa-angle-left pull-right" aria-hidden="true"></i>
                    </span>
                </a>
                <ul class="treeview-menu">
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['languages'], false)['link']; ?>">
                            <?= admin_translate('nav.all_languages'); ?>
                        </a>
                    </li>
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['translations'], false)['link']; ?>">
                            <?= admin_translate('nav.translations'); ?>
                        </a>
                    </li>
                    <li>
                        <a
                            href="<?= $url . '/' . generate_request('index.php', 'get', ['option-language'], false)['link']; ?>">
                            <?= admin_translate('nav.language_config'); ?>
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-lang-indicator" role="complementary" aria-label="<?= admin_translate('nav.language_settings'); ?>">
            <a href="<?= $url . '/' . generate_request('index.php', 'get', ['option-language'], false)['link']; ?>"
               class="sidebar-lang-link"
               aria-label="<?= admin_translate('nav.language_settings') . ' - ' . $currentLangName; ?>"
               title="<?= admin_translate('nav.language_settings'); ?>">
                <span class="sidebar-lang-icon" aria-hidden="true">&#127758;</span>
                <span class="sidebar-lang-code"><?= safe_html(strtoupper($currentLocale)); ?></span>
                <span class="sidebar-lang-name"><?= safe_html($currentLangName); ?></span>
            </a>
        </div>
    </section>
</aside>

<style>
.sidebar-lang-indicator {
    border-top: 1px solid rgba(255,255,255,0.08);
    padding: 0;
    margin-top: 4px;
    transition: border-color 0.2s ease;
}

.sidebar-lang-indicator:hover {
    border-top-color: rgba(255,255,255,0.18);
}

.sidebar-lang-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 15px 10px 18px;
    color: rgba(255,255,255,0.55);
    text-decoration: none;
    font-size: 12px;
    transition: all 0.2s ease;
    border-radius: 0;
    outline-offset: -2px;
}

.sidebar-lang-link:hover,
.sidebar-lang-link:focus {
    color: rgba(255,255,255,0.9);
    background: rgba(255,255,255,0.05);
}

.sidebar-lang-link:focus-visible {
    outline: 2px solid rgba(255,255,255,0.4);
    outline-offset: -2px;
}

.sidebar-lang-icon {
    font-size: 14px;
    line-height: 1;
    flex-shrink: 0;
}

.sidebar-lang-code {
    font-weight: 600;
    letter-spacing: 0.5px;
    font-size: 11px;
    text-transform: uppercase;
    flex-shrink: 0;
}

.sidebar-lang-name {
    font-weight: 400;
    font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    opacity: 0.7;
    transition: opacity 0.2s ease;
}

.sidebar-lang-link:hover .sidebar-lang-name {
    opacity: 1;
}

/* When sidebar is collapsed (mini mode) - show only icon + code */
.sidebar-mini.sidebar-collapse .sidebar-lang-name {
    display: none;
}

.sidebar-mini.sidebar-collapse .sidebar-lang-link {
    justify-content: center;
    padding: 10px 5px;
}

.sidebar-mini.sidebar-collapse .sidebar-lang-code {
    display: none;
}

.sidebar-mini.sidebar-collapse .sidebar-lang-indicator:hover .sidebar-lang-name {
    display: inline;
    position: absolute;
    left: 50px;
    background: rgba(0,0,0,0.9);
    padding: 4px 8px;
    border-radius: 3px;
    white-space: nowrap;
    z-index: 1000;
    opacity: 1;
}

/* RTL support for language indicator */
body:not(.sidebar-mini) .sidebar-lang-indicator,
body:not(.sidebar-collapse) .sidebar-lang-indicator {
    direction: inherit;
}

/* Reduced motion preference */
@media (prefers-reduced-motion: reduce) {
    .sidebar-lang-link,
    .sidebar-lang-indicator,
    .sidebar-lang-name {
        transition: none;
    }
}

/* Dark mode compatibility for sidebar */
@media (prefers-color-scheme: dark) {
    .sidebar-lang-indicator {
        border-top-color: rgba(255,255,255,0.06);
    }
}
</style>
<?php
}
