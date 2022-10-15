<?php 
$entries = (function_exists('rewrite_status') && rewrite_status() === 'yes') ?: retrieves_posts_published()['postsPublished']; 

?>
<div class="container">
      <div class="row">
        <!-- Latest Posts -->
        <main class="posts-listing col-lg-8"> 
          <div class="container">
            <div class="row">
              <!-- post -->

              <?php 
                 
                foreach ($entries as $entry) :

                  $post_id = isset($entry['ID']) ? (int)$entry['ID'] : "";
                  $post_title = isset($entry['post_title']) ? htmlout($entry['post_title']) : "";
                  $post_img = (isset($entry['media_filename']) && $entry['media_filename'] !== "") ? htmlout($entry['media_filename']) : "";
                  
              ?>

              <div class="post col-xl-6">
                <div class="post-thumbnail"><a href="post.html"><img src="img/blog-post-1.jpeg" alt="..." class="img-fluid"></a></div>
                <div class="post-details">
                  <div class="post-meta d-flex justify-content-between">
                    <div class="date meta-last">20 May | 2016</div>
                    <div class="category"><a href="#">Business</a></div>
                  </div><a href="post.html">
                    <h3 class="h4">Alberto Savoia Can Teach You About Interior</h3></a>
                  <p class="text-muted">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore.</p>
                  <footer class="post-footer d-flex align-items-center"><a href="#" class="author d-flex align-items-center flex-wrap">
                      <div class="avatar"><img src="img/avatar-3.jpg" alt="..." class="img-fluid"></div>
                      <div class="title"><span>John Doe</span></div></a>
                    <div class="date"><i class="icon-clock" aria-hidden="true"></i> 2 months ago</div>
                    <div class="comments meta-last"><i class="icon-comment" aria-hidden="true"></i>12</div>
                  </footer>
                </div>
              </div>

              <?php 
                endforeach;
              ?>
              
            </div>

            <!-- Pagination -->
            <nav aria-label="Page navigation example">
              <ul class="pagination pagination-template d-flex justify-content-center">
                <li class="page-item"><a href="#" class="page-link"> <i class="fa fa-angle-left"></i></a></li>
                <li class="page-item"><a href="#" class="page-link active">1</a></li>
                <li class="page-item"><a href="#" class="page-link">2</a></li>
                <li class="page-item"><a href="#" class="page-link">3</a></li>
                <li class="page-item"><a href="#" class="page-link"> <i class="fa fa-angle-right"></i></a></li>
              </ul>
            </nav>
          </div>
        </main>
        
        <?php 
          //include __DIR__ . '/sidebar.php';
        ?>
        
      </div>
    </div>