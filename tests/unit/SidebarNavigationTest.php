<?php
/**
 * SidebarNavigationTest
 *
 * Unit tests for sidebar_navigation() in admin/sidebar-nav.php.
 *
 * Tests HTML structure, active module highlighting, permission-based
 * section visibility, language indicator, and user profile link.
 *
 * Uses separate PHP subprocesses to stub global dependencies
 * (access_control_list, admin_translate, generate_request, etc.)
 * so no database or application bootstrap is required.
 *
 * @category   UnitTests
 * @version    1.0.0
 */

use PHPUnit\Framework\TestCase;

if (!defined('SCRIPTLOG')) {
    define('SCRIPTLOG', hash_hmac('sha256', 'test', 'test'));
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

class SidebarNavigationTest extends TestCase
{
    /**
     * Build a PHP snippet that stubs all functions required by sidebar_navigation()
     * and returns the captured HTML output.
     */
    private function buildSnippet(
        string $module = 'dashboard',
        array $permissions = [],
        string $locale = 'en',
        string $baseUrl = '/admin',
        ?int $userId = null,
        ?string $userSession = null
    ): string {
        $sidebarFile = var_export(realpath(__DIR__ . '/../../admin/sidebar-nav.php'), true);

        $defaultPerms = [
            'posts' => true,
            'topics' => true,
            'medialib' => true,
            'downloads' => true,
            'pages' => true,
            'comments' => true,
            'import' => true,
            'privacy' => true,
            'users' => true,
            'themes' => true,
            'navigation' => true,
            'configuration' => true,
            'plugins' => true,
        ];

        $perms = array_merge($defaultPerms, $permissions);
        $permsCode = var_export($perms, true);
        $userIdVal = var_export($userId, true);
        $userSessionVal = var_export($userSession, true);

        return <<<PHP
<?php
define('SCRIPTLOG', 'test');
define('DS', DIRECTORY_SEPARATOR);

\$__perms = {$permsCode};

function access_control_list(\$action = null) {
    global \$__perms;
    if (\$action === null) return false;
    return isset(\$__perms[\$action]) ? \$__perms[\$action] : false;
}

function safe_html(\$data) {
    return htmlspecialchars((string)\$data, ENT_QUOTES, 'UTF-8', false);
}

function admin_translate(string \$key, ?string \$locale = null): string {
    \$map = [
        'nav.navigation' => 'Navigation',
        'nav.dashboard' => 'Dashboard',
        'nav.posts' => 'Posts',
        'nav.all_posts' => 'All Posts',
        'nav.add_new' => 'Add New',
        'nav.categories' => 'Categories',
        'nav.media' => 'Media',
        'nav.library' => 'Library',
        'nav.downloads' => 'Downloads',
        'nav.pages' => 'Pages',
        'nav.all_pages' => 'All Pages',
        'nav.comments' => 'Comments',
        'nav.tools' => 'Tools',
        'nav.import' => 'Import',
        'nav.export' => 'Export',
        'nav.users' => 'Users',
        'nav.all_users' => 'All Users',
        'nav.your_profile' => 'Your Profile',
        'nav.appearance' => 'Appearance',
        'nav.themes' => 'Themes',
        'nav.menus' => 'Menus',
        'nav.settings' => 'Settings',
        'nav.general' => 'General',
        'nav.reading' => 'Reading',
        'nav.permalink' => 'Permalink',
        'nav.timezone' => 'Timezone',
        'nav.membership' => 'Membership',
        'nav.mail_settings' => 'Mail Settings',
        'nav.download_settings' => 'Download Settings',
        'nav.api' => 'API',
        'nav.plugins' => 'Plugins',
        'nav.privacy' => 'Privacy',
        'nav.privacy_settings' => 'Privacy Settings',
        'nav.data_requests' => 'Data Requests',
        'nav.audit_logs' => 'Audit Logs',
        'nav.privacy_policy' => 'Privacy Policy',
        'nav.languages' => 'Languages',
        'nav.all_languages' => 'All Languages',
        'nav.translations' => 'Translations',
        'nav.language_config' => 'Language Config',
        'nav.language_settings' => 'Language Settings',
    ];
    return \$map[\$key] ?? \$key;
}

function admin_get_locale(): string {
    return \$_SESSION['admin_locale'] ?? 'en';
}

function admin_set_locale(string \$locale): void {
    \$_SESSION['admin_locale'] = \$locale;
}

function admin_get_available_locales(): array {
    return [
        'en' => 'English', 'ar' => 'العربية', 'zh' => '中文',
        'fr' => 'Français', 'ru' => 'Русский', 'es' => 'Español',
        'id' => 'Bahasa Indonesia',
    ];
}

function admin_is_rtl(): bool {
    return in_array(admin_get_locale(), ['ar']);
}

function generate_request(\$base, \$type, \$data = [], \$encoded = true) {
    \$load = isset(\$data[0]) ? rawurlencode((string)\$data[0]) : '';
    \$action = isset(\$data[1]) ? urlencode((string)\$data[1]) : null;
    \$id = isset(\$data[2]) ? abs((int)\$data[2]) : null;
    \$unique_id = isset(\$data[3]) ? sanitize_urls((string)\$data[3]) : null;
    if (\$encoded) {
        if (\$load === 'users') {
            return ['link' => \$base . '?load=' . \$load . '&action=' . \$action . '&Id=' . \$id . '&sessionId=' . \$unique_id];
        }
        return ['link' => \$base . '?load=' . \$load . '&action=' . \$action . '&Id=' . \$id];
    }
    return ['link' => \$base . '?load=' . \$load];
}

function sanitize_urls(\$data) { return strip_tags((string)\$data); }

function build_query(\$base, \$data) {
    \$parts = [];
    foreach (\$data as \$k => \$v) { \$parts[] = \$k . '=' . urlencode((string)\$v); }
    return \$base . '?' . implode('&', \$parts);
}

function check_request_generated() {}

function app_key(): string {
    return 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX';
}

// Stub ActionConst class (sidebar-nav.php references these constants)
class ActionConst {
    public const DASHBOARD  = "dashboard";
    public const DETAILITEM = "detailItem";
    public const CONFIGURATION     = "configuration";
    public const GENERAL_CONFIG    = "generalConfig";
    public const PERMALINK_CONFIG  = "permalinkConfig";
    public const READING_CONFIG    = "readingConfig";
    public const TIMEZONE_CONFIG   = "timezoneConfig";
    public const MEMBERSHIP_CONFIG = "membershipConfig";
    public const MAIL_CONFIG       = "mailConfig";
    public const POSTS       = "posts";
    public const NEWPOST     = "newPost";
    public const EDITPOST    = "editPost";
    public const DELETEPOST  = "deletePost";
    public const PAGES       = "pages";
    public const NEWPAGE     = "newPage";
    public const EDITPAGE    = "editPage";
    public const DELETEPAGE  = "deletePage";
    public const TOPICS      = "topics";
    public const NEWTOPIC    = "newTopic";
    public const EDITTOPIC   = "editTopic";
    public const DELETETOPIC = "deleteTopic";
    public const COMMENTS      = "comments";
    public const EDITCOMMENT   = "editComment";
    public const DELETECOMMENT = "deleteComment";
    public const REPLY         = "reply";
    public const EDITREPLY     = "editReply";
    public const DELETEREPLY   = "deleteReply";
    public const NAVIGATION  = "navigation";
    public const NEWMENU     = "newMenu";
    public const EDITMENU    = "editMenu";
    public const DELETEMENU  = "deleteMenu";
    public const NEWSUBMENU    = "newSubMenu";
    public const EDITSUBMENU   = "editSubMenu";
    public const DELETESUBMENU = "deleteSubMenu";
    public const MEDIALIB      = "medialib";
    public const NEWMEDIA      = "newMedia";
    public const EDITMEDIA     = "editMedia";
    public const DELETEMEDIA   = "deleteMedia";
    public const PLUGINS          = "plugins";
    public const INSTALLPLUGIN    = "installPlugin";
    public const ACTIVATEPLUGIN   = "activatePlugin";
    public const DEACTIVATEPLUGIN = "deactivatePlugin";
    public const DELETEPLUGIN     = "deletePlugin";
    public const THEMES        = "themes";
    public const NEWTHEME      = "newTheme";
    public const INSTALLTHEME  = "installTheme";
    public const ACTIVATETHEME = "activateTheme";
    public const DEACTIVATETHEME = "deactivateTheme";
    public const EDITTHEME     = "editTheme";
    public const DELETETHEME   = "deleteTheme";
    public const USERS      = "users";
    public const NEWUSER    = "newUser";
    public const EDITUSER   = "editUser";
    public const DELETEUSER = "deleteUser";
    public const LOGOUT = "doLogOut";
    public const IMPORT = "import";
    public const PRIVACY = "privacy";
    public const DATA_REQUESTS = "dataRequests";
    public const AUDIT_LOGS = "auditLogs";
    public const LANGUAGES     = "languages";
    public const NEWLANGUAGE   = "newLanguage";
    public const EDITLANGUAGE  = "editLanguage";
    public const DELETELANGUAGE = "deleteLanguage";
    public const TRANSLATIONS   = "translations";
    public const NEWTRANSLATION = "newTranslation";
    public const EDITTRANSLATION = "editTranslation";
    public const DELETETRANSLATION = "deleteTranslation";
    public const LANGUAGE_CONFIG = "languageConfig";
    public const DOWNLOADS = "downloads";
    public const DOWNLOAD_CONFIG = "downloadConfig";
    public const DELETEDOWNLOAD = "deleteDownload";
    public const API_CONFIG = "apiConfig";
}

\$_SESSION['admin_locale'] = '{$locale}';

require_once {$sidebarFile};

ob_start();
sidebar_navigation('{$module}', '{$baseUrl}', {$userIdVal}, {$userSessionVal});
\$output = ob_get_clean();
echo \$output;
PHP;
    }

    /**
     * Write snippet to temp file, execute in child process, return output.
     */
    private function runSnippet(string $snippet): string
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sbnav_') . '.php';
        file_put_contents($tmpFile, $snippet);
        $output = shell_exec('php ' . escapeshellarg($tmpFile) . ' 2>/dev/null');
        @unlink($tmpFile);
        $this->assertIsString($output, 'PHP subprocess must produce string output');
        $this->assertNotEmpty(trim($output), 'Rendered sidebar output must not be empty');
        return $output;
    }



    // -----------------------------------------------------------------------
    // Basic HTML Structure
    // -----------------------------------------------------------------------

    /**
     * Verify the outer HTML skeleton is rendered: <aside>, sidebar-menu, header, closing tag.
     */
    public function testBasicHtmlStructure(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $this->assertStringContainsString('<aside', $html);
        $this->assertStringContainsString('sidebar-menu', $html);
        $this->assertStringContainsString('Navigation', $html);
        $this->assertStringContainsString('</aside>', $html);
    }

    // -----------------------------------------------------------------------
    // Active state highlighting
    // -----------------------------------------------------------------------

    /**
     * Dashboard menu item receives active class when module equals 'dashboard'.
     */
    public function testDashboardIsActive(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $this->assertStringContainsString('class="active"', $html);
        $this->assertMatchesRegularExpression(
            '/class="active"[^>]*>.*Dashboard/s',
            $html
        );
    }

    /**
     * Posts section receives treeview active when module equals 'posts'.
     */
    public function testPostsTreeviewActive(): void
    {
        $html = $this->runSnippet($this->buildSnippet('posts'));
        $this->assertStringContainsString('class="treeview active"', $html);
        $this->assertMatchesRegularExpression(
            '/class="treeview active"[^>]*>.*Posts/s',
            $html
        );
    }

    /**
     * Comments menu item receives active class when module equals 'comments'.
     */
    public function testCommentsIsActive(): void
    {
        $html = $this->runSnippet($this->buildSnippet('comments'));
        $this->assertStringContainsString('class="active"', $html);
        $this->assertMatchesRegularExpression(
            '/class="active"[^>]*>.*Comments/s',
            $html
        );
    }

    /**
     * Media section receives treeview active when module equals 'medialib'.
     */
    public function testMediaTreeviewActive(): void
    {
        $html = $this->runSnippet($this->buildSnippet('medialib'));
        $this->assertMatchesRegularExpression(
            '/class="treeview active"[^>]*>.*Media/s',
            $html
        );
    }

    /**
     * Modules 'option-general', 'option-permalink', etc. trigger active on Settings.
     */
    public function testSettingsTreeviewActive(): void
    {
        $modules = ['option-general', 'option-permalink', 'option-reading',
                     'option-timezone', 'option-memberships', 'option-api',
                     'option-mail', 'option-downloads'];
        foreach ($modules as $mod) {
            $html = $this->runSnippet($this->buildSnippet($mod));
            $this->assertMatchesRegularExpression(
                '/class="treeview active"[^>]*>.*Settings/s',
                $html,
                "Module '$mod' should activate the Settings treeview"
            );
        }
    }

    /**
     * Pages section receives treeview active when module equals 'pages'.
     */
    public function testPagesTreeviewActive(): void
    {
        $html = $this->runSnippet($this->buildSnippet('pages'));
        $this->assertMatchesRegularExpression(
            '/class="treeview active"[^>]*>.*Pages/s',
            $html
        );
    }

    /**
     * Users section receives treeview active when module equals 'users'.
     */
    public function testUsersTreeviewActive(): void
    {
        $html = $this->runSnippet($this->buildSnippet('users'));
        $this->assertMatchesRegularExpression(
            '/class="treeview active"[^>]*>.*Users/s',
            $html
        );
    }

    // -----------------------------------------------------------------------
    // Permission-based section visibility
    // -----------------------------------------------------------------------

    /**
     * With all permissions granted, all major sections should appear.
     */
    public function testAllSectionsRenderWithFullPermissions(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $sections = ['Dashboard', 'Posts', 'Media', 'Pages', 'Comments',
                      'Tools', 'Users', 'Appearance', 'Settings', 'Plugins',
                      'Privacy', 'Languages'];
        foreach ($sections as $section) {
            $this->assertStringContainsString(
                $section,
                $html,
                "Section '$section' should appear when user has all permissions"
            );
        }
    }

    /**
     * When POSTS permission is denied, the Posts section must be absent.
     */
    public function testPostsSectionHiddenWithoutPermission(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard', ['posts' => false]));
        $this->assertStringNotContainsString('All Posts', $html);
        $this->assertStringNotContainsString('Categories', $html);
    }

    /**
     * When MEDIALIB permission is denied, Media section must be absent.
     */
    public function testMediaSectionHiddenWithoutPermission(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard', ['medialib' => false]));
        $this->assertStringNotContainsString('Library', $html);
        $this->assertStringNotContainsString('Downloads', $html);
    }

    /**
     * When PAGES permission is denied, Pages section must be absent.
     */
    public function testPagesSectionHiddenWithoutPermission(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard', ['pages' => false]));
        $this->assertStringNotContainsString('All Pages', $html);
    }

    /**
     * When COMMENTS permission is denied, Comments menu must be absent.
     */
    public function testCommentsSectionHiddenWithoutPermission(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard', ['comments' => false]));
        $this->assertStringNotContainsString('Comments', $html);
    }

    /**
     * When CONFIGURATION permission is denied, Settings section must be absent.
     */
    public function testSettingsSectionHiddenWithoutPermission(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard', ['configuration' => false]));
        $this->assertStringNotContainsString('General', $html);
        $this->assertStringNotContainsString('Reading', $html);
        $this->assertStringNotContainsString('Permalink', $html);
    }

    /**
     * When PLUGINS permission is denied, Plugins menu must be absent.
     */
    public function testPluginsSectionHiddenWithoutPermission(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard', ['plugins' => false]));
        $this->assertStringNotContainsString('Plugins', $html);
    }

    // -----------------------------------------------------------------------
    // User profile link (when user management permission is lacking)
    // -----------------------------------------------------------------------

    /**
     * Without USERS permission, a "Your Profile" link must appear instead of
     * the full user management treeview.
     */
    public function testUserProfileLinkWithoutUserManagement(): void
    {
        $html = $this->runSnippet(
            $this->buildSnippet(
                'dashboard',
                ['users' => false],
                'en',
                '/admin',
                5,
                'abc-session-123'
            )
        );
        $this->assertStringContainsString('Your Profile', $html);
        $this->assertStringNotContainsString('All Users', $html);
        $this->assertStringContainsString('editUser', $html);
    }

    // -----------------------------------------------------------------------
    // Language indicator
    // -----------------------------------------------------------------------

    /**
     * The sidebar must render a language indicator showing the current locale
     * code and language name.
     */
    public function testLanguageIndicatorRenders(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard', [], 'fr'));
        $this->assertStringContainsString('sidebar-lang-indicator', $html);
        $this->assertStringContainsString('sidebar-lang-code', $html);
        $this->assertStringContainsString('FR', $html);
        $this->assertStringContainsString('Français', $html);
        $this->assertStringContainsString('Language Settings', $html);
    }

    /**
     * Language indicator reflects English locale correctly.
     */
    public function testLanguageIndicatorEnglish(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard', [], 'en'));
        $this->assertStringContainsString('EN', $html);
        $this->assertStringContainsString('English', $html);
    }

    // -----------------------------------------------------------------------
    // Section sub-items
    // -----------------------------------------------------------------------

    /**
     * Settings treeview must contain all expected sub-items: General, Reading,
     * Permalink, Timezone, Membership, Mail Settings, Download Settings, API.
     */
    public function testSettingsSectionContainsAllSubItems(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $items = ['General', 'Reading', 'Permalink', 'Timezone',
                   'Membership', 'Mail Settings', 'Download Settings', 'API'];
        foreach ($items as $item) {
            $this->assertStringContainsString($item, $html, "Settings sub-item '$item' must appear");
        }
    }

    /**
     * Tools treeview must contain Import and Export sub-items.
     */
    public function testToolsSectionContainsImportExport(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $this->assertStringContainsString('Import', $html);
        $this->assertStringContainsString('Export', $html);
    }

    /**
     * Privacy treeview must contain sub-items.
     */
    public function testPrivacySectionContainsSubItems(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $items = ['Privacy Settings', 'Data Requests', 'Audit Logs', 'Privacy Policy'];
        foreach ($items as $item) {
            $this->assertStringContainsString($item, $html, "Privacy sub-item '$item' must appear");
        }
    }

    /**
     * Languages treeview must contain All Languages, Translations, Language Config.
     */
    public function testLanguagesSectionContainsSubItems(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $items = ['All Languages', 'Translations', 'Language Config'];
        foreach ($items as $item) {
            $this->assertStringContainsString($item, $html, "Languages sub-item '$item' must appear");
        }
    }

    /**
     * Appearance treeview must contain Themes and Menus.
     */
    public function testAppearanceSectionContainsThemesAndMenus(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $this->assertStringContainsString('Themes', $html);
        $this->assertStringContainsString('Menus', $html);
    }

    // -----------------------------------------------------------------------
    // CSS styles
    // -----------------------------------------------------------------------

    /**
     * The function outputs inline CSS styles for the language indicator.
     */
    public function testSidebarOutputsInlineCss(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('</style>', $html);
        $this->assertStringContainsString('sidebar-lang-indicator', $html);
        $this->assertStringContainsString('.sidebar-lang-link', $html);
    }

    // -----------------------------------------------------------------------
    // aria attributes
    // -----------------------------------------------------------------------

    /**
     * The aside element should have role="navigation" and an aria-label.
     */
    public function testSidebarHasAriaAttributes(): void
    {
        $html = $this->runSnippet($this->buildSnippet('dashboard'));
        $this->assertStringContainsString('role="navigation"', $html);
        $this->assertStringContainsString('aria-label=', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    // -----------------------------------------------------------------------
    // Locale bootstrap guard (static cache)
    // -----------------------------------------------------------------------

    /**
     * When admin_locale is already set in the session, the function must not
     * attempt to instantiate ConfigurationDao (tested by verifying output
     * still renders correctly without database classes).
     */
    public function testLocaleBootstrapUsesSessionWhenSet(): void
    {
        // Session is set in snippet as 'fr'; verify FR appears in indicator
        $html = $this->runSnippet($this->buildSnippet('dashboard', [], 'fr'));
        $this->assertStringContainsString('FR', $html);
        $this->assertStringContainsString('Français', $html);
    }
}
