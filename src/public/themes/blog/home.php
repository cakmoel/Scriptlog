<?php
  
  $latest_posts = latest_posts(app_reading_setting()['post_per_page']);
  $galleries = display_galleries(0,4);

    foreach (featured_post() as $hero_headline) {

      $featured_hero_id = isset($hero_headline['ID']) ? (int)$hero_headline['ID'] : "";
      $featured_hero_img = (isset($hero_headline['media_filename']) && $hero_headline['media_filename'] !== "") ? safe_html($hero_headline['media_filename']) : "";
      $featured_hero_title = isset($hero_headline['post_title']) ? safe_html($hero_headline['post_title']) : "";
      
    }

?>

   <!-- Hero Section-->
   <section style="background: url('<?= isset($featured_hero_img) ? invoke_webp_image($featured_hero_img, false) : "https://picsum.photos/1920/1438 "; ?>'); background-size: cover; background-position: center center" class="hero">
     <div class="container">
       <div class="row">
         <div class="col-lg-7">
           <h1 class="h1"><?= isset($featured_hero_title) ? $featured_hero_title : ""; ?></h1>
           <a href="<?= isset($featured_hero_id) ? permalinks($featured_hero_id)['post'] : "#"; ?>" class="hero-link">Discover More</a>
         </div>

       </div><a href="<?= (empty($sticky_page)) ? "#" : ".intro"; ?>" class="continue link-scroll"><i class="fa fa-long-arrow-down"></i> Scroll Down</a>
     </div>
   </section>

 <?php
    foreach (sticky_page() as $sticky) :
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

 <!-- random post/featured post -->
 <section class="featured-posts no-padding-top">
   <div class="container">

     <?php

        $r = 0;

        foreach (random_posts(0,3) as $random_post) :

          $r++;

          $random_post_id = isset($random_post['ID']) ? (int)$random_post['ID'] : "";
          $random_image_id = (isset($random_post['media_id']) && $random_post['media_id'] !== 0) ? (int)$random_post['media_id'] : ""; 
          $random_post_img = (isset($random_post['media_filename']) && $random_post['media_filename'] != "") ? safe_html($random_post['media_filename']) : "";
          $random_post_author = (isset($random_post['user_login']) || isset($random_post['user_fullname']) ? safe_html($random_post['user_login']) : safe_html($random_post['user_fullname']));
          $random_post_title = isset($random_post['post_title']) ? escape_html($random_post['post_title']) : "";
          $random_post_content = isset($random_post['post_content']) ? paragraph_l2br($random_post['post_content']) : "";
          $random_post_created = isset($random_post['post_modified']) || isset($random_post['post_date']) ? safe_html(make_date($random_post['post_modified'])) : safe_html(make_date($random_post['post_date'])); 

          if ($r % 2 == 1) :

      ?>

           <!-- Random Post-->
           <div class="row d-flex align-items-stretch">
             <div class="text col-lg-7">
               <div class="text-inner d-flex align-items-center">
                 <div class="content">

                   <header class="post-header">
                     <div class="category">
                       <?= isset($random_post_id) ? retrieve_post_topic($random_post_id) : ""; ?>
                     </div>
                     <a href="<?= isset($random_post_id) ? permalinks($random_post_id)['post'] : "#"; ?>" title="<?= isset($random_post_title) ? $random_post_title : ""; ?>">
                       <h2 class="h4"><?= isset($random_post_title) ? $random_post_title : ""; ?></h2>
                     </a>
                   </header>

                   <p><?= isset($random_post_content) ? $random_post_content : ""; ?></p>
                   <footer class="post-footer d-flex align-items-center">
                     <a href="#" class="author d-flex align-items-center flex-wrap">
                       <div class="title"><span><i class="fa fa-user-circle"></i> <?= isset($random_post_author) ? $random_post_author : ""; ?> </span></div>
                     </a>
                     <div class="date"><i class="fa fa-calendar"></i>
                       <?= isset($random_post_created) ? $random_post_created : ""; ?>
                     </div>
                   </footer>
                 </div>
               </div>
             </div>
             <div class="image col-lg-5"><img src="<?= isset($random_post_img) ? invoke_webp_image($random_post_img) : "https://via.placeholder.com/516x344"; ?>" alt="<?= isset($random_post['media_caption']) ? safe_html($random_post['media_caption']) : safe_html($random_post['post_title']); ?>"></div>

           </div>

         <?php
          else :
          ?>
           <div class="row d-flex align-items-stretch">
           <div class="image col-lg-5"><img src="<?= isset($random_post_img) ? invoke_webp_image($random_post_img) : "https://via.placeholder.com/516x344"; ?>" alt="<?= isset($random_post['media_caption']) ? safe_html($random_post['media_caption']) : safe_html($random_post['post_title']); ?>"></div>
             <div class="text col-lg-7">
               <div class="text-inner d-flex align-items-center">
                 <div class="content">

                   <header class="post-header">
                     <div class="category">
                       <?= isset($random_post_id) ? retrieve_post_topic($random_post_id) : ""; ?>
                     </div>
                     <a href="<?= isset($random_post_id) ? permalinks($random_post_id)['post'] : "#"; ?>">
                       <h2 class="h4"><?= isset($random_post_title) ? $random_post_title : ""; ?></h2>
                     </a>
                   </header>

                   <p><?= isset($random_post_content) ? $random_post_content : ""; ?></p>
                   <footer class="post-footer d-flex align-items-center">
                     <a href="#" class="author d-flex align-items-center flex-wrap">
                       <div class="title"><span><i class="fa fa-user-circle"></i> <?= $random_post_author; ?> </span></div>
                     </a>
                     <div class="date"><i class="fa fa-calendar"></i>
                       <?= isset($random_post_created) ? $random_post_created : ""; ?>
                     </div>

                   </footer>
                 </div>
               </div>
             </div>
           </div>

     <?php
          endif;
        endforeach;
      ?>

   </div>
   <!--.container-->
 </section>

 <!-- divider section -->

 <?php

    foreach (featured_post() as $divider_content) {

      $featured_divider_id = isset($divider_content['ID']) ? (int)$divider_content['ID'] : "";
      $featured_divider_img = (isset($divider_content['media_filename']) && $divider_content['media_filename'] != "") ? safe_html($divider_content['media_filename']) : "";
      $featured_divider_title = isset($divider_content['post_title']) ? safe_html($divider_content['post_title']) : "";
    }

  ?>

   <section <?php  if (isset($featured_divider_img) ) : ?> style="background: url(<?= invoke_webp_image($featured_divider_img); ?>); background-size: cover; background-position: center bottom"  
    <?php 
       else:
      ?>       
      style="background: url( https://picsum.photos/1920/1280 ); background-size: cover; background-position: center bottom"
      <?php 
        endif; 
      ?> 
    class="divider">
     
     <div class="container">
       <div class="row">
         <div class="col-md-7">
           <h2 class="h2"><?= isset($featured_divider_title) ? $featured_divider_title : ""; ?> </h2><a href="<?= isset($featured_divider_id) ? permalinks($featured_divider_id)['post'] : ""; ?>" class="hero-link">View More</a>
         </div>
       </div>
     </div>

   </section>

 <!-- Latest Post -->
 <section class="latest-posts">
   <div class="container">
     <header>

     <?php
        if ( $latest_posts ) :
      ?>   

      <h2>Latest from the blog</h2>
     </header>
     <div class="row">

       <?php
          foreach ($latest_posts as $latest_post) :

            $latest_post_id = isset($latest_post['ID']) ? (int)$latest_post['ID'] : "";
            $latest_post_title = isset($latest_post['post_title']) ? safe_html($latest_post['post_title']) : "";
            $latest_post_img = (isset($latest_post['media_filename']) && $latest_post['media_filename'] !== "" ) ? safe_html($latest_post['media_filename']) : "";
            $latest_img_caption = isset($latest_post['media_caption']) ? safe_html($latest_post['media_caption']) : "";

        ?>

           <div class="post col-md-4">
             <div class="post-thumbnail"><a href="<?= isset($latest_post_id) ? permalinks($latest_post_id)['post'] : "#"; ?>" title="<?= $latest_post_title; ?>">
             <img src="<?= isset($latest_post_img) ? invoke_webp_image($latest_post_img) : "https://via.placeholder.com/640x450"; ?>" alt="<?= isset($latest_img_caption) ? $latest_img_caption : $latest_post_title; ?>" class="img-fluid"></a></div>
             <div class="post-details">
               <div class="post-meta d-flex justify-content-between">
                 <div class="date"><?= isset($latest_post['post_modified']) ? safe_html(make_date($latest_post['post_modified'])) : safe_html(make_date($latest_post['post_date'])); ?></div>
                 <div class="category"><?= retrieves_topic($latest_post_id); ?></div>
               </div>
               <a href="<?= permalinks($latest_post_id)['post']; ?>" title="<?= $latest_post_title; ?>">
                 <h3 class="h4"><?= $latest_post_title; ?></h3>
               </a>
               <p class="text-muted"><?= isset($latest_post['post_content']) ? paragraph_l2br($latest_post['post_content']) : ""; ?> </p>
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

