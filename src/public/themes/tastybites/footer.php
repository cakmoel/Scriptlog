<?php
defined('SCRIPTLOG') || die('Direct access not permitted');
?>

<footer>
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <h5><?php 
                $siteName = function_exists('app_sitename') ? app_sitename() : '';
                echo !empty($siteName) && $siteName !== 'Scriptlog 1.0' ? tastybites_htmlout($siteName) : 'TastyBites'; 
                ?></h5>
                <p><?php 
                $tagline = function_exists('app_tagline') ? app_tagline() : '';
                echo !empty($tagline) && $tagline !== 'Just another personal weblog' ? tastybites_htmlout($tagline) : 'Sharing delicious recipes and food stories'; 
                ?></p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="fa fa-facebook"></i></a>
                    <a href="#" aria-label="Twitter"><i class="fa fa-twitter"></i></a>
                    <a href="#" aria-label="Instagram"><i class="fa fa-instagram"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fa fa-linkedin"></i></a>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <h6><?= t('nav.categories'); ?></h6>
                <ul class="footer-links">
                    <?php
                    $categories = sidebar_topics();
                    foreach ($categories as $cat) :
                    ?>
                    <li><a href="<?= permalinks($cat['ID'])['cat']; ?>"><?= tastybites_htmlout($cat['topic_title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <h6><?= t('nav.menu'); ?></h6>
                <ul class="footer-links">
                    <li><a href="<?= app_url(); ?>"><?= t('nav.home'); ?></a></li>
                    <li><a href="<?= app_url(); ?>/archives"><?= t('nav.archives'); ?></a></li>
                    <li><a href="<?= app_url(); ?>/?s="><?= t('nav.search'); ?></a></li>
                    <?php
                    $footerMenus = theme_navigation('footer');
                    echo front_navigation(0, $footerMenus);
                    ?>
                </ul>
            </div>
        </div>
        <div class="copyright">
            &copy; <?= date('Y'); ?> <?php 
            $siteName = function_exists('app_sitename') ? app_sitename() : '';
            echo !empty($siteName) && $siteName !== 'Scriptlog 1.0' ? tastybites_htmlout($siteName) : 'TastyBites'; 
            ?>. <?= t('footer.powered_by'); ?>.
        </div>
    </div>
</footer>

<?php if (function_exists('theme_dir')) : ?>
<script src="<?= theme_dir(); ?>assets/vendor/jquery/jquery.min.js"></script>
<script src="<?= theme_dir(); ?>assets/vendor/bootstrap/js/popper.min.js"></script>
<script src="<?= theme_dir(); ?>assets/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="<?= theme_dir(); ?>assets/js/front.min.js"></script>
<?php if (is_rtl()) : ?>
<script src="<?= theme_dir(); ?>assets/js/rtl.min.js"></script>
<?php endif; ?>
<?php endif; ?>

<?php 
if (!function_exists('render_cookie_consent')) {
    require_once dirname(__FILE__) . '/cookie-consent.php';
}
echo render_cookie_consent(); 
?>

</body>
</html>