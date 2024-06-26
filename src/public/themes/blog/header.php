<?php 
require dirname(__FILE__) . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<?= function_exists('theme_meta') ? theme_meta()['site_meta_tags'] . PHP_EOL : ""; ?>

<?php 
 if (function_exists('theme_dir')) :
?>
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/font-awesome/css/font-awesome.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/fontastic.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,700">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/style.sea.css" id="theme-stylesheet">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/custom.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/not-found.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/comment.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/animate.min.css">
<link rel="stylesheet" href="<?= theme_dir(); ?>assets/css/sina-nav.css">
<link rel="shortcut icon" href="<?= theme_dir(); ?>assets/img/favicon.ico">
<?php 
endif;
?>

<?= function_exists('theme_meta') ? theme_meta()['site_schema'] . PHP_EOL : ""; ?>
<!-- Tweaks for older IEs--><!--[if lt IE 9]>
<script src="<?= theme_dir(); ?>assets/js/html5shiv.min.js"></script>
<script src="<?= theme_dir(); ?>assets/js/respond.min.js"></script><![endif]-->
</head>
<body>
<nav class="sina-nav mobile-sidebar navbar-fixed" data-top="0">
        <div class="container">

            <div class="sina-nav-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbar-menu">
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