<div class="row">
  
</div> 

</div>
 </section>

 <!-- Gallery Section -->
 <section class="gallery no-padding">
   <div class="row">

     <?php
      if ( $galleries ) :

        foreach ($galleries as $gallery) :

          $img_filename = isset($gallery['media_filename']) ? safe_html($gallery['media_filename']) : "";
          $img_alt = isset($gallery['media_caption']) ? safe_html($gallery['media_caption']) : "";

      ?>
      
         <div class="mix col-lg-3 col-md-3 col-sm-6">
           <div class="item"><a href="<?= isset($img_filename) ? invoke_webp_image($img_filename) : "https://via.placeholder.com/640x450"; ?>" data-fancybox="gallery" class="image"><img src="<?= isset($img_filename) ? invoke_webp_image($img_filename) : "https://via.placeholder.com/640x450"; ?>" alt="<?= isset($img_alt) ? $img_alt : ""; ?>" class="img-fluid">
               <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div>
             </a></div>
         </div>

       <?php
        endforeach;
      else:
      ?>
      <div class="mix col-lg-3 col-md-3 col-sm-6">
           <div class="item"><a href="https://picsum.photos/640/450" data-fancybox="gallery" class="image">
             <img src="https://picsum.photos/640/450" alt="This is a gallery" class="img-fluid">
               <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div>
             </a></div>
      </div>
      <div class="mix col-lg-3 col-md-3 col-sm-6">
           <div class="item"><a href="https://picsum.photos/640/450" data-fancybox="gallery" class="image">
             <img src="https://picsum.photos/640/450" alt="This is a gallery" class="img-fluid">
               <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div>
             </a></div>
      </div>
      <div class="mix col-lg-3 col-md-3 col-sm-6">
           <div class="item"><a href="https://picsum.photos/640/450" data-fancybox="gallery" class="image">
             <img src="https://picsum.photos/640/450" alt="" class="img-fluid">
               <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div>
             </a></div>
      </div>
      <div class="mix col-lg-3 col-md-3 col-sm-6">
           <div class="item"><a href="https://picsum.photos/640/450" data-fancybox="gallery" class="image">
             <img src="https://picsum.photos/640/450" alt="" class="img-fluid">
               <div class="overlay d-flex align-items-center justify-content-center"><i class="icon-search"></i></div>
             </a></div>
      </div>
      <?php
        endif;
      ?>
   </div>
 </section>