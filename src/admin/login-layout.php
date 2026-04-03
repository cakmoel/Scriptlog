<?php
/**
 * login-layout.php - Refactored
 */
function login_header($stylePath)
{
    // Define a version identifier for cache busting (change this value when assets are updated)
    $version = '1.0.1';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="robots" content="noindex,noarchive" />
  
  <meta name="referrer" content="no-referrer" /> 
  <title>Log In</title>
  
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/components/bootstrap/dist/css/bootstrap.min.css?v=<?=$version; ?>">
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/components/font-awesome/css/font-awesome.min.css?v=<?=$version; ?>">
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/components/Ionicons/css/ionicons.min.css?v=<?=$version; ?>">
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/dist/css/AdminLTE.min.css?v=<?=$version; ?>">
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/components/iCheck/square/blue.css?v=<?=$version; ?>">
  
<link rel="apple-touch-icon" sizes="57x57" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?= $stylePath; ?>/assets/dist/img/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="<?= $stylePath; ?>/assets/dist/img/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $stylePath; ?>/assets/dist/img/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="<?= $stylePath; ?>/assets/dist/img/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $stylePath; ?>/assets/dist/img/favicon-16x16.png">
<link rel="manifest" href="<?= $stylePath; ?>/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="<?= $stylePath; ?>/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  
<link href="<?= $stylePath; ?>/favicon.ico" rel="Shortcut Icon">
<style>
/* Honeypot field CSS is retained */
.scriptpot{opacity:0;position:absolute;top:0;left:0;height:0;width:0;z-index:-1}
</style>
</head>
<body class="hold-transition login-page">
<div class="login-box">

    <?php
}

function login_footer($stylePath)
{
    $version = '1.0.1';
    ?>

</div>
  
<script src="<?= $stylePath; ?>/assets/components/jquery/dist/jquery.min.js?v=<?=$version; ?>"></script>
<script src="<?= $stylePath; ?>/assets/components/bootstrap/dist/js/bootstrap.min.js?v=<?=$version; ?>"></script>
<script src="<?= $stylePath; ?>/assets/components/iCheck/icheck.min.js?v=<?=$version; ?>"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/checklogin.js?v=<?=$version; ?>"></script>
<script>
    $(function () {
      $('input').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%' 
      });
    });
</script>
</body>
</html>

    <?php
}
