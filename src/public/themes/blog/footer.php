</main>

<footer class="main-footer" role="contentinfo">
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
            <a href="#" aria-label="Facebook" title="Facebook" target="_blank" rel="noopener noreferrer">
              <i class="fa fa-facebook" aria-hidden="true"></i>
            </a>
          </li>
          <li class="list-inline-item">
            <a href="#" aria-label="Twitter" title="Twitter" target="_blank" rel="noopener noreferrer">
              <i class="fa fa-twitter" aria-hidden="true"></i>
            </a>
          </li>
          <li class="list-inline-item">
            <a href="#" aria-label="Instagram" title="Instagram" target="_blank" rel="noopener noreferrer">
              <i class="fa fa-instagram" aria-hidden="true"></i>
            </a>
          </li>
          <li class="list-inline-item">
            <a href="#" aria-label="LinkedIn" title="LinkedIn" target="_blank" rel="noopener noreferrer">
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
<script src="<?= theme_dir(); ?>assets/vendor/jquery/jquery.min.js" integrity="sha384-vtXRMe3mGCbOeY7l30aIg8H9p3GdeSe4IFlP6G8JMa7o7lXvnz3GFKzPxzJdPfGK" crossorigin="anonymous"></script>
<script src="<?= theme_dir(); ?>assets/vendor/popper.js/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/vendor/bootstrap/js/bootstrap.min.js" integrity="sha384-uefMccjFJAIv6A+rW+L4AHf99KvxDjWSu1z9VI8SKNVmz4sk7buKt/6v9KI65qnm" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/vendor/jquery.cookie/jquery.cookie.js" integrity="sha384-ch1nZWLCNJ31V+4aC8U2svT7i40Ru+O8WHeLF4Mvq4aS7VD5ciODxwuOCdkIsX86" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.js" integrity="sha384-9P6MXH8lfxrzYEF6RdCaepmJsgsERWZGoUT0A7GtFJnA3drRC/UFhapoG1ETT/G/" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/front.min.js" integrity="sha384-p8/ptwh75dWpkRgwFM0+kBa446gJ6bMsowJdjYc1U6pjNjxBUa8EDyN0BZuQ1qYp" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.marquee.min.js" integrity="sha384-WQc1hOZkcOOSA84mSoGlpISELGiMCzndtE9DqqiDoYBcUD65cMZqNR9Ut8Pfl5Sj" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.pause.min.js" integrity="sha384-ozxO7tw8nUwiSvQ7WYLD9xV5KlhtBKrlM3ej/cvXGEdJgc7NDJxAEzm9f/dzi5Ne" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.easing.min.js" integrity="sha384-leGYpHE9Tc4N9OwRd98xg6YFpB9shlc/RkilpFi0ljr3QD4tFoFptZvgnnzzwG4Q" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/comment-submission.min.js" integrity="sha384-RO3HpMEOwGndTi+GQwg/oakdqyhXLrWUPeGfAELqbgICG3BcdM732oGsGvlwB30G" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/load-comment.min.js?v=1.2" integrity="sha384-21BdLjc4mbzBjh9r01nbkCzZRI1O6nFEUvxoIm25e6Wpr9o1woH42G46EG/ccoFC" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/validator.min.js" integrity="sha384-IVLGjVZ5yAK+F5rq00pVxaH2zVPOHaexaLQTy+iLpU8XTO4vdBpAxwX3VvilNzFe" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/wow.min.js" integrity="sha384-XOseRua7mFtme3Rj2toJG4TV6dhOfTuxqFD12kXAlEvssIFjcpwaC/MTx8+6BUPN" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/sina-nav.min.js" integrity="sha384-9iszGKzSIhu1BEKZdzZuu6WOLXRZuvE1AMg5nLn+Zi1t2C09NuIEywYzOk/lAJ1P" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/cookie-consent.min.js" integrity="sha384-+Yc5DNW9JhQFG7c07tVmd1yk03uqFBDaobohF9qjWMAoP5LIuTTt6riEMj3xKl9A" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/search.min.js" integrity="sha384-u4L0NfVauqq3qGwgarvTQFK1tmGbmWOuwJ2124epDNxXFbUQKzwGY+ulm6tqxwLU" crossorigin="anonymous" defer></script>
<script src="<?= theme_dir(); ?>assets/js/unlock-post.min.js" integrity="sha384-NkKzpDeSB682ZQqYMFuEcsghhPX6gzWxD8phHVGSijG/yQM8t2TcskjfBr8GAo1J" crossorigin="anonymous" defer></script>
<?php if (is_rtl()) : ?>
<script src="<?= theme_dir(); ?>assets/js/rtl.min.js" integrity="sha384-1+uymlvUWF7Mb7OqV3KH8vk4U+VtQ7p0j7e3HDd2yOZq/8fqkK0BwaeonSlO1+KR" crossorigin="anonymous" defer></script>
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