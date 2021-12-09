<?php
/**
 * initialize_uri_request() 
 *
 * @category themes function
 * @return object
 * 
 */
function initialize_request()
{
return new RequestPath();
}

/**
 * initialize_sanitizer
 *
 * @return object
 * 
 */
function initialize_sanitizer()
{
 return new Sanitize();
}

/**
 * initialize_post()
 *
 * @return object
 */
function initialize_post()
{
 $frontPostContent = new PostProviderModel();
 return $frontPostContent;
}

/**
 * initialize_page()
 *
 * @return object
 */
function initialize_page()
{
 $frontPageContent = new PageProviderModel();
 return $frontPageContent;
}

/**
 * initialize_topic()
 *
 * @return object
 */
function initialize_topic()
{
 $frontTopicContent = new TopicProviderModel();
 return $frontTopicContent;
}

/**
 * featured_post
 * 
 * retrieving random headlines
 *
 * @category themes function
 * @return array
 * 
 */
function featured_post()
{

   if ( trim($_SERVER['REQUEST_URI'], DIRECTORY_SEPARATOR) === initialize_request()->matched ) {

      $headlines = FrontContentProvider::frontRandomHeadlines(initialize_post());

      return $headlines;

   }

}

/**
 * sticky_page
 * 
 * @category theme function
 * @return mixed
 *
 */
function sticky_page()
{

 if ( trim($_SERVER['REQUEST_URI'], DIRECTORY_SEPARATOR) === initialize_request()->matched ) {

   $sticky_page = FrontContentProvider::frontRandomStickyPage(initialize_page());

   return $sticky_page;

 }

}

/**
 * random_posts
 *
 * @category theme function
 * @param int|num $limit
 * @return mixed
 * 
 */
function random_posts($limit)
{

 if ( trim($_SERVER['REQUEST_URI'], DIRECTORY_SEPARATOR) === initialize_request()->matched ) {

   $random_posts = FrontContentProvider::frontRandomPosts($limit, initialize_post());

   return $random_posts;

 }

}

/**
 * latest_posts
 *
 * @param int|numeric $position
 * @param int|numeric $limit
 * @return mixed
 * 
 */
function latest_posts($position, $limit)
{
  if ( trim($_SERVER['REQUEST_URI'], DIRECTORY_SEPARATOR) === initialize_request()->matched ) {

    $latest_posts = FrontContentProvider::frontLatestPosts($position, $limit, initialize_post());

    return $latest_posts;

  }
}

/**
 * retrieve_post_topic
 * 
 * @param int $postId
 * @return 
 * 
 */
function retrieve_post_topic($postId)
{
 $post_topic = FrontContentProvider::frontPostTopic($postId, initialize_topic());
 return $post_topic;
}

/**
 * nothing_found
 *
 * @return string
 * 
 */
function nothing_found()
{

$site_url = app_info()['app_url'];

$nothing_found = <<<_NOTHING_FOUND

<div class="alert alert-warning" role="alert">
  <h4 class="alert-heading">Whoops !</h4>
  <p>You have not any post yet!</p>
  <hr>
  <p class="mb-0">Please go to <a href="$site_url/admin/login.php">administrator panel</a> to populate your blog.</p>
</div>

_NOTHING_FOUND;

return $nothing_found;

}