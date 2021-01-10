<?php if (!defined('SCRIPTLOG')) exit(); ?>
 <!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
<!-- Content Header (Page header) -->
    <section class="content-header">
      <h1>
        <?=(isset($pageTitle)) ? $pageTitle : ""; ?>
        <small>	<a href="<?= generate_request('index.php', 'get', ['users', ActionConst::NEWUSER, 0, sha1(app_key())])['link']; ?>"
					class="btn btn-primary"> <i
					class="fa fa-plus-circle"></i> Add New
				</a>
		    </small>
      </h1>
      <ol class="breadcrumb">
        <li><a href="index.php?load=dashboard"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="index.php?load=users">All Users</a></li>
        <li class="active">Data Users</li>
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
                <h4><i class="icon fa fa-ban"></i> Error!</h4>
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
                <h4><i class="icon fa fa-check"></i> Success!</h4>
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
              <?=(isset($usersTotal)) ? $usersTotal : 0; ?> 
               User<?=($usersTotal != 1) ? 's' : ''; ?>
               in Total  
              </h2>
            </div>
              <!-- /.box-header -->
             
              <div class="box-body">
                <table id="scriptlog-table" class="table table-bordered table-striped table-responsive">
                <thead>
                <tr>
                  <th>#</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Edit</th>
                  <th>Delete</th>
                </tr>
                </thead>
                <tbody>
                   <?php 
                     if (is_array($users)) : 
                       $no = 0;
                       foreach ($users as $user) :
                        $no++;
                   ?>
                   
                     <tr>
                       <td><?= $no; ?></td>
                       <td><?= safe_html($user['user_login']); ?></td>
                       <td><?= safe_html($user['user_email']); ?></td>
                       <td><?= safe_html($user['user_level']); ?></td>
                       <td>
                       <a href="<?=generate_request('index.php', 'get', ['users', ActionConst::EDITUSER, $user['ID'], $user['user_session']])['link']; ?>" class="btn btn-warning" title="Edit user">
                       <i class="fa fa-pencil fa-fw"></i></a>
                       </td>
                       <td>
                       <a href="javascript:deleteUser('<?= abs((int)$user['ID']); ?>', '<?= safe_html($user['user_login']); ?>')" class="btn btn-danger" title="Delete user">
                       <i class="fa fa-trash-o fa-fw"></i></a>
                       </td>
                     </tr>
                  
                   <?php 
                      endforeach; 
                      endif; 
                    ?>
                    
                </tbody>
                <tfoot>
                <tr>
                  <th>#</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Role</th>
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
  function deleteUser(id, name)
  {
	  if (confirm("Are you sure want to delete User '" + name + "'"))
	  {
	  	window.location.href = 'index.php?load=users&action=deleteUser&Id=' + id;
	  }
  }
</script>