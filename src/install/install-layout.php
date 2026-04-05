<?php
/**
 * install-layout.php
 *
 * Layout components and helper functions for the installation process.
 * Standardized to the "Minimalist & Elegant Dashboard" pattern.
 *
 * @category  Installation
 * @package   Scriptlog
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.2
 */

/**
 * install_header()
 *
 * Renders the HTML head and opening body tag.
 * Optimized for mobile-first responsiveness and performance.
 *
 * @param string $stylePath Base path for assets.
 */
function install_header($stylePath)
{
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Scriptlog Installation">
    <link href="<?= $stylePath; ?>assets/img/favicon.ico" rel="Shorcut Icon">

    <title>Scriptlog Installation</title>

    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Theme Initialization -->
    <script>
        (function() {
            const theme = localStorage.getItem('scriptlog_install_theme') || 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>

    <!-- Minimal UI Assets -->
    <link href="<?= $stylePath; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $stylePath; ?>assets/vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet">
    <link href="<?= $stylePath; ?>assets/css/form-validation.css" rel="stylesheet">
</head>
<body>
    <?php
}

/**
 * install_footer()
 *
 * Renders the site footer and closing body/html tags.
 *
 * @param string $stylePath Base path for assets.
 */
function install_footer($stylePath)
{
    ?>
    <footer class="my-5 pt-5 text-muted text-center text-small" role="contentinfo">
        <div class="container border-top pt-4">
            <p class="mb-1">&copy; 
                <?php
                    $starYear = 2021;
                $thisYear = date("Y");
                echo ($starYear == $thisYear) ? $starYear : "{$starYear} &#8211; {$thisYear}";
                echo " Scriptlog";
                ?>
            </p>

            <ul class="list-inline">
                <li class="list-inline-item"><a href="../license.txt" target="_blank" rel="noopener noreferrer" title="license.txt">License</a></li>
                <li class="list-inline-item">&middot;</li>
                <li class="list-inline-item"><span class="badge badge-light" aria-label="Memory usage"><?= 'Memory: ' . convert_memory_used(memory_get_usage()); ?></span></li>
                <li class="list-inline-item">&middot;</li>
                <li class="list-inline-item"><span class="badge badge-light" aria-label="Page execution time"><?= 'Time: ' . round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]), 4) . 's'; ?></span></li>
                <li class="list-inline-item">&middot;</li>
                <li class="list-inline-item"><a href="../readme.html" target="_blank" rel="noopener noreferrer" title="readme.html">ReadMe</a></li>
            </ul>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/jquery-3.3.1.min.js"></script>
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/popper.min.js"></script>
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script>
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
    <?php
}

/**
 * check_sisfo()
 *
 * Aggregates system information for the dashboard.
 *
 * @return array
 */
function check_sisfo()
{
    $os = check_os();
    return [
        'PHPVersion' => PHP_VERSION,
        'OS'         => isset($os['Operating_system']) ? $os['Operating_system'] : PHP_OS
    ];
}

/**
 * get_sisfo()
 *
 * Renders the System Environment dashboard.
 * Optimized for responsiveness and accessibility.
 */
function get_sisfo()
{
    $sisfo = check_sisfo();
    $php_passed = version_compare($sisfo['PHPVersion'], '7.4', '>=');
    $web_server = check_web_server();
    $server_success = in_array($web_server['WebServer'], ['Apache', 'LiteSpeed', 'nginx', 'Microsoft-IIS']);
    ?>
<div class="dashboard-section mb-5" role="region" aria-labelledby="env-title">
    <h5 id="env-title" class="dashboard-title">System Environment</h5>
    <div class="sys-info-grid">
        <div class="sys-card shadow-soft">
            <div class="sys-label text-muted">PHP Version</div>
            <div class="sys-value <?= $php_passed ? 'text-success' : 'text-danger'; ?>" aria-label="PHP Version: <?= $sisfo['PHPVersion']; ?>"><?= $sisfo['PHPVersion']; ?></div>
            <div class="mt-2 small text-muted"><?= $php_passed ? 'Meets requirement' : 'Update PHP'; ?></div>
        </div>

        <div class="sys-card shadow-soft">
            <div class="sys-label text-muted">Operating System</div>
            <div class="sys-value"><?= $sisfo['OS']; ?></div>
            <div class="mt-2 small text-muted">Compatible System</div>
        </div>

        <div class="sys-card shadow-soft">
            <div class="sys-label text-muted">Web Server</div>
            <div class="sys-value <?= $server_success ? 'text-success' : 'text-danger'; ?>"><?= $web_server['WebServer']; ?></div>
            <div class="mt-2 small text-muted"><?= $web_server['Version']; ?></div>
        </div>
    </div>
</div>
    <?php
}

/**
 * required_settings()
 *
 * Renders the PHP Extensions requirements as a sleek, accessible table.
 */
