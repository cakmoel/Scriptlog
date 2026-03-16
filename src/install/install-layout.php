<?php
function install_header($stylePath)
{
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Scriptlog Installation">
    <link href="<?= $stylePath; ?>assets/img/favicon.ico" rel="Shorcut Icon" >

    <title>Scriptlog Installation</title>

    <!-- Bootstrap core CSS -->
    <link href="<?= $stylePath; ?>assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= $stylePath; ?>assets/vendor/font-awesome/css/font-awesome.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="<?= $stylePath; ?>assets/css/form-validation.css" rel="stylesheet">

</head>
<body class="bg-light">

<?php
}

function install_footer($stylePath)
{

?>

<footer class="my-5 pt-5 text-muted text-center text-small">
    <div class="container">
        <p class="mb-1">&copy; 
           <?php 
                   
              $starYear = 2021;
              $thisYear = date( "Y" );
              if ($starYear == $thisYear) {
                 
                  echo $starYear;
                 
              } else {
                  
                  echo " {$starYear} &#8211; {$thisYear} ";
               }
                         
                 echo "Scriptlog";
                 
            ?>
             
            </p>
            
        <ul class="list-inline">
              <li class="list-inline-item"><a href="../license.txt" target="_blank" rel="noopener noreferrer" title="license.txt" >License</a></li>
              <li class="list-inline-item">&middot;</li>
              <li class="list-inline-item"><span class="badge badge-light"><?= 'Memory: '. convert_memory_used(memory_get_usage()); ?></span></li>
              <li class="list-inline-item">&middot;</li>
              <li class="list-inline-item"><span class="badge badge-light"><?= 'Time: '.round((microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]), 4). 's'; ?></span></li>
              <li class="list-inline-item">&middot;</li>
              <li class="list-inline-item"><a href="../readme.html" target="_blank" rel="noopener noreferrer" title="readme.html" >ReadMe</a></li>
            </ul>
    </div>
</footer>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/jquery-3.3.1.min.js"></script>
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/vendor/popper.min.js"></script>
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script>
      // Example starter JavaScript for disabling form submissions if there are invalid fields
      (function() {
        'use strict';

        window.addEventListener('load', function() {
          // Fetch all the forms we want to apply custom Bootstrap validation styles to
          var forms = document.getElementsByClassName('needs-validation');

          // Loop over them and prevent submission
          var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
              if (form.checkValidity() === false) {
                event.preventDefault();
                event.stopPropagation();
              }
              form.classList.add('was-validated');
            }, false);
          });
        }, false);
      })();
    </script>
  </body>
</html>

<?php
}

function get_sisfo()
{
?>
<div class="sidebar-title">System Info</div>
<ul class="list-group mb-4 shadow-sm">
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
            <div class="font-weight-bold small text-uppercase text-muted">PHP Version</div>
            <?php 
            if (check_php_version()) :
                $php_passed = 'text-success';
                $php_checked = 'fa fa-check-circle';
            endif;
            ?>
            <span class="<?=(isset($php_passed)) ? $php_passed : 'text-danger'; ?> font-weight-bold"><?=(isset($php_passed)) ? PHP_VERSION : 'Requires 7.4+'; ?></span>
        </div>
        <i class="<?=(isset($php_checked)) ? $php_checked : 'fa fa-times-circle'; ?> fa-lg <?=(isset($php_passed)) ? 'text-success' : 'text-danger'; ?>"></i>
    </li>
    
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
            <div class="font-weight-bold small text-uppercase text-muted">OS</div>
            <?php 
              $osname = check_os()['Operating_system'];
              $oslist = array('Linux', 'OS X', 'FreeBSD', 'Chrome OS', 'OpenBSD', 'NetBSD', 'OpenSolaris', 'Windows');
              $os_passed = in_array($osname, $oslist);
            ?>
            <span class="<?=($os_passed) ? 'text-success' : 'text-danger'; ?> font-weight-bold"><?=($os_passed) ? $osname : 'Not Supported'; ?></span>
        </div>
        <i class="<?=($os_passed) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?> fa-lg <?=($os_passed) ? 'text-success' : 'text-danger'; ?>"></i>
    </li>
    
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
            <div class="font-weight-bold small text-uppercase text-muted">Server</div>
            <?php 
             $web_server = check_web_server();
             $server_name = $web_server['WebServer'];
             $server_version = $web_server['Version'];
             $serverList = array('Apache', 'LiteSpeed', 'nginx', 'Microsoft-IIS');
             $server_success = in_array($server_name, $serverList);
            ?>
            <span class="<?=($server_success) ? 'text-success' : 'text-danger'; ?> font-weight-bold"><?=($server_name) ? $server_name.' '.$server_version : 'Not supported'; ?></span>
        </div>
        <i class="<?=($server_success) ? 'fa fa-check-circle' : 'fa fa-times-circle'; ?> fa-lg <?=($server_success) ? 'text-success' : 'text-danger'; ?>"></i>
    </li>
</ul>
<?php
}

