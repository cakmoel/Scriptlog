<?php  if (!defined('SCRIPTLOG')) { exit(); } ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?= displaying_greeting(); ?>
        <small><?=(isset($pageTitle)) ? $pageTitle : ""; ?></small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="?load=dashboard"><i class="fa fa-dashboard"></i>Home </a></li>
        <li class="active">Dashboard</li>
      </ol>
    </section>

    <!-- Main content -->
    <section class="content">

      <!--------------------------
        | Your Page Content Here |
        -------------------------->

    </section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->