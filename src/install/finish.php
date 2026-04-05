<?php

/**
 * File finish.php
 *
 * @category  installation file -- finish.php
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   0.1
 * @since     Since Release 0.1
 *
 */

require dirname(__FILE__) . '/include/settings.php';
require dirname(__FILE__) . '/include/setup.php';
require dirname(__FILE__) . '/install-layout.php';

$current_path = preg_replace("/\/index\.php.*$/i", "", current_url());

install_header($current_path, $protocol, $server_host);

?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="card shadow-sm border-0 py-5">
                <div class="card-body">
                    <img class="install-icon mb-4" src="<?= $current_path; ?>assets/img/icon612x612.png" alt="Scriptlog Logo">
                    <h2 class="font-weight-bold mb-3">Scriptlog</h2>
                    
                    <?php
                    if (
                        !isset($_GET['status']) || empty($_GET['status']) || $_GET['status'] !== 'success'
                          || !isset($_GET['token']) || !isset($_SESSION['token']) || empty($_GET['token'])
                          || $_GET['token'] !== $_SESSION['token']
                    ) :
                        ?>
                        <div class="py-4">
                            <i class="fa fa-exclamation-circle text-warning fa-4x mb-3"></i>
                            <p class="lead">Oops! Installation is already complete.</p>
                            <p class="text-muted">Redirecting you to the login page...</p>
                        </div>
                        
                        <script>
                        function leave() {
                            window.location = "../admin/login.php";
                        }
                        setTimeout("leave()", 3000);
                        </script>

                    <?php else :
                        ?>
                        <div class="py-4">
                            <i class="fa fa-check-circle text-success fa-4x mb-3"></i>
                            <h3 class="text-success mb-3">Installation Successful!</h3>
                            <p class="lead mb-4">Your blog is ready to go. You can now log in to the admin panel and start blogging.</p>
                            
                            <?php
                                # Show server-specific instructions
                            if (isset($_SESSION['server_config']) && $_SESSION['server_config']['web_server'] === 'Nginx') :
                                ?>
                                <div class="alert alert-warning text-left mt-3 mb-4">
                                    <h5><i class="fa fa-info-circle"></i> Nginx Configuration Required</h5>
                                    <p class="mb-2">Since you're using Nginx, please add this to your Nginx vhost config:</p>
                                    <pre class="bg-light p-3 rounded"><code>include <?= dirname(__DIR__, 2); ?>/nginx-rewrites.conf;</code></pre>
                                    <p class="mb-0 text-muted"><small>Then restart Nginx for changes to take effect.</small></p>
                                </div>
                            <?php endif; ?>
                            
                            <a href="<?= setup_base_url($protocol, $server_host) . '/admin/login.php'; ?>" class="btn btn-success btn-lg shadow-sm px-5">
                                <i class="fa fa-sign-in mr-2"></i> Log In to Dashboard
                            </a>
                        </div>
                          <?php
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

(isset($_SESSION['token']) ? purge_installation() : session_destroy());
install_footer($current_path, $protocol, $server_host);

?>
