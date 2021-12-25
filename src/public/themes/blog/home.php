 <?php 
 
$hero_headlines = featured_post();

if ( isset($hero_headlines) ) :

   foreach ($hero_headlines as $hero_headline) {
     
    $featured_hero_id = isset($hero_headline['ID']) ? safe_html((int)$hero_headline['ID']) : ""; 
    $featured_hero_img = isset($hero_headline['media_filename']) ? safe_html($hero_headline['media_filename']) : "";
    $featured_hero_title = isset($hero_headline['post_title']) ? safe_html($hero_headline['post_title']) : "";

   }

?>

<!-- Hero Section--> 
<section style="background: url('<?= isset($featured_hero_img) ? invoke_webp_image($featured_hero_img, false) : "https://via.placeholder.com/1920x1438";?>'); background-size: cover; background-position: center center" class="hero transparent">
      <div class="container">
        <div class="row">
          <div class="col-lg-7">
            <h1 style="color: #379392;"><?=isset($featured_hero_title) ? $featured_hero_title : ""; ?></h1>
            <a href="<?= permalinks($featured_hero_id)['post']; ?>" class="hero-link">Discover More</a>
          </div>
        </div><a href=".intro" class="continue link-scroll"><i class="fa fa-long-arrow-down"></i> Scroll Down</a>
      </div>
</section>

<?php 
else:

  echo nothing_found();

endif;
?>

<?php 

$sticky_page = sticky_page();

 if ( is_array($sticky_page) ) :

   foreach ($sticky_page as $sticky) :
     
    $sticky_title = isset($sticky['post_title']) ? safe_html($sticky['post_title']) : "";
    $sticky_content = isset($sticky['post_content']) ? safe_html($sticky['post_content']) : "";
     
   endforeach; 

?>

<!-- Intro Section-->
<section class="intro">
      <div class="container">
        <div class="row">
          <div class="col-lg-8">
         
  <h2 class="h3"><?= isset($sticky_title) ? $sticky_title : ""; ?></h2>
            <p class="text-big">
              <?= isset($sticky_content) ? $sticky_content : ""; ?>
            </p>
          
          </div>
        </div>
      </div>
</section>

<?php 
else :

  echo nothing_found();

endif; 
?>

<!-- random post/featured post -->
<section class="featured-posts no-padding-top">
  <div class="container">
        
<?php 
    
$random_posts = random_posts(3);

if ( is_array($random_posts) ) :

    $r = 0;

  foreach ($random_posts as $random_post) :

    $r++;

    if ( $r++ % 2 == 1) :

?>

<!-- Random Post-->
        <div class="row d-flex align-items-stretch">
          <div class="text col-lg-7">
            <div class="text-inner d-flex align-items-center">
              <div class="content">

        <?php 
              
        $post_topics = retrieve_post_topic(isset($random_post['ID']) ? (int)$random_post['ID'] : "");

        if (is_array($post_topics)) :

            $topic_links = [];
           
            foreach ( $post_topics as $post_topic ) :
     
              $random_post_img = isset($random_post['media_filename']) ? safe_html($random_post['media_filename']) : "";
              $random_post_author = (isset($random_post['user_fullname']) || isset($random_post['user_login']) ) ? safe_html($random_post['user_fullname']) : safe_html($random_post['user_login']);
              $topic_slug = isset($post_topic['topic_slug']) ? safe_html($post_topic['topic_slug']) : "";
              $topic_title = isset($post_topic['topic  title']) ? safe_html($post_topic['topic_title']) : "";

              $topic_links[] = "<a href='".app_url().DS."category'".DS."$topic_slug.'>".$topic_title."</a>";

           endforeach;

        endif;
              
       ?>
              
          <header class="post-header">
              <div class="category">
                    <?= isset($topic_links) ? implode(", ", $topic_links) : "";?>
                  </div>
                  <a href="#">
                    <h2 class="h4"> 
                       <?=isset($random_post['post_title']) ? safe_html($random_post['post_title']) : ""; ?> 
                    </h2>
                  </a>
                </header>

                <p><?= isset($random_post['post_content']) ? paragraph_l2br($random_post['post_content']) : ""; ?></p></p>
                <footer class="post-footer d-flex align-items-center">
                  <a href="#" class="author d-flex align-items-center flex-wrap">
                    <div class="avatar"><i class="fa fa-user-circle"></i></div>
                    <div class="title"><span><?= isset($random_post_author) ? $random_post_author : ""; ?></span></div>
                  </a>
                  <div class="date"><i class="icon-clock"></i> <?= isset($random_post['post_modified']) ? safe_html(make_date($random_post['post_modified'])) : safe_html(make_date($random_post['post_date'])); ?></div>
                </footer>
              </div>
            </div>
          </div>
          <div class="image col-lg-5"><img src="<?=isset($random_post_img) ? invoke_webp_image($random_post_img) : "https://via.placeholder.com/516x344"; ?>" alt="<?= isset($random_post['post_title']) ? safe_html($random_post['post_title']) : ""; ?>"></div>
        </div>
            