function required_settings()
{
?>
<div class="sidebar-title">PHP Settings</div>
<ul class="list-group mb-4 shadow-sm">
    <?php
    $checks = [
        'PCRE UTF-8' => [
            'condition' => check_pcre_utf8() === false,
            'error' => 'Required UTF-8 support'
        ],
        'SPL Autoload' => [
            'condition' => check_spl_enabled('spl_autoload_register'),
            'error' => 'Required SPL'
        ],
        'Filters' => [
            'condition' => check_filter_enabled(),
            'error' => 'Required Filters'
        ],
        'Iconv' => [
            'condition' => check_iconv_enabled(),
            'error' => 'Required Iconv'
        ],
        'Mbstring' => [
            'condition' => check_mbstring_enabled(),
            'error' => 'Required Mbstring'
        ],
        'Fileinfo' => [
            'condition' => check_fileinfo_enabled(),
            'error' => 'Required Fileinfo'
        ],
        'GD' => [
            'condition' => check_gd_enabled(),
            'error' => 'Required GD v2'
        ],
        'PDO MySQL' => [
            'condition' => check_pdo_mysql(),
            'error' => 'Required PDO MySQL'
        ],
        'MySQLi' => [
            'condition' => check_mysqli_enabled(),
            'error' => 'Required MySQLi'
        ]
    ];

    foreach ($checks as $label => $check) :
    ?>
    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
        <span class="small font-weight-bold text-muted"><?= $label; ?></span>
        <i class="<?= ($check['condition']) ? 'fa fa-check text-success' : 'fa fa-times text-danger'; ?>"></i>
    </li>
    <?php endforeach; ?>
</ul>
<?php
}

function check_mod_rewrite()
{
  if (true === check_modrewrite()) :
?>
    <div class="sidebar-title">Modes</div>
    <ul class="list-group mb-4 shadow-sm">
        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <span class="small font-weight-bold text-muted">Mode Rewrite</span>
            <i class="fa fa-check text-success"></i>
        </li>
    </ul>
<?php
  endif;
}

function check_dir_file()
{
?>
<div class="sidebar-title">Directories & Files</div>
<ul class="list-group mb-4 shadow-sm">
    <?php
    $dirs = [
        'Main Engine' => ['condition' => check_main_dir(), 'label' => 'lib/main.php'],
        'Load Engine' => ['condition' => check_loader(), 'label' => 'Autoloader.php'],
        'Logs' => ['condition' => check_log_dir(), 'label' => 'public/log'],
        'Cache' => ['condition' => check_cache_dir(), 'label' => 'public/cache'],
        'Themes' => ['condition' => check_theme_dir(), 'label' => 'public/themes'],
        'Plugins' => ['condition' => check_plugin_dir(), 'label' => 'admin/plugins']
    ];

    foreach ($dirs as $name => $dir) :
    ?>
    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
        <div>
            <div class="small font-weight-bold text-muted"><?= $name; ?></div>
            <code class="xsmall text-muted" style="font-size: 75%;"><?= $dir['label']; ?></code>
        </div>
        <i class="<?= ($dir['condition']) ? 'fa fa-check text-success' : 'fa fa-times text-danger'; ?>"></i>
    </li>
    <?php endforeach; ?>
</ul>
<?php
}
