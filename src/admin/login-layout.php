<?php

function login_header($stylePath)
{

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Log In | Scriptlog</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
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
  
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="assets/dist/js/html5shiv.js"></script>
  <script src="assets/dist/js/respond.min.js"></script>
  <![endif]-->
  
<!-- Google Font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  
<link href="favicon.ico" rel="Shortcut Icon">
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

</body>
</html>

<?php
}