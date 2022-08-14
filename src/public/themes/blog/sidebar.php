<aside class="col-lg-4">
          
          <?php 
            $latest_posts = latest_posts(app_reading_setting()['post_per_page'], 'sidebar');
          ?>

          <!-- Widget [Latest Posts Widget] -->
          <div class="widget latest-posts">
            
            <?php 
              if ( $latest_posts ) :      
            ?>
            
            <header>
              <h3 class="h6">Latest Posts</h3>
            </header>

            <div class="blog-posts">
               
              <?php 
                foreach ( (array)$latest_posts['sidebarPosts'] as $latest_post) :
                  $author = (isset($latest_post['user_login'])) ? escape_html($latest_post['user_login']) : escape_html($latest_post['user_fullname']);

              ?>
             <a href="<?= isset($latest_post['ID']) ? permalinks($latest_post['ID'])['post'] : "#"?>">
                <div class="item d-flex align-items-center">
                  <div class="title"><strong><?= isset($latest_post['post_title']) ? escape_html($latest_post['post_title']) : ""; ?></strong>
                    <div class="d-flex align-items-center">
                      <div class="views"><i class="fa fa-user-circle"></i> <?= $author; ?></div>
                      <div class="comments"><i class="fa fa-calendar"></i> <?= $post_created; ?> </div>
                    </div>
                  </div>
                </div>
              </a>
                
              <?php 
                endforeach;
              ?>
            </div>
          <?php 
            endif;
          ?>
          </div>
          <!-- Widget [Categories Widget]-->
          <div class="widget categories">
            <header>
              <h3 class="h6">Categories</h3>
            </header>

            <?php 
              foreach (sidebarTopics() as $category) :
            ?>
            
              <div class="item d-flex justify-content-between"><a href="#"><?= isset($category['topic_title']) ? escape_html($category['topic_title']) : ""; ?></a><span><?= isset($category['total_posts']) ? $category['total_posts'] : ""?></span></div>
            
            <?php 
              endforeach;
            ?>
          </div>
        

          <!-- Widget [Archives Widget]-->
          <div class="widget categories">
            <header>
              <h3 class="h6">Archives</h3>
            </header>
            <div class="item d-flex justify-content-between"><a href="#">Growth</a><span>12</span></div>
            <div class="item d-flex justify-content-between"><a href="#">Local</a><span>25</span></div>
            <div class="item d-flex justify-content-between"><a href="#">Sales</a><span>8</span></div>
            <div class="item d-flex justify-content-between"><a href="#">Tips</a><span>17</span></div>
            <div class="item d-flex justify-content-between"><a href="#">Local</a><span>25</span></div>
          </div>
          <!-- Widget [Tags Cloud Widget]-->

          <div class="widget tags">       
            <header>
              <h3 class="h6">Tags</h3>
            </header>
            <ul class="list-inline">
              <?= outputting_tags(); ?>
            </ul>
          </div>
        </aside>