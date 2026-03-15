<?php  defined('SCRIPTLOG') || die("Direct access not permitted");
if ((isset($_GET['notfound']) && ($_GET['notfound'] === md5(app_key().get_ip_address())))) :
?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        404 Error Page
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="#">Page Not Found</a></li>
        <li class="active">404 error</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="error-page">
        <h2 class="headline text-yellow"> 404</h2>

        <div class="error-content">
          <h3><i class="fa fa-warning text-yellow"></i> Oops! Page not found.</h3>

          <p>
            We could not find the page you were looking for.
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
<script type="text/javascript">function leave() {  window.location = "index.php?load=dashboard";} setTimeout("leave()", 5000);</script>
<?php
else :
  direct_page('index.php?load=dashboard', 307);
endif;
?>