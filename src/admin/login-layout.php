<?php

function login_header($stylePath)
{

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="robots" content="noindex,noarchive" />
  <meta name="referrer" content="strict-origin-when-cross-origin" />
  <title>Log In</title>
  <meta content="width=device-width" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/components/bootstrap/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/components/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/components/Ionicons/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/dist/css/AdminLTE.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="<?=$stylePath; ?>/assets/components/iCheck/square/blue.css">
  
<link rel="apple-touch-icon" sizes="57x57" href="<?= $stylePath; ?>/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="<?= $stylePath; ?>/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?= $stylePath; ?>/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="<?= $stylePath; ?>/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?= $stylePath; ?>/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="<?= $stylePath; ?>/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="<?= $stylePath; ?>/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="<?= $stylePath; ?>/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?= $stylePath; ?>/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="<?= $stylePath; ?>/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?= $stylePath; ?>/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="<?= $stylePath; ?>/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $stylePath; ?>/favicon-16x16.png">
<link rel="manifest" href="<?= $stylePath; ?>/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="<?= $stylePath; ?>/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">

  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="assets/dist/js/html5shiv.js"></script>
  <script src="assets/dist/js/respond.min.js"></script>
  <![endif]-->
  
<!-- Google Font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  
<link href="<?= $stylePath; ?>/favicon.ico" rel="Shortcut Icon">
<style>
.scriptpot{opacity:0;position:absolute;top:0;left:0;height:0;width:0;z-index:-1}
</style>
</head>
<body class="hold-transition login-page">
<div class="login-box">

<?php
}

function login_footer($stylePath)
{

?>

</div>
  
<script src="<?= $stylePath; ?>/assets/components/jquery/dist/jquery.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/iCheck/icheck.min.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/checklogin.js"></script>
<script>
    $(function () {
      $('input').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%' 
      });
    });
</script>
<script>
$('img').bind('contextmenu',function(e){return false;}); 
</script>
<script>
$(document).bind("contextmenu",function(e){return false;});
</script>
<script>
document.onkeydown=function(e){if(e.ctrlKey&&(e.keyCode===67||e.keyCode===86||e.keyCode===85||e.keyCode===117)){return false;}else{return true;}};$(document).keypress("u",function(e){if(e.ctrlKey)
{return false;}
else
{return true;}});
</script>
<script>
$(document).keydown(function(event){if(event.keyCode==123){return false}else if(event.ctrlKey&&event.shiftKey&&event.keyCode==73){return false}});
</script>
</body>
</html>

<?php
}