<?php  defined('SCRIPTLOG') || die("Direct access not permitted");
 if (isset($_GET['forbidden']) && ($_GET['forbidden'] === md5(APP_HOSTNAME.get_ip_address()))) :
?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        403 Forbidden
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Forbidden</a></li>
        <li class="active">403 error</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="error-page">
        <h2 class="headline text-yellow"> 403</h2>

        <div class="error-content">
          <h3><i class="fa fa-warning text-yellow"></i> Oops! Forbidden.</h3>

          <p>
            You do not have privilege, forbidden access.
            Meanwhile, you may <a href="index.php?load=dashboard">return to dashboard</a>.
          </p>

        </div>
        <!-- /.error-content -->
      </div>
      <!-- /.error-page -->
    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
<script type="text/javascript">function leave() { window.location = "index.php?load=dashboard";} setTimeout("leave()", 5000);</script>
<?php
 else:
    
    direct_page('index.php?load=dashboard', 307);
 endif;  
?>