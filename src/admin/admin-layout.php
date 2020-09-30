<?php 

function admin_header($stylePath, $breadCrumbs = null, $allowedQuery = null) 
{
  
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
<title><?= cp_tag_title($breadCrumbs, $allowedQuery); ?></title>
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width" name="viewport">
  <!-- Bootstrap 3.3.7 -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/bootstrap/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/bootstrap/dist/css/bootstrap-select.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/font-awesome/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/Ionicons/css/ionicons.min.css">
  <!-- DataTables -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/datatables.net-bs/css/dataTables.bootstrap.min.css">
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/datatables.net/css/responsive.bootstrap.min.css">
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/datatables.net/css/responsive.dataTables.min.css">
  <!-- select2 -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/components/select2/css/select2.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/dist/css/AdminLTE.min.css">
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/dist/css/skins/scriptlog-skin.css">
  <!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/dist/css/ie10-viewport-bug-workaround.css">
  <!-- Image Preview -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/dist/css/imagePreview.css">
  <!-- Audio Preview -->
  <link rel="stylesheet" href="<?= $stylePath; ?>/assets/dist/css/audioPreview.css">
   <!-- wysiwyg editor-->
  <link href="<?= $stylePath; ?>/wysiwyg/summernote/summernote.min.css" rel="stylesheet">

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
<style>
  .avatar {
  vertical-align: middle;
  width: 50px;
  height: 50px;
  border-radius: 50%;
}
</style>

</head>

<body class="hold-transition skin-scriptlog sidebar-mini">
<div class="wrapper">

<?php 
}

function admin_footer($stylePath, $ubench = null)
{
    
?>
 
<footer class="main-footer">
   
    <div class="pull-right hidden-xs">
      <?php 
        echo APP_CODENAME;
       ?>
    </div>
    
    <strong>Thank you for creating with 
    <a href="https://scriptlog.my.id" target="_blank" rel="noopener noreferrer" title="Personal Blogware Platform">Scriptlog</a>
     <?php echo APP_VERSION; ?></strong>
     <strong><?=((true === APP_DEVELOPMENT) && (isset($ubench))) ? " Page generated in: ". $ubench->getTime() . " Memory usage: ".$ubench->getMemoryUsage() : "" ?></strong>
</footer>
  
  <div class="control-sidebar-bg"></div>  
</div>

<script src="<?= $stylePath; ?>/assets/components/jquery/dist/jquery.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/bootstrap/dist/js/bootstrap-select.js"></script>
<script src="<?= $stylePath; ?>/assets/components/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/datatables.net-bs/js/dataTables.bootstrap.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/datatables.net/js/responsive.bootstrap.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/datatables.net/js/responsive.dataTables.js"></script>
<script src="<?= $stylePath; ?>/assets/components/select2/js/select2.full.min.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/adminlte.min.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/ie10-viewport-bug-workaround.js"></script>
<script src="<?= $stylePath; ?>/assets/components/jquery-slimscroll/jquery.slimscroll.min.js"></script>
<script src="<?= $stylePath; ?>/assets/components/fastclick/lib/fastclick.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/checkFormSetting.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/mandatory-plugin-upload.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/mandatory-theme-upload.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/imagevalidation.js"></script>
<script src="<?= $stylePath; ?>/assets/dist/js/toggle-field.js"></script>
<script src="<?= $stylePath; ?>/wysiwyg/summernote/summernote.min.js"></script>
<script type="text/javascript" src="<?= $stylePath; ?>/assets/dist/js/jquery.uploadPreview.min.js"></script>
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
<script type="text/javascript">
$(document).ready(function() {
  $.uploadPreview({
    input_field: "#image-upload",
    preview_box: "#image-preview",
    label_field: "#image-label"
  });
});
</script>
<script>
$('img').bind('contextmenu',function(e){return false;}); 
</script>
<script>
$(document).ready(function() {
  $('#summernote').summernote({
    height: 300,                 
    minHeight: null,             
    maxHeight: null,             
  });
});
</script>
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

  })
</script>
</html>
<?php 
}