function required_settings()
{
    ?>
<div class="dashboard-section mb-5" role="region" aria-labelledby="ext-title">
    <h5 id="ext-title" class="dashboard-title">PHP Extensions</h5>
    <table class="requirements-table" aria-label="PHP Extensions Requirements">
        <tbody>
            <?php
                $checks = [
                    'PCRE UTF-8' => ['condition' => check_pcre_utf8() !== false],
                    'SPL'        => ['condition' => check_spl_enabled('spl_autoload_register')],
                    'Filters'    => ['condition' => check_filter_enabled()],
                    'Iconv'      => ['condition' => check_iconv_enabled()],
                    'Mbstring'   => ['condition' => check_mbstring_enabled()],
                    'Fileinfo'   => ['condition' => check_fileinfo_enabled()],
                    'GD'         => ['condition' => check_gd_enabled()],
                    'PDO MySQL'  => ['condition' => check_pdo_mysql()]
                ];

                foreach ($checks as $label => $check) :
                    ?>
            <tr>
                <td class="py-3"><span class="font-weight-bold text-main" style="letter-spacing: 0.05em;"><?= $label; ?></span></td>
                <td class="text-right py-3">
                    <span class="small font-weight-bold mr-3 <?= $check['condition'] ? 'text-success' : 'text-danger'; ?>" role="status">
                        <?= $check['condition'] ? 'Enabled' : 'Disabled'; ?>
                    </span>
                    <i class="fa <?= $check['condition'] ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>" aria-hidden="true" title="<?= $check['condition'] ? 'Enabled' : 'Disabled'; ?>"></i>
                </td>
            </tr>
                <?php endforeach; ?>
        </tbody>
    </table>
</div>
    <?php
}

/**
 * check_mod_rewrite()
 *
 * Renders Server Module status with focus on minimalist clarity.
 */
function check_mod_rewrite()
{
    if (true === check_modrewrite()) :
        ?>
    <div class="dashboard-section mb-5">
        <h5 class="dashboard-title">Server Modules</h5>
        <div class="sys-card d-flex justify-content-between align-items-center py-3">
            <div class="sys-value" style="font-size: 0.85rem; letter-spacing: 0.05em;">Apache Mod_Rewrite</div>
            <i class="fa fa-check-circle text-success" aria-hidden="true" title="Enabled"></i>
        </div>
    </div>
        <?php
    endif;
}

/**
 * check_dir_file()
 *
 * Renders the File & Directory Permissions with path-level detail.
 */
function check_dir_file()
{
    ?>
<div class="dashboard-section mb-5" role="region" aria-labelledby="dir-title">
    <h5 id="dir-title" class="dashboard-title">Directories & Permissions</h5>
    <table class="requirements-table" aria-label="Directory Permissions Status">
        <tbody>
            <?php
                $dirs = [
                    'Main Engine' => ['condition' => check_main_dir(), 'path' => 'lib/main.php'],
                    'Load Engine' => ['condition' => check_loader(), 'path' => 'Autoloader.php'],
                    'Logs'        => ['condition' => check_log_dir(), 'path' => 'public/log'],
                    'Cache'       => ['condition' => check_cache_dir(), 'path' => 'public/cache'],
                    'Themes'      => ['condition' => check_theme_dir(), 'path' => 'public/themes'],
                    'Plugins'     => ['condition' => check_plugin_dir(), 'path' => 'admin/plugins']
                ];

                foreach ($dirs as $name => $dir) :
                    $is_ok = $dir['condition'];
                    ?>
            <tr>
                <td class="py-3">
                    <div class="font-weight-bold text-main" style="letter-spacing: 0.05em;"><?= $name; ?></div>
                    <code class="xsmall text-muted" aria-label="Path"><?= $dir['path']; ?></code>
                </td>
                <td class="text-right py-3">
                    <span class="small font-weight-bold mr-3 <?= $is_ok ? 'text-success' : 'text-danger'; ?>" role="status">
                        <?= $is_ok ? 'Writable' : 'Read Only'; ?>
                    </span>
                    <i class="fa <?= $is_ok ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'; ?>" aria-hidden="true" title="<?= $is_ok ? 'Writable' : 'Read Only'; ?>"></i>
                </td>
            </tr>
                <?php endforeach; ?>
        </tbody>
    </table>
</div>
    <?php
}

/**
 * are_all_requirements_met()
 *
 * Aggregates all technical checks into a single boolean for gatekeeping.
 *
 * @return bool
 */
function are_all_requirements_met()
{
    $sisfo = check_sisfo();
    $php_passed = version_compare($sisfo['PHPVersion'], '7.4', '>=');

    $ext_checks = [
        check_pcre_utf8() !== false,
        check_spl_enabled('spl_autoload_register'),
        check_filter_enabled(),
        check_iconv_enabled(),
        check_mbstring_enabled(),
        check_fileinfo_enabled(),
        check_gd_enabled(),
        check_pdo_mysql()
    ];

    $dirs_passed = (
        check_main_dir() &&
        check_loader() &&
        check_log_dir() &&
        check_cache_dir() &&
        check_theme_dir() &&
        check_plugin_dir()
    );

    foreach ($ext_checks as $check) {
        if ($check === false) {
            return false;
        }
    }

    return ($php_passed && $dirs_passed);
}
