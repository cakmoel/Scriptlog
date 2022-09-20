<footer class="main-footer">
  <div class="container">
    <!--
        <div class="row">
          <div class="col-md-4">
            <div class="logo">
              <h6 class="text-white">Bootstrap Blog</h6>
            </div>
            <div class="contact-details">
              <p>53 Broadway, Broklyn, NY 11249</p>
              <p>Phone: (020) 123 456 789</p>
              <p>Email: <a href="mailto:info@company.com">Info@Company.com</a></p>
              <ul class="social-menu">
                <li class="list-inline-item"><a href="#"><i class="fa fa-facebook"></i></a></li>
                <li class="list-inline-item"><a href="#"><i class="fa fa-twitter"></i></a></li>
                <li class="list-inline-item"><a href="#"><i class="fa fa-instagram"></i></a></li>
                <li class="list-inline-item"><a href="#"><i class="fa fa-behance"></i></a></li>
                <li class="list-inline-item"><a href="#"><i class="fa fa-pinterest"></i></a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-4">
            <div class="menus d-flex">
              <ul class="list-unstyled">
                <li> <a href="#">My Account</a></li>
                <li> <a href="#">Add Listing</a></li>
                <li> <a href="#">Pricing</a></li>
                <li> <a href="#">Privacy &amp; Policy</a></li>
              </ul>
              <ul class="list-unstyled">
                <li> <a href="#">Our Partners</a></li>
                <li> <a href="#">FAQ</a></li>
                <li> <a href="#">How It Works</a></li>
                <li> <a href="#">Contact</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-4">
            <div class="latest-posts"><a href="#">
                <div class="post d-flex align-items-center">
                  <div class="image"><img src="/img/small-thumbnail-1.jpg" alt="..." class="img-fluid"></div>
                  <div class="title"><strong>Hotels for all budgets</strong><span class="date last-meta">October 26, 2016</span></div>
                </div></a><a href="#">
                <div class="post d-flex align-items-center">
                  <div class="image"><img src="/img/small-thumbnail-2.jpg" alt="..." class="img-fluid"></div>
                  <div class="title"><strong>Great street atrs in London</strong><span class="date last-meta">October 26, 2016</span></div>
                </div></a><a href="#">
                <div class="post d-flex align-items-center">
                  <div class="image"><img src="/img/small-thumbnail-3.jpg" alt="..." class="img-fluid"></div>
                  <div class="title"><strong>Best coffee shops in Sydney</strong><span class="date last-meta">October 26, 2016</span></div>
                </div></a></div>
          </div>
        </div>
-->

  </div>
  <div class="copyrights">
    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <p><?= copyright() . "\r"; ?><?= year_on_footer(date("Y")); ?>. All rights reserved. <?= app_info()['site_name']; ?> </p>
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
<script src="<?= theme_dir(); ?>assets/vendor/popper.js/umd/popper.min.js"></script>
<script src="<?= theme_dir(); ?>assets/vendor/bootstrap/js/bootstrap.min.js"></script>
<script src="<?= theme_dir(); ?>assets/vendor/jquery.cookie/jquery.cookie.js"></script>
<script src="<?= theme_dir(); ?>assets/vendor/@fancyapps/fancybox/jquery.fancybox.min.js"></script>
<script src="<?= theme_dir(); ?>assets/js/front.js"></script>
<script src="<?= theme_dir(); ?>assets/js/multidropdown.js"></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.marquee.js"></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.pause.min.js"></script>
<script src="<?= theme_dir(); ?>assets/js/jquery.easing.min.js"></script>
<script src="<?= theme_dir(); ?>assets/js/comment.js"></script>
<script type="text/javascript" src="<?= theme_dir(); ?>assets/js/validator.min.js"></script>

</body>
</html>