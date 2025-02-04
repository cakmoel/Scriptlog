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
          <li class="list-inline-item"><a href="#"><?= 'Memory used: <strong>'. convert_memory_used(memory_get_usage()).'</strong>'; ?></a></li>
          <li class="list-inline-item"><a href="#"><?= 'Execution time: <strong>'.(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]). ' seconds</strong>'; ?></a></li>
          <li class="list-inline-item"><a href="../readme.html" target="_blank" rel="noopener noreferrer" title="readme.html" >ReadMe</a></li>
        </ul>
      </footer>
</div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/jquery-3.3.1.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script>window.jQuery || document.write('<script src="assets/vendor/bootstrap/js/jquery-slim.min.js"><\/script>')</script>
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/vendor/popper.min.js"></script>
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="<?= $stylePath; ?>assets/vendor/bootstrap/js/holder.min.js"></script>
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
<ul class="list-group mb-3">
            <li class="list-group-item d-flex justify-content-between lh-condensed">
              <div>
                <h6 class="my-0">PHP version</h6>
                 <?php 
                if (check_php_version()) :
                
                $php_passed = 'text-success';
                $php_checked = 'fa fa-check fa-lg';
                
                endif;
                
                ?>
                <small class="<?=(isset($php_passed)) ? $php_passed : 'text-danger'; ?>"><?=(isset($php_passed)) ? PHP_VERSION : $errors['errorChecking'] = 'Requires PHP 5.6 or newer'; ?></small>
              </div>
              <span class="<?=(isset($php_passed)) ? $php_passed : 'text-danger'; ?>"><i class="<?=(isset($php_checked)) ? $php_checked : 'fa fa-close fa-lg'; ?>" aria-hidden="true"></i></span>
               
            </li>
            
            <li class="list-group-item d-flex justify-content-between lh-condensed">
              <div>
                <h6 class="my-0">Operating System</h6>
                <?php 
             
                  $osname = check_os()['Operating_system'];
                  $oslist = array(
                            'Linux', 
                            'OS X', 
                            'FreeBSD', 
                            'Chrome OS', 
                            'OpenBSD',  
                            'NetBSD', 
                            'OpenSolaris', 
                            'Windows');
                  
                  foreach ($oslist as $operating_system) :
                  
                   if ($osname === $operating_system) {
                      
                      $os_passed = 'text-success';
                      $os_checked = 'fa fa-check fa-lg';
                       
                   }
                    
                  endforeach;
                ?>
                   <small class="<?=(isset($os_passed)) ? $os_passed : 'text-danger'; ?>"><?=(isset($os_passed)) ? $osname : $errors['errorChecking'] = 'Operating System Not Supported'; ?></small>
                </div>
                <span class="<?=(isset($os_passed)) ? $os_passed : 'text-danger'; ?>"><i class="<?=(isset($os_checked)) ? $os_checked : 'fa fa-close fa-lg'; ?>"></i></span>
             
            </li>
            
             <li class="list-group-item d-flex justify-content-between lh-condensed">
              <div>
                <h6 class="my-0">Browser</h6>
                <?php 
                   
                   $browserslist = array('Chrome', 'Firefox', 'Internet Explorer', 'Opera', 'Vivaldi');
                  
                   foreach ($browserslist as $browser_name) :
                       
                       if ((check_browser() == $browser_name) && (check_browser_version() === true)) {
                        $browser_failed = "text-danger";
                        $fabrowser_close = "fa fa-close fa-lg";      
                       }
                       
                   endforeach;
                  
                ?>
                  <small class="<?=(isset($browser_failed)) ? $browser_failed : 'text-success'; ?>"><?=(isset($browser_failed)) ? $errors['errorChecking'] = 'Browser is not supported' : check_browser(). ' ' .get_browser_version(); ?></small>
                </div>
                <span class="<?=(isset($browser_failed)) ? $browser_failed : 'text-success'; ?>"><i class="<?=(isset($fabrowser_close)) ? $fabrowser_close : 'fa fa-check fa-lg'; ?> ?>"></i></span>
             
            </li>
           
             <li class="list-group-item d-flex justify-content-between lh-condensed">
              <div>
                <h6 class="my-0">Server</h6>
                <?php 
                 $web_server = check_web_server();
                 $server_name = $web_server['WebServer'];
                 $server_version = $web_server['Version'];
                 
                 $serverList = array('Apache', 'LiteSpeed', 'nginx', 'Microsoft-IIS');
                 
                 foreach ($serverList as $server) :

                 if ($server_name === $server) {
                 
                      $server_success = "text-success";
                      $server_checked = "fa fa-check fa-lg";
                      
                 }
                 
                 endforeach;
                ?>
                <small class="<?=(isset($server_success)) ? $server_success : 'text-danger'; ?>"><?=(isset($server_name)) ? $server_name.' '.$server_version : $errors['errorChecking'] = 'Web server not supported'; ?></small>
              </div>
              <span class="<?=(isset($server_success)) ? $server_success : 'text-danger'; ?>"><i class="<?=(isset($server_checked)) ? $server_checked : 'fa fa-close fa-lg'; ?>"></i></span>
             
            </li>
            
          </ul>
