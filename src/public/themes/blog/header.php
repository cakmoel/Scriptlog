<?php
require dirname(__FILE__) . '/functions.php';
?>
<!DOCTYPE html>
<html lang="<?= get_locale(); ?>" dir="<?= get_html_dir(); ?>">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<?= function_exists('theme_meta') ? theme_meta()['site_meta_tags'] . PHP_EOL : ""; ?>
<?php if (function_exists('app_url')) : ?>
<link rel="alternate" type="application/rss+xml" title="<?= function_exists('app_sitename') ? app_sitename() : ''; ?> RSS Feed" href="<?= app_url(); ?>/rss.php">
<link rel="alternate" type="application/atom+xml" title="<?= function_exists('app_sitename') ? app_sitename() : ''; ?> Atom Feed" href="<?= app_url(); ?>/atom.php">
<?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700" as="style">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700">

<style>
/* Critical CSS */
body{overflow-x:hidden;font-family:"Open Sans",sans-serif}
.sina-nav{min-height:60px;background:#fff;border:1px solid #eee;position:relative;z-index:9999}
.sina-nav .container{position:relative}
.sina-nav .sina-brand{height:60px;float:left;text-decoration:none}
.sina-nav .sina-brand h2{color:#222;font-size:30px;line-height:36px;margin:0}
.sina-nav .sina-brand p{color:#222;font-size:14px;line-height:16px;margin:0}
.sina-nav .sina-menu{list-style:none;margin:0;padding:0}
.sina-nav .sina-menu>li{float:right}
.sina-nav .sina-menu>li>a{display:block;padding:20px 15px;color:#222;font-size:14px;font-weight:700;text-transform:uppercase;text-decoration:none}
@media (max-width:1024px){.sina-nav .sina-brand{margin-left:-35px}.sina-nav .navbar-toggle{float:left;padding:4px 10px;margin-top:12px;background:transparent;border:0;font-size:18px}}
.main-footer{padding:50px 0;background:#222;color:#ccc}
</style>

<?php
if (function_exists('theme_dir')) :
    ?>
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/fontastic.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/style.sea.min.css" id="theme-stylesheet" media="print" onload="this.media='all'">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/custom.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/not-found.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/privacy.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/comment.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/animate.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/sina-nav.min.css" media="print" onload="this.media='all'">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/cookie-consent.min.css">
    <?php if (is_rtl()) : ?>
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/rtl.min.css">
    <?php endif; ?>
<link rel="shortcut icon" href="<?= theme_dir(); ?>assets/img/favicon.ico">
    <?php
endif;
?>

<?= function_exists('theme_meta') ? theme_meta()['site_schema'] . PHP_EOL : ""; ?>
<script>
    var scriptlog_vars = {
        api_url: '<?= app_url(); ?>/api/v1',
        site_url: '<?= app_url(); ?>',
        theme_dir: '<?= theme_dir(); ?>'
    };
</script>
<!-- Tweaks for older IEs--><!--[if lt IE 9]>
<script src="<?= theme_dir(); ?>assets/js/html5shiv.min.js"></script>
<script src="<?= theme_dir(); ?>assets/js/respond.min.js"></script><![endif]-->
</head>
<body>
<nav class="sina-nav mobile-sidebar navbar-fixed" data-top="0">
        <div class="container">

            <div class="sina-nav-header">
                <button id="al" aria-label="Menu" type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
                    <i class="fa fa-bars fa-fw" aria-hidden="true"></i>
                </button>
                <a class="sina-brand" href="<?= app_url(); ?>">
                    <h2>
                        <?= htmlout(app_sitename());?>
                    </h2>
                    <p><?= htmlout(app_tagline()) ?></p>
                </a>
            </div><!-- .sina-nav-header -->


            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="navbar-menu">
                <ul class="sina-menu sina-menu-right" data-in="fadeInLeft" data-out="fadeInOut">
                    <?php
                    $menus = theme_navigation('public');
                    echo  front_navigation(0, $menus);
                    ?>
                </ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- .container -->
    </nav>