<?php  
     endif;
   endforeach; 
  else:
     echo nothing_found();
  endif;   
?>
  </div>
      <!--.container-->
</section>

<!-- divider section -->

<?php 
$divider_contents = featured_post();

if ( is_array( $divider_contents ) ) :

  foreach ($divider_contents as $divider_content) {
    
    $featured_divider_img = isset($divider_content['media_filename']) ? safe_html($divider_content['media_filename']) : "";
     
  }

?>

<section style="background: url(https://via.placeholder.com/1920x1280); background-size: cover; background-position: center bottom" class="divider">
      <div class="container">
        <div class="row">
          <div class="col-md-7">
            <h2></h2><a href="#" class="hero-link">View More</a>
          </div>
        </div>
      </div>
</section>

<?php 
else :

  echo nothing_found();

endif;
?>

<!-- Latest Post -->
<section class="latest-posts"> 
      <div class="container">
        <header> 
          <h2>Latest from the blog</h2>
          <p class="text-big"></p>
        </header>
        <div class="row">
          
<?php 

$latest_posts = latest_posts(0, 3);

if ( is_array($latest_posts) ) :

    foreach ( $latest_posts as $latest_post ) :

      $latest_img = isset($latest_post['media_filename']) ? safe_html($latest_post['media_filename']) : "";
      $latest_title = isset($latest_post['post_title']) ? safe_html($latest_post['post_title']) : "";

?>
          <div class="post col-md-4">
            <div class="post-thumbnail">
              <a href="">
                <img src="<?= isset($latest_img) ? $latest_img : "https:via.placeholder.com/640x450"?>" alt="<?=isset($latest_title) ? $latest_title : ""; ?>" class="img-fluid">
              </a>
            </div>
            <div class="post-details">
              <div class="post-meta d-flex justify-content-between">
                <div class="date">  </div>
                <div class="category"><a href="#">Business</a></div>
              </div><a href="post.html">
                <h3 class="h4">Ways to remember your important ideas</h3></a>
              <p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.</p>
            </div>
          </div>
          
<?php 
 endforeach;
endif;
?>         

        </div>
      </div>
</section>
    
<!-- Newsletter Section -->
<section class="newsletter no-padding-top">    
      <div class="container">
        <!-- 
        <div class="row">
          <div class="col-md-6">
            <h2>Subscribe to Newsletter</h2>
            <p class="text-big">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
          </div>
          <div class="col-md-8">
            <div class="form-holder">
              <form action="#">
                <div class="form-group">
                  <input type="email" name="email" id="email" placeholder="Type your email address">
                  <button type="submit" class="submit">Subscribe</button>
                </div>
              </form>
            </div>
          </div>
        </div> -->
      </div>
</section>

<!-- Gallery Section -->
<section class="gallery no-padding">    
      <div class="row">
        <div class="mix col-lg-3 col-md-3 col-sm-6">
          <div class="item"><a href="img/gallery-1.jpg" data-fancybox="gallery" class="image"><img src="img/gallery-1.jpg" alt="..." class="img-fluid">
              <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div></a></div>
        </div>
        <div class="mix col-lg-3 col-md-3 col-sm-6">
          <div class="item"><a href="img/gallery-2.jpg" data-fancybox="gallery" class="image"><img src="img/gallery-2.jpg" alt="..." class="img-fluid">
              <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div></a></div>
        </div>
        <div class="mix col-lg-3 col-md-3 col-sm-6">
          <div class="item"><a href="img/gallery-3.jpg" data-fancybox="gallery" class="image"><img src="img/gallery-3.jpg" alt="..." class="img-fluid">
              <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div></a></div>
        </div>
        <div class="mix col-lg-3 col-md-3 col-sm-6">
          <div class="item"><a href="img/gallery-4.jpg" data-fancybox="gallery" class="image"><img src="img/gallery-4.jpg" alt="..." class="img-fluid">
              <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div></a></div>
        </div>
      </div>
</section>