<?php
}

// required settings
function required_settings()
{
?>

<ul class="list-group mb-3">

<li class="list-group-item d-flex justify-content-between lh-condensed">
      <div>
        
        <h6 class="my-0">PCRE UTF-8</h6>
                
            <?php 
                
                if (check_pcre_utf8() === true ) {
                    $pcre_failed = 'text-danger';
                    $pcre_close = 'fa fa-close fa-lg';
                }

            ?>

                <small class="<?=(isset($pcre_failed)) ? $pcre_failed : 'text-success'; ?>"><?=(isset($pcre_failed)) ? $errors['errorChecking'] = 'PCRE has not been compiled with UTF-8 or Unicode property support' : 'Pass' ?></small>
              </div>
              <span class="<?=(isset($pcre_failed)) ? $pcre_failed : 'text-success'; ?>"><i class="<?=(isset($pcre_close)) ? $pcre_close : 'fa fa-check fa-lg' ?>"></i></span>
    </li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
              
      <div>
                
        <h6 class="my-0">SPL Autoload Register</h6>
                
          <?php 
                
            if (check_spl_enabled('spl_autoload_register')) {
                    $spl_passed = 'text-success';
                    $spl_checked = 'fa fa-check fa-lg';
            }
                
          ?>
              
            <small class="<?=(isset($spl_passed)) ? $spl_passed : 'text-danger'; ?>"><?=(isset($spl_passed)) ? 'Pass' : $errors['errorChecking'] = 'spl autoload register is either not loaded or compiled in'; ?></small>
            </div>
            <span class="<?=(isset($spl_passed)) ? $spl_passed : 'text-danger'; ?>"><i class="<?=(isset($spl_checked)) ? $spl_checked : 'fa fa-close fa-lg'; ?>"></i></span>
            
</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
    <div>
                
      <h6 class="my-0">Filters Enabled</h6>
                
          <?php 
                
            if (check_filter_enabled()) {
                    $filter_passed = 'text-success';
                    $filter_checked = 'fa fa-check fa-lg';
                
            }
                
          ?>
                
        <small class="<?=(isset($filter_passed) ? $filter_passed : 'text-danger');  ?>"><?=(isset($filter_passed) ? 'Pass' : $errors['errorChecking'] = 'The filter extension is either not loaded or compiled in') ; ?></small>
        </div>
      <span class="<?=(isset($filter_passed) ? $filter_passed : 'text-danger'); ?>"><i class="<?=(isset($filter_checked) ? $spl_checked : 'fa fa-close fa-lg' ) ; ?>"></i></span>
            
</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
              
    <div>
                
        <h6 class="my-0">Iconv Extension Loaded</h6>
                
            <?php 
                
              if (check_iconv_enabled()) {
                    
                  $iconv_passed = 'text-success';
                  $iconv_checked = 'fa fa-check fa-lg';
                
              }
                
            ?>

      <small class="<?=(isset($iconv_passed)) ? $iconv_passed : 'text-danger'; ?>"><?=(isset($iconv_passed)) ? 'Pass' : $errors['errorChecking'] = 'Scriptlog requires the Iconv extension'; ?></small>
      </div>
      <span class="<?=(isset($iconv_passed)) ? $iconv_passed : 'text-danger'; ?>"><i class="<?=(isset($iconv_checked)) ? $iconv_checked : 'fa fa-close fa-lg'; ?>"></i></span>
            
</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
              
    <div>
                
        <h6 class="my-0">Mbstring Extension Loaded</h6>
                
            <?php 
                
              if (check_mbstring_enabled()) {
                    
                  $mbstring_passed = 'text-success';
                  $mbstring_checked = 'fa fa-check fa-lg';
                
              }
                
            ?>

      <small class="<?=(isset($mbstring_passed)) ? $mbstring_passed : 'text-danger'; ?>"><?=(isset($mbstring_passed)) ? 'Pass' : $errors['errorChecking'] = 'Scriptlog requires the Multibyte String extension'; ?></small>
      </div>
      <span class="<?=(isset($mbstring_passed)) ? $mbstring_passed : 'text-danger'; ?>"><i class="<?=(isset($mbstring_checked)) ? $mbstring_checked : 'fa fa-close fa-lg'; ?>"></i></span>
            
</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
              
    <div>
                
        <h6 class="my-0">Fileinfo Extension Loaded</h6>
                
            <?php 
                
              if (check_fileinfo_enabled()) {
                    
                  $fileinfo_passed = 'text-success';
                  $fileinfo_checked = 'fa fa-check fa-lg';
                
              }
                
            ?>

      <small class="<?=(isset($fileinfo_passed)) ? $fileinfo_passed : 'text-danger'; ?>"><?=(isset($fileinfo_passed)) ? 'Pass' : $errors['errorChecking'] = 'Scriptlog requires the fileinfo extension'; ?></small>
      </div>
      <span class="<?=(isset($fileinfo_passed)) ? $fileinfo_passed : 'text-danger'; ?>"><i class="<?=(isset($fileinfo_checked)) ? $fileinfo_checked : 'fa fa-close fa-lg'; ?>"></i></span>
            
</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
    <div>
      
      <h6 class="my-0">Character Type Extension</h6>
                
          <?php 
              if (check_character_type()) {
                  $ctype_failed = 'text-danger';
                  $ctype_close = 'fa fa-close fa-lg';
              }
                
          ?>

    <small class="<?=(isset($ctype_failed)) ? $ctype_failed : 'text-success'; ?>"><?=(isset($ctype_failed)) ? $errors['errorChecking'] = 'The ctype extension is overloading PHP\'s native string functions' : 'Pass' ; ?></small>
    </div>
    <span class="<?=(isset($ctype_failed)) ? $ctype_failed : 'text-success' ; ?>"><i class="<?=(isset($ctype_close)) ?  $ctype_close : 'fa fa-check fa-lg'; ?>"></i></span>
            
</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
              
    <div>
                
        <h6 class="my-0">GD Extension Loaded</h6>
                
            <?php 
                
              if (check_gd_enabled()) {
                    
                    $gd_passed = 'text-success';
                    $gd_check = 'fa fa-check fa-lg';
              }
                
            ?>
      <small class="<?=(isset($gd_passed)) ? $gd_passed : 'text-danger'; ?>"><?=(isset($gd_passed)) ? 'Pass' : $errors['errorChecking'] = 'requires GD v2 for the image manipulation'; ?></small>
      </div>
      <span class="<?=(isset($gd_passed)) ? $gd_passed : 'text-danger' ; ?>"><i class="<?=(isset($gd_check)) ?  $gd_check : 'fa fa-close fa-lg'; ?>"></i></span>
            
</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
              
  <div>
    
    <h6 class="my-0">PDO MySQL Enabled</h6>
        <?php 
          if (check_pdo_mysql()) {
                    
              $pdo_passed = 'text-success';
              $pdo_check = 'fa fa-check fa-lg';
                
          }
                
        ?>

      <small class="<?=(isset($pdo_passed)) ? $pdo_passed : 'text-danger'; ?>"><?=(isset($pdo_passed)) ? 'Pass' : $errors['errorChecking'] = 'requires PDO MySQL enabled'; ?></small>
      </div>
    <span class="<?=(isset($pdo_passed)) ? $pdo_passed : 'text-danger' ; ?>"><i class="<?=(isset($pdo_check)) ?  $pdo_check : 'fa fa-close fa-lg'; ?>"></i></span>

</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
    <div>
      <h6 class="my-0">MySQL Improved Enabled</h6>
                
            <?php 
              if (check_mysqli_enabled()) {
                  $mysqli_passed = 'text-success';
                  $mysqli_check = 'fa fa-check fa-lg';
              }
                
            ?>
                
    <small class="<?=(isset($mysqli_passed)) ? $mysqli_passed : 'text-danger'; ?>"><?=(isset($mysqli_passed)) ? 'Pass' : $errors['errorChecking'] = 'requires MySQL improved enabled'; ?></small>
    </div>
    <span class="<?=(isset($mysqli_passed)) ? $mysqli_passed : 'text-danger' ; ?>"><i class="<?=(isset($mysqli_check)) ?  $mysqli_check : 'fa fa-close fa-lg'; ?>"></i></span>
            
</li>

<li class="list-group-item d-flex justify-content-between lh-condensed">
    <div>
      
      <h6 class="my-0">URI Determination</h6>
                
          <?php 
            if (check_uri_determination()) {
                  $uri_passed = 'text-success';
                  $uri_check = 'fa fa-check fa-lg';
            }
                
          ?>
                
      <small class="<?=(isset($uri_passed)) ? $uri_passed : 'text-danger'; ?>"><?=(isset($uri_passed)) ?  'Pass' : $errors['errorChecking'] = 'Neither $_SERVER[REQUEST_URI], $_SERVER[PHP_SELF] or $_SERVER[PATH_INFO] is available' ; ?></small>
      </div>
      <span class="<?=(isset($uri_passed)) ? $uri_passed : 'text-danger' ; ?>"><i class="<?=(isset($uri_check)) ?  $uri_check : 'fa fa-close fa-lg'; ?>"></i></span>
            
</li>

</ul>

<?php
}

