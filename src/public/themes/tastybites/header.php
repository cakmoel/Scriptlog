<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
require dirname(__FILE__) . '/functions.php';
?>
<!DOCTYPE html>
<html lang="<?= get_locale(); ?>" dir="<?= get_html_dir(); ?>">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="TastyBites - Delicious recipes and food stories">
<?php if (function_exists('app_url')) : ?>
<link rel="alternate" type="application/rss+xml" title="TastyBites RSS Feed" href="<?= app_url(); ?>/rss.php">
<link rel="alternate" type="application/atom+xml" title="TastyBites Atom Feed" href="<?= app_url(); ?>/atom.php">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap">

<?php
if (function_exists('theme_dir')) :
    ?>
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/style.css">
    <?php if (is_rtl()) : ?>
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/rtl.css">
    <?php endif; ?>
<link rel="shortcut icon" href="<?= theme_dir(); ?>assets/img/favicon.ico">
    <?php
endif;
?>

<script>
var scriptlog_vars = {
    api_url: '<?= app_url(); ?>/api/v1',
    site_url: '<?= app_url(); ?>',
    theme_dir: '<?= theme_dir(); ?>'
};
</script>
<!--[if lt IE 9]>
<script src="<?= theme_dir(); ?>assets/js/html5shiv.min.js"></script>
<script src="<?= theme_dir(); ?>assets/js/respond.min.js"></script><![endif]-->
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand font-weight-bold" href="<?= app_url(); ?>" style="color: var(--primary-color); font-size: 1.8rem;">
            <?= function_exists('app_sitename') ? app_sitename() : 'TastyBites'; ?>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <?php
                $menus = theme_navigation('public');
                echo front_navigation(0, $menus);
                ?>
                
                <!-- Language Switcher -->
                <li class="dropdown language-switcher">
                    <button type="button" 
                            class="btn-language dropdown-toggle" 
                            id="languageMenu" 
                            data-toggle="dropdown" 
                            aria-haspopup="true" 
                            aria-expanded="false"
                            aria-label="Select language - Current: <?php echo get_language_name(get_locale(), false); ?>">
                        <i class="fa fa-globe" aria-hidden="true"></i>
                        <span class="lang-text"><?php echo get_language_name(get_locale(), true); ?></span>
                        <span class="lang-code"><?php echo strtoupper(get_locale()); ?></span>
                    </button>
                    <ul class="dropdown-menu" role="menu" aria-labelledby="languageMenu">
                        <?php
                        $locales = available_locales();
                        $current = get_locale();
                        $permalinksEnabled = function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes';
                        
                        foreach ($locales as $locale) :
                            $is_active = ($locale === $current) ? 'active' : '';
                            
                            // Determine the correct URL based on permalink and locale prefix settings
                            if (!$permalinksEnabled) {
                                // When permalinks disabled: use query string format
                                $lang_url = '?switch-lang=' . urlencode($locale) . '&redirect=' . urlencode($_SERVER['REQUEST_URI']);
                            } else {
                                // When permalinks enabled: use locale_url() which handles prefix logic
                                $lang_url = locale_url($_SERVER['REQUEST_URI'], $locale);
                            }
                            
                            $native_name = get_language_name($locale, true);
                            $english_name = get_language_name($locale, false);
                            $lang_code = strtoupper($locale);
                            ?>
                            <li>
                                <a href="<?php echo htmlspecialchars($lang_url, ENT_QUOTES, 'UTF-8'); ?>" 
                                   class="dropdown-item <?php echo $is_active; ?>" 
                                   role="menuitem"
                                   aria-current="<?php echo ($is_active === 'active') ? 'page' : 'false'; ?>"
                                   aria-label="<?php echo htmlout($english_name); ?>">
                                    <span class="lang-flag" aria-hidden="true">
                                        <?php if ($is_active === 'active') : ?>
                                            <i class="fa fa-check text-success" aria-hidden="true"></i>
                                        <?php else : ?>
                                            <i class="fa fa-circle-o text-muted" aria-hidden="true"></i>
                                        <?php endif; ?>
                                    </span>
                                    <span class="lang-info">
                                        <span class="lang-native"><?php echo htmlout($native_name); ?></span>
                                        <span class="lang-english"><?php echo htmlout($english_name); ?></span>
                                    </span>
                                    <span class="lang-code-badge"><?php echo htmlout($lang_code); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>