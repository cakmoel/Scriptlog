<footer class="main-footer">
  <div class="container">
    <?php
    // Retrieve footer navigation from tbl_menu using existing utility function
    $footerMenus = theme_navigation('public');
    $footerMenuItems = [];
    
    if (isset($footerMenus['items']) && !empty($footerMenus['items'])) {
        $footerMenuItems = array_values($footerMenus['items']);
    }
    ?>
    
    <?php if (!empty($footerMenuItems)) : ?>
    <div class="row footer-navigation" role="navigation" aria-label="<?= t('footer.navigation.aria_label'); ?>">
      <div class="col-12">
        <ul class="footer-nav list-inline text-center mb-4">
          <?php foreach ($footerMenuItems as $item) : ?>
            <?php 
            // Skip parent items with children in footer (keep flat)
            $isParent = false;
            foreach ($footerMenuItems as $checkItem) {
                if (isset($checkItem['parent_id']) && $checkItem['parent_id'] == $item['ID']) {
                    $isParent = true;
                    break;
                }
            }
            if ($isParent) continue;
            ?>
            <li class="list-inline-item">
              <a href="<?= htmlout($item['menu_link'] ?? '#'); ?>" class="footer-nav-link">
                <?= htmlout($item['menu_label'] ?? ''); ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>
    
    <hr class="footer-divider">
    
    <!-- Social Links Section (optional - can be managed via admin in future) -->
    <div class="row footer-social">
      <div class="col-12 text-center">
        <ul class="social-menu list-inline mb-4">
          <li class="list-inline-item">
            <a href="#" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
              <i class="fa fa-facebook" aria-hidden="true"></i>
            </a>
          </li>
          <li class="list-inline-item">
            <a href="#" aria-label="Twitter" target="_blank" rel="noopener noreferrer">
              <i class="fa fa-twitter" aria-hidden="true"></i>
            </a>
          </li>
          <li class="list-inline-item">
            <a href="#" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
              <i class="fa fa-instagram" aria-hidden="true"></i>
            </a>
          </li>
          <li class="list-inline-item">
            <a href="#" aria-label="LinkedIn" target="_blank" rel="noopener noreferrer">
              <i class="fa fa-linkedin" aria-hidden="true"></i>
            </a>
          </li>
          <li class="list-inline-item">
            <a href="<?= app_url(); ?>/rss.php" aria-label="RSS Feed">
              <i class="fa fa-rss" aria-hidden="true"></i>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>
  <div class="copyrights">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <p><?= copyright() . "\r"; ?><?= year_on_footer(date("Y")); ?>. <?= t('footer.copyright'); ?>. <?= function_exists('app_sitename') ? app_sitename() : ""; ?> </p>
        </div>
        <div class="col-md-6 text-right">
          <p>Template By <a href="https://bootstrapious.com" class="text-white">Ondrej Svetska</a>
            <!-- Please do not remove the backlink to Bootstrap Temple unless you purchase an attribution-free license @ Bootstrap Temple or support us at http://bootstrapious.com/donate. It is part of the license conditions. Thanks for understanding :)                         -->
          </p>
        </div>
      </div>
    </div>
  </div>
</footer>
<!-- JavaScript files-->
<script src="<?= theme_dir(); ?>assets/vendor/jquery/jquery.min.js"></script>
<script src="<?= theme_dir(); ?>assets/vendor/popper.js/umd/popper.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/vendor/bootstrap/js/bootstrap.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/vendor/jquery.cookie/jquery.cookie.js" defer></script>
<script src="<?= theme_dir(); ?>assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/front.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.marquee.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.pause.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.easing.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/comment-submission.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/load-comment.min.js?v=1.2" defer></script>
<script src="<?= theme_dir(); ?>assets/js/validator.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/wow.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/sina-nav.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/cookie-consent.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/search.min.js" defer></script>
<script src="<?= theme_dir(); ?>assets/js/unlock-post.min.js" defer></script>
<?php if (is_rtl()) : ?>
<script src="<?= theme_dir(); ?>assets/js/rtl.min.js" defer></script>
<?php endif; ?>

<!-- For All Plug-in Activation & Others -->
 <script type="text/javascript">
        window.addEventListener('load', function() {
            if (typeof WOW !== 'undefined') {
                new WOW().init();
            }
        });
</script>

<!-- Cookie Consent Banner -->
<?php
if (function_exists('should_show_consent_banner') && should_show_consent_banner()) {
    if (file_exists(__DIR__ . '/cookie-consent.php')) {
        include __DIR__ . '/cookie-consent.php';
    }
}
?>

</body>
</html>