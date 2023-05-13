<?php if (!defined('SCRIPTLOG')) { exit(); } ?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <h1>
      <?= (isset($pageTitle)) ? $pageTitle : ""; ?>
      <small>
        <a href="index.php?load=menu&action=newMenu&Id=0" class="btn btn-primary"><i class="fa fa-plus-circle" aria-hidden="true"></i> Add New</a>
      </small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard" aria-hidden="true"></i> Home </a></li>
      <li><a href="index.php?load=menu">All Menus </a></li>
      <li class="active">Data Menu</li>
    </ol>
  </section>

  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <?php
        if (isset($errors)) :
        ?>
          <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h2><i class="icon fa fa-ban" aria-hidden="true"></i> Error!</h2>
            <?php
            foreach ($errors as $e) :
              echo $e;
            endforeach;
            ?>
          </div>
        <?php
        endif;
        ?>

        <?php
        if (isset($status)) :
        ?>
          <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
            <h2><i class="icon fa fa-check" aria-hidden="true"></i> Success!</h2>
            <?php
            foreach ($status as $s) :
              echo $s;
            endforeach;
            ?>
          </div>
        <?php
        endif;
        ?>

        <div class="box box-primary">
          <div class="box-header with-border">
            <h2 class="box-title">
              <?= (isset($menusTotal)) ? $menusTotal : 0; ?>
              Menu<?= ($menusTotal != 1) ? 's' : ''; ?>
              in Total
            </h2>
          </div>
          <!-- /.box-header -->

          <div class="box-body table-responsive">
            <table id="scriptlog-table" class="table table-bordered table-striped" aria-describedby="all menus">
              <thead>
                <tr>
                  <th>Menu</th>
                  <th>Parent</th>
                  <th>Link</th>
                  <th>Status</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if (is_array($menus)) :
                  $no = 0;
                  $parents = null;
                  while ($menu = array_shift($menus)) :
                ?>

                    <tr>

                      <td><?= htmlspecialchars($menu['menu_label']); ?></td>
                      <td>
                        
                        <?php
                        $parent = nav_parent($menu['parent_id']);
                        $total = $parent->num_rows;

                        if ($total > 0) {

                          while ($data_parent = nav_nested($parent)) {

                            echo htmlout(strtolower($data_parent['menu_label']));

                          }
                        } else {

                          echo "parent";
                        }

                        ?>
                      </td>

                      <td><?= htmlspecialchars($menu['menu_link']); ?></td>
                      <td>
                        <?php if ($menu['menu_status'] === 'N') : ?>
                          disabled
                        <?php
                        else :
                        ?>
                          enabled
                        <?php endif; ?>
                      </td>
                      <td>
                        <a href="<?= generate_request('index.php', 'get', ['menu', ActionConst::EDITMENU, $menu['ID']])['link']; ?>" class="btn btn-warning" title="Edit menu">
                          <i class="fa fa-pencil fa-fw" aria-hidden="true"></i> </a>
                      </td>
                      <td>
                        <a href="javascript:deleteMenu('<?= abs((int)$menu['ID']); ?>', '<?= $menu['menu_label']; ?>')" class="btn btn-danger" title="Deactivate menu">
                          <i class="fa fa-trash-o fa-fw" aria-hidden="true"></i> </a>
                      </td>

                    </tr>

                <?php
                    $no++;
                  endwhile;
                endif;
                ?>

              </tbody>
              <tfoot>
                <tr>
                  <th>Menu</th>
                  <th>Parent</th>
                  <th>Link</th>
                  <th>Status</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
              </tfoot>
            </table>
          </div>
          <!-- /.box-body -->
        </div>
        <!-- /.box -->
      </div>
      <!-- /.col-xs-12 -->
    </div>
    <!-- /.row -->
  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<script type="text/javascript">
  function deleteMenu(id, menu) {
    if (confirm("Are you sure want to delete menu '" + menu + "'")) {
      window.location.href = 'index.php?load=menu&action=deleteMenu&Id=' + id;
    }
  }
</script>