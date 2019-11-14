<?php 

function admin_header($stylePath, $breadCrumbs, $allowedQuery) 
{
  
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>

  <?php 
    
  if (strstr($breadCrumbs, '../') !== false) {
    
        echo 'Error: 400 Bad Request';
    
  } elseif(strstr($breadCrumbs, 'file://') !== false ) {
   
        echo 'Error: 400 Bad Request';
        
  } elseif ((empty($breadCrumbs)) || (!in_array($breadCrumbs, $allowedQuery))) {
  
        echo 'Error: 404 Not Found';
       
  } else {
      
      cp_tag_title($breadCrumbs);
      
  }
       
  ?>

  </title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/bootstrap/dist/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/Ionicons/css/ionicons.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/datatables.net-bs/css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/datatables.net/css/responsive.bootstrap.min.css">
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/datatables.net/css/responsive.dataTables.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/dist/css/AdminLTE.min.css">
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/dist/css/skins/scriptlog-skin.css">
  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/dist/css/ie10-viewport-bug-workaround.css">
   <!-- wysiwyg editor-->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/wysihtml5/bootstrap3-wysihtml5.min.css">

  
<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
<!--[if lt IE 9]>
<script src="<?= $stylePath; ?>/assets/dist/js/html5shiv.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/respond.min.js"></script>
<![endif]-->

<!-- Google Font -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
<!-- Icon -->
<link href="<?= $stylePath; ?>/favicon.ico" rel="Shortcut Icon">
   
</head>

<body class="hold-transition skin-scriptlog sidebar-mini">

<div class="wrapper">
<?php 
}

function admin_footer($stylePath)
{
    
?>
 <!-- Main Footer -->
  <footer class="main-footer">
    <!-- To the right -->
    <div class="pull-right hidden-xs">
      <?php 
        echo APP_CODENAME;
       ?>
    </div>
    <!-- Default to the left -->
    <strong>Thank you for creating with 
    <a href="https://scriptlog.web.id" targer="_blank" title="Personal Blogware Platform">Scriptlog</a>
     <?php echo APP_VERSION; ?></strong>
  </footer>
  
   <!-- Add the sidebar's background. This div must be placed
  immediately after the control sidebar -->
  <div class="control-sidebar-bg"></div>
  </div>
<!-- ./wrapper -->

<!-- jQuery 3 -->
<script src="<?= $stylePath; ?>/assets/components/jquery/dist/jquery.min.js"></script>
<!-- Bootstrap 3.3.7 -->
<script src="<?= $stylePath; ?>/assets/components/bootstrap/dist/js/bootstrap.min.js"></script>
<!-- DataTables -->
<script src="<?= $stylePath; ?>/assets/components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/datatables.net/js/responsive.bootstrap.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/datatables.net/js/responsive.dataTables.js"></script>
<!-- AdminLTE App -->
<script src="<?= $stylePath; ?>/assets/dist/js/adminlte.min.js"></script>
<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
<script src="<?= $stylePath; ?>/assets/dist/js/ie10-viewport-bug-workaround.js"></script>
<!-- Slimscroll -->
<script src="<?= $stylePath; ?>/assets/components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
<!-- FastClick -->
<script src="<?= $stylePath; ?>/assets/components/fastclick/lib/fastclick.js"></script>
<!-- Checking Form Field -->
<script src="<?= $stylePath; ?>/assets/dist/js/checkFormSetting.js"></script>
<!-- Mandatory Plugin File Uploaded -->
<script src="<?= $stylePath; ?>/assets/dist/js/mandatory-plugin-upload.js"></script>
<!-- Mandatory Theme File Uploaded -->
<script src="<?= $stylePath; ?>/assets/dist/js/mandatory-theme-upload.js"></script>
<!-- Validate Image -->
<script src="<?= $stylePath; ?>/assets/dist/js/imagevalidation.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/imagesizechecker.js"></script>
<!-- Bootstrap WYSIHTML5 -->
<script src="<?= $stylePath; ?>/assets/components/wysihtml5/bootstrap3-wysihtml5.all.min.js"></script>
<!-- data-table script -->
<script>
$(document).ready(function(){
	$('#scriptlog-table').DataTable({
		"order": [],
		"columnDefs":[
			{
				"targets":[0, 4, 5],
				"orderable":false,
			},
		],

   });
});
</script>
<!-- Text Editor -->
<script>
  $(function () {
    //bootstrap WYSIHTML5 - text editor
    $('.textarea').wysihtml5()
  })
</script>
</body>
</html>
<?php 
}