function check_mod_rewrite()
{
  if (true === check_modrewrite()) :
    $mode_rewrite_passed = 'text-success';
    $mode_rewrite_check = 'fa fa-check fa-lg';

?>
      <h4 class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-muted">Modes</span>
      </h4>
           
      <ul class="list-group mb-3">
        <li class="list-group-item d-flex justify-content-between lh-condensed">
              
        <div>
          
          <h6 class="my-0">Mode Rewrite</h6>
            <small class="<?=(isset($mode_rewrite_passed)) ? $mode_rewrite_passed : 'text-danger'; ?>"><?=(isset($mode_rewrite_passed)) ? 'Pass' : $errors['errorChecking'] = 'Requires mode rewrite enabled'; ?></small>
          </div>
          <span class="<?=(isset($mode_rewrite_passed)) ? $mode_rewrite_passed : 'text-danger'; ?>"><i class="<?=(isset($mode_rewrite_check)) ? $mode_rewrite_check : 'fa fa-close fa-lg'; ?>"></i></span>
            
        </li>
          
      </ul>
            
<?php
endif;

}

function check_dir_file()
{
?>
<ul class="list-group mb-3">
          
    <li class="list-group-item d-flex justify-content-between lh-condensed" >
              
        <div>
                
          <h6 class="my-0">Main Engine</h6>
                 
            <?php 
              
              if (check_main_dir()) :
                   
                $main_passed = 'text-success';
                $main_checked = 'fa fa-check fa-lg';
                   
              endif;

            ?>

          <small class="<?=( isset($main_passed) ? $main_passed : 'text-danger'); ?>"><?=( isset($main_passed) ? 'Pass' : $errors['errorChecking'] = 'Required file not found'); ?></small>
          </div>
          <span class="<?=( isset($main_passed) ? $main_passed : 'text-danger' ); ?>"><i class="<?=( isset($main_checked) ? $main_checked : 'fa fa-close fa-lg') ; ?>"></i></span>
     </li>
            
      <li class="list-group-item d-flex justify-content-between lh-condensed" >
            <div>
                <h6 class="my-0">Load Engine</h6>
                 <?php 
                  if (check_loader()) :
                   
                   $init_passed = 'text-success';
                   $init_checked = 'fa fa-check fa-lg';
                   
                  endif;
                 ?>
                  <small class="<?=(isset($init_passed)) ? $init_passed : 'text-danger'; ?>"><?=(isset($init_passed)) ? 'Pass' : $errors['errorChecking'] = 'Required file not found'; ?></small>
              </div>
              <span class="<?=(isset($init_passed)) ? $init_passed : 'text-danger' ?>"><i class="<?=(isset($init_checked)) ? $init_checked : 'fa fa-close fa-lg'; ?>"></i></span>
            
        </li>
            
        <li class="list-group-item d-flex justify-content-between lh-condensed" >
              <div>
                 <h6 class="my-0">Logs Directory</h6>
                 <?php 
                  if (check_log_dir()) :
                   
                   $log_passed = 'text-success';
                   $log_checked = 'fa fa-check fa-lg';
                   
                  endif;
                 ?>
                  <small class="<?=(isset($log_passed)) ? $log_passed : 'text-danger'; ?>"><?=(isset($log_passed)) ? 'public/log writeable' : $errors['errorChecking'] = 'public/log is not writeable'; ?></small>
              </div>
              <span class="<?=(isset($log_passed)) ? $log_passed : 'text-danger' ?>"><i class="<?=(isset($log_checked)) ? $log_checked : 'fa fa-close fa-lg'; ?>"></i></span>
        </li>
            
        <li class="list-group-item d-flex justify-content-between lh-condensed" >
              <div>
                 <h6 class="my-0">Cache Directory</h6>
                 <?php 
                  if (check_cache_dir()) :
                   
                   $cache_passed = 'text-success';
                   $cache_checked = 'fa fa-check fa-lg';
                   
                  endif;
                 ?>
                  <small class="<?=(isset($cache_passed)) ? $cache_passed : 'text-danger'; ?>"><?=(isset($cache_passed)) ? 'public/cache writeable' : $errors['errorChecking'] = 'public/cache is not writeable'; ?></small>
              </div>
              <span class="<?=(isset($cache_passed)) ? $cache_passed : 'text-danger' ?>"><i class="<?=(isset($cache_checked)) ? $cache_checked : 'fa fa-close fa-lg'; ?>"></i></span>
        </li>

        <li class="list-group-item d-flex justify-content-between lh-condensed" >
            
            <div>
                 
              <h6 class="my-0">Theme Directory</h6>
                 
                 <?php 
                  if (check_theme_dir()) :
                   
                   $theme_passed = 'text-success';
                   $theme_checked = 'fa fa-check fa-lg';
                   
                  endif;
                 ?>
              <small class="<?=(isset($theme_passed)) ? $theme_passed : 'text-danger'; ?>"><?=(isset($theme_passed)) ? 'public/themes writeable' : $errors['errorChecking'] = 'public/themes is not writeable'; ?></small>
              </div>
              <span class="<?=(isset($theme_passed)) ? $theme_passed : 'text-danger' ?>"><i class="<?=(isset($theme_checked)) ? $theme_checked : 'fa fa-close fa-lg'; ?>"></i></span>
        </li>

        <li class="list-group-item d-flex justify-content-between lh-condensed" >
              
          <div>
            <h6 class="my-0">Plugin Directory</h6>
                 
              <?php 
                  
                if (check_plugin_dir()) :
                   
                  $plugin_passed = 'text-success';
                  $plugin_checked = 'fa fa-check fa-lg';
                   
                  endif;
              ?>
             <small class="<?=(isset($plugin_passed)) ? $plugin_passed : 'text-danger'; ?>"><?=(isset($plugin_passed)) ? 'admin/plugins writeable' : $errors['errorChecking'] = 'admin/plugins is not writeable'; ?></small>
             </div>
            <span class="<?=(isset($plugin_passed)) ? $plugin_passed : 'text-danger' ?>"><i class="<?=(isset($plugin_checked)) ? $plugin_checked : 'fa fa-close fa-lg'; ?>"></i></span>
            </li>

  </ul>

<?php

}