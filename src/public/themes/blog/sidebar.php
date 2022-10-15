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
                      <div class="views"><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $author; ?></div>
                      <div class="comments"><i class="icon-comment" aria-hidden="true"></i> <?= $total_comment; ?> </div>
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
              foreach (sidebar_topics() as $category) :
            ?>
            
              <div class="item d-flex justify-content-between"><a href="<?= isset($category['ID']) ? permalinks($category['ID'])['cat'] : "#"; ?>"><?= isset($category['topic_title']) ? escape_html($category['topic_title']) : ""; ?></a><span><?= isset($category['total_posts']) ? $category['total_posts'] : ""?></span></div>
            
            <?php 
              endforeach;
            ?>
          </div>
        

          <!-- Widget [Archives Widget]-->
          <div class="widget categories">
            <header>
              <h3 class="h6">Archives</h3>
            </header>
            <?php 
              foreach (retrieve_archives() as $archives) :
                $month_name = isset($archives['month']) ? date("F Y", mktime(0, 0, 0, intval( $archives['month']), 7, intval($archives['year']))) : "";
            ?>
            <div class="item d-flex justify-content-between"><a href="<?= permalinks($archives['month'].$archives['year'])['archive']; ?>" title="<?= $month_name; ?>"><?= $month_name; ?></a><span><?= isset($archives['total']) ? $archives['total'] : ""; ?></span></div>
            <?php 
              endforeach;
            ?>
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