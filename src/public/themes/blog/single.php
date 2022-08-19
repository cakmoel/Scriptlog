<?php 

$retrieve_post = (rewrite_status() === 'yes') ? retrieve_detail_post(request_path()->param2) : retrieve_detail_post(HandleRequest::isQueryStringRequested()['value']);
$post_img = isset($retrieve_post['media_filename']) ? escape_html($retrieve_post['media_filename']) : "";
$post_id = isset($retrieve_post['ID']) ? (int)$retrieve_post['ID'] : "";
$post_title = isset($retrieve_post['post_title']) ? escape_html($retrieve_post['post_title']) : "";
$post_author = ( isset($retrieve_post['user_login']) ) ? escape_html($retrieve_post['user_login']) : escape_html($retrieve_post['user_fullname']);
$img_alt = isset($retrieve_post['media_caption']) ? escape_html($retrieve_post['media_caption']) : "";
$post_content = isset($retrieve_post['post_content']) ? html_entity_decode(htmLawed($retrieve_post['post_content'])) : "";
$post_created = isset($retrieve_post['post_modified']) ? safe_html(make_date($retrieve_post['post_modified'])) : safe_html(make_date($retrieve_post['post_date']));

?>

<div class="container">

    <div class="row">

        <!-- Latest Posts -->
        <main class="post blog-post col-lg-8"> 
          <div class="container">
            <div class="post-single">
              <div class="post-thumbnail"><img src="<?= isset($post_img) ? invoke_webp_image($post_img) : "https://picsum.photos/730/486"; ?>" alt="<?= isset($img_alt) ? $img_alt : $post_title; ?>" class="img-fluid"></div>
              <div class="post-details">
                <div class="post-meta d-flex justify-content-between">
                  <div class="category">
                    <?= isset($post_id) ? link_topic((int)$post_id) : ""; ?>
                  </div>
                </div>
                <h1><?= $post_title; ?><a href="<?= isset($post_id) ? permalinks($post_id)['post'] : "#"; ?>" title="<?= $post_title; ?>"><i class="fa fa-external-link" aria-hidden="true"></i></a></h1>
                <div class="post-footer d-flex align-items-center flex-column flex-sm-row">
                  <a href="#" class="author d-flex align-items-center flex-wrap">
                    <div class="title"><span><i class="fa fa-user-circle" aria-hidden="true"></i> <?= $post_author; ?> </span></div>
                  </a>
                  <div class="d-flex align-items-center flex-wrap">       
                    <div class="date"><i class="fa fa-calendar" aria-hidden="true"></i> <?= $post_created; ?> </div>
                    <div class="comments meta-last"><i class="icon-comment" aria-hidden="true"></i>12</div>
                  </div>
                </div>
                <div class="post-body">
                   <?= $post_content; ?>
                </div>
                
                <div class="post-tags">
                <?= isset($post_id) ? link_tag($post_id) : ""; ?>
                </div>
                
                <div class="posts-nav d-flex justify-content-between align-items-stretch flex-column flex-md-row">
                  <?= previous_post($post_id); ?>
                  <?= next_post($post_id); ?>
                </div>

                <div class="post-comments">
                  <header>
                    <h3 class="h6">Post Comments<span class="no-of-comments">(3)</span></h3>
                  </header>
                  <div class="comment">
                    <div class="comment-header d-flex justify-content-between">
                      <div class="user d-flex align-items-center">
                        <div class="image"><img src="img/user.svg" alt="..." class="img-fluid rounded-circle"></div>
                        <div class="title"><strong>Jabi Hernandiz</strong><span class="date">May 2016</span></div>
                      </div>
                    </div>
                    <div class="comment-body">
                      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.</p>
                    </div>
                  </div>
                  <div class="comment">
                    <div class="comment-header d-flex justify-content-between">
                      <div class="user d-flex align-items-center">
                        <div class="image"><img src="img/user.svg" alt="..." class="img-fluid rounded-circle"></div>
                        <div class="title"><strong>Nikolas</strong><span class="date">May 2016</span></div>
                      </div>
                    </div>
                    <div class="comment-body">
                      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.</p>
                    </div>
                  </div>
                  <div class="comment">
                    <div class="comment-header d-flex justify-content-between">
                      <div class="user d-flex align-items-center">
                        <div class="image"><img src="img/user.svg" alt="..." class="img-fluid rounded-circle"></div>
                        <div class="title"><strong>John Doe</strong><span class="date">May 2016</span></div>
                      </div>
                    </div>
                    <div class="comment-body">
                      <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam.</p>
                    </div>
                  </div>
                </div>
                <div class="add-comment">
                  <header>
                    <h3 class="h6">Leave a reply</h3>
                  </header>
                  <form action="#" class="commenting-form">
                    <div class="row">
                      <div class="form-group col-md-6">
                        <input type="text" name="username" id="username" placeholder="Name" class="form-control">
                      </div>
                      <div class="form-group col-md-6">
                        <input type="email" name="username" id="useremail" placeholder="Email Address (will not be published)" class="form-control">
                      </div>
                      <div class="form-group col-md-12">
                        <textarea name="usercomment" id="usercomment" placeholder="Type your comment" class="form-control"></textarea>
                      </div>
                      <div class="form-group col-md-12">
                        <button type="submit" class="btn btn-secondary">Submit Comment</button>
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </main>
        
        <?php include __DIR__ . '/sidebar.php'; ?>
         
    </div>

</div>