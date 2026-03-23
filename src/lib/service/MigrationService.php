<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * MigrationService
 * 
 * Main service for handling content import from various platforms
 * 
 * @category  Service Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class MigrationService
{
  private $dbc;
  private $sanitizer;
  
  private $authorId;
  private $importStats;
  
  private $categoryMap = [];
  private $postIdMap = [];
  
  const SOURCE_WORDPRESS = 'wordpress';
  const SOURCE_GHOST = 'ghost';
  const SOURCE_BLOGSPOT = 'blogspot';
  
  public function __construct(Sanitize $sanitizer)
  {
    $this->dbc = Registry::get('dbc');
    $this->sanitizer = $sanitizer;
    
    $this->authorId = 1;
    $this->importStats = [
      'posts_created' => 0,
      'posts_updated' => 0,
      'posts_skipped' => 0,
      'pages_created' => 0,
      'categories_created' => 0,
      'comments_created' => 0,
      'comments_skipped' => 0,
      'errors' => []
    ];
    
    $this->categoryMap = [];
    $this->postIdMap = [];
  }
  
  /**
   * Set author ID for imported content
   * 
   * @param int $authorId
   */
  public function setAuthorId($authorId)
  {
    $this->authorId = (int) $authorId;
  }
  
  /**
   * Get import statistics
   * 
   * @return array
   */
  public function getStats()
  {
    return $this->importStats;
  }
  
  /**
   * Reset import statistics
   * 
   * @return void
   */
  public function resetStats()
  {
    $this->importStats = [
      'posts_created' => 0,
      'posts_updated' => 0,
      'posts_skipped' => 0,
      'pages_created' => 0,
      'categories_created' => 0,
      'comments_created' => 0,
      'comments_skipped' => 0,
      'errors' => []
    ];
    
    $this->categoryMap = [];
    $this->postIdMap = [];
  }
  
  /**
   * Import content from WordPress WXR file
   * 
   * @param string $wxrContent
   * @return array
   */
  public function importFromWordPress($wxrContent)
  {
    $this->resetStats();
    
    try {
      $importer = new WordPressImporter();
      $importer->parse($wxrContent);
      
      $categories = $importer->getCategories();
      $this->importCategories($categories);
      
      $posts = $importer->getPosts();
      $this->importPosts($posts);
      
      return [
        'success' => true,
        'source' => self::SOURCE_WORDPRESS,
        'stats' => $this->importStats,
        'site_info' => $importer->getSiteInfo()
      ];
      
    } catch (ImportException $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'source' => self::SOURCE_WORDPRESS
      ];
    }
  }
  
  /**
   * Import content from Ghost JSON file
   * 
   * @param string $jsonContent
   * @return array
   */
  public function importFromGhost($jsonContent)
  {
    $this->resetStats();
    
    try {
      $importer = new GhostImporter();
      $importer->parse($jsonContent);
      
      $categories = $importer->getCategories();
      $this->importCategories($categories);
      
      $posts = $importer->getPosts();
      $this->importPosts($posts);
      
      return [
        'success' => true,
        'source' => self::SOURCE_GHOST,
        'stats' => $this->importStats,
        'site_info' => $importer->getSiteInfo()
      ];
      
    } catch (ImportException $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'source' => self::SOURCE_GHOST
      ];
    }
  }
  
  /**
   * Import content from Blogspot XML file
   * 
   * @param string $xmlContent
   * @return array
   */
  public function importFromBlogspot($xmlContent)
  {
    $this->resetStats();
    
    try {
      $importer = new BlogspotImporter();
      $importer->parse($xmlContent);
      
      $categories = $importer->getCategories();
      $this->importCategories($categories);
      
      $posts = $importer->getPosts();
      $this->importPosts($posts);
      
      $pages = $importer->getPages();
      $this->importPages($pages);
      
      return [
        'success' => true,
        'source' => self::SOURCE_BLOGSPOT,
        'stats' => $this->importStats,
        'site_info' => $importer->getSiteInfo()
      ];
      
    } catch (ImportException $e) {
      return [
        'success' => false,
        'error' => $e->getMessage(),
        'source' => self::SOURCE_BLOGSPOT
      ];
    }
  }
  
  /**
   * Preview import data without importing
   * 
   * @param string $content
   * @param string $source
   * @return array
   */
  public function previewImport($content, $source)
  {
    try {
      switch ($source) {
        case self::SOURCE_WORDPRESS:
          $importer = new WordPressImporter();
          $importer->parse($content);
          return [
            'success' => true,
            'posts_count' => count($importer->getPosts()),
            'categories_count' => count($importer->getCategories()),
            'tags_count' => count($importer->getTags()),
            'site_info' => $importer->getSiteInfo()
          ];
          
        case self::SOURCE_GHOST:
          $importer = new GhostImporter();
          $importer->parse($content);
          return [
            'success' => true,
            'posts_count' => count($importer->getPosts()),
            'categories_count' => count($importer->getCategories()),
            'tags_count' => count($importer->getTags()),
            'site_info' => $importer->getSiteInfo()
          ];
          
        case self::SOURCE_BLOGSPOT:
          $importer = new BlogspotImporter();
          $importer->parse($content);
          return [
            'success' => true,
            'posts_count' => count($importer->getPosts()),
            'pages_count' => count($importer->getPages()),
            'categories_count' => count($importer->getCategories()),
            'site_info' => $importer->getSiteInfo()
          ];
          
        default:
          throw new ImportException('Unknown source: ' . $source);
      }
    } catch (ImportException $e) {
      return [
        'success' => false,
        'error' => $e->getMessage()
      ];
    }
  }
  
  /**
   * Import categories/topics
   * 
   * @param array $categories
   */
  private function importCategories($categories)
  {
    if (empty($categories)) {
      return;
    }
    
    foreach ($categories as $category) {
      $name = $this->sanitizeInput($category['name']);
      $slug = $this->sanitizeInput($category['slug'] ?? make_slug($category['name']));
      
      if (empty($name)) {
        continue;
      }
      
      $existingTopic = $this->findTopicBySlug($slug);
      
      if ($existingTopic) {
        $this->categoryMap[$slug] = $existingTopic['ID'];
      } else {
        $topicId = $this->createTopic($name, $slug);
        
        if ($topicId) {
          $this->categoryMap[$slug] = $topicId;
          $this->importStats['categories_created']++;
        }
      }
    }
  }
  
  /**
   * Import posts
   * 
   * @param array $posts
   */
  private function importPosts($posts)
  {
    if (empty($posts)) {
      return;
    }
    
    foreach ($posts as $post) {
      try {
        $slug = $this->sanitizeInput($post['slug'] ?? make_slug($post['title']));
        $title = $this->sanitizeInput($post['title'] ?? 'Untitled');
        $content = purify_dirty_html($post['content'] ?? '');
        $excerpt = $this->sanitizeInput($post['excerpt'] ?? '');
        
        $existingPost = $this->findPostBySlug($slug);
        
        if ($existingPost) {
          $this->importStats['posts_skipped']++;
          $originalPostId = $existingPost['ID'];
        } else {
          $postData = [
            'post_author' => $this->authorId,
            'post_date' => $this->formatDate($post['date'] ?? date('Y-m-d H:i:s')),
            'post_title' => $title,
            'post_slug' => $this->ensureUniqueSlug($slug),
            'post_content' => $content,
            'post_summary' => $excerpt,
            'post_status' => $this->mapStatus($post['status'] ?? 'publish'),
            'post_visibility' => 'public',
            'post_password' => '',
            'post_tags' => implode(',', $post['tags'] ?? []),
            'post_type' => $post['type'] ?? 'blog',
            'comment_status' => $post['comment_status'] ?? 'open'
          ];
          
          $postId = $this->createPost($postData);
          
          if ($postId) {
            $this->postIdMap[$post['id'] ?? $postId] = $postId;
            
            if (!empty($post['categories'])) {
              $this->assignCategories($postId, $post['categories']);
            }
            
            if ($post['type'] === 'page') {
              $this->importStats['pages_created']++;
            } else {
              $this->importStats['posts_created']++;
            }
            
            $originalPostId = $postId;
          } else {
            $this->importStats['errors'][] = 'Failed to create post: ' . $title;
            continue;
          }
        }
        
        if (!empty($post['comments'])) {
          $this->importComments($post['comments'], $originalPostId);
        }
        
      } catch (\Throwable $e) {
        $this->importStats['errors'][] = 'Error importing post: ' . ($post['title'] ?? 'Unknown') . ' - ' . $e->getMessage();
      }
    }
  }
  
  /**
   * Import pages
   * 
   * @param array $pages
   */
  private function importPages($pages)
  {
    if (empty($pages)) {
      return;
    }
    
    foreach ($pages as $page) {
      try {
        $slug = $this->sanitizeInput($page['slug'] ?? make_slug($page['title']));
        $title = $this->sanitizeInput($page['title'] ?? 'Untitled');
        $content = purify_dirty_html($page['content'] ?? '');
        
        $postData = [
          'post_author' => $this->authorId,
          'post_date' => $this->formatDate($page['date'] ?? date('Y-m-d H:i:s')),
          'post_title' => $title,
          'post_slug' => $this->ensureUniqueSlug($slug),
          'post_content' => $content,
          'post_summary' => '',
          'post_status' => $this->mapStatus($page['status'] ?? 'publish'),
          'post_visibility' => 'public',
          'post_password' => '',
          'post_tags' => '',
          'post_type' => 'page',
          'comment_status' => 'closed'
        ];
        
        $postId = $this->createPost($postData);
        
        if ($postId) {
          $this->importStats['pages_created']++;
        }
        
      } catch (\Throwable $e) {
        $this->importStats['errors'][] = 'Error importing page: ' . ($page['title'] ?? 'Unknown') . ' - ' . $e->getMessage();
      }
    }
  }
  
  /**
   * Import comments
   * 
   * @param array $comments
   * @param int $postId
   */
  private function importComments($comments, $postId)
  {
    if (empty($comments)) {
      return;
    }
    
    foreach ($comments as $comment) {
      try {
        $content = $this->sanitizeInput($comment['content'] ?? '');
        
        if (empty($content)) {
          $this->importStats['comments_skipped']++;
          continue;
        }
        
        $commentData = [
          'comment_post_id' => $postId,
          'comment_parent_id' => !empty($comment['parent']) ? ($this->postIdMap[$comment['parent']] ?? 0) : 0,
          'comment_author_name' => $this->sanitizeInput($comment['author_name'] ?? 'Anonymous'),
          'comment_author_email' => $this->sanitizeInput($comment['author_email'] ?? ''),
          'comment_author_url' => $this->sanitizeInput($comment['author_url'] ?? ''),
          'comment_author_ip' => $this->sanitizeInput($comment['author_ip'] ?? '127.0.0.1'),
          'comment_content' => $content,
          'comment_status' => $this->mapCommentStatus($comment['status'] ?? 'approved'),
          'comment_date' => $this->formatDate($comment['date'] ?? date('Y-m-d H:i:s'))
        ];
        
        $commentId = $this->createComment($commentData);
        
        if ($commentId) {
          $this->importStats['comments_created']++;
        }
        
      } catch (\Throwable $e) {
        $this->importStats['comments_skipped']++;
      }
    }
  }
  
  /**
   * Assign categories to post
   * 
   * @param int $postId
   * @param array $categories
   */
  private function assignCategories($postId, $categories)
  {
    if (empty($categories)) {
      return;
    }
    
    $topicIds = [];
    
    foreach ($categories as $category) {
      $slug = $category['slug'] ?? make_slug($category['name'] ?? '');
      
      if (isset($this->categoryMap[$slug])) {
        $topicIds[] = $this->categoryMap[$slug];
      }
    }
    
    if (!empty($topicIds)) {
      foreach ($topicIds as $topicId) {
        $this->dbc->dbInsert('tbl_post_topic', [
          'post_id' => $postId,
          'topic_id' => $topicId
        ]);
      }
    }
  }
  
  /**
   * Find topic by slug
   * 
   * @param string $slug
   * @return array|null
   */
  private function findTopicBySlug($slug)
  {
    $sql = "SELECT ID, topic_title, topic_slug FROM tbl_topics WHERE topic_slug = ?";
    $stmt = $this->dbc->prepare($sql);
    $stmt->execute([$slug]);
    return $stmt->fetch();
  }
  
  /**
   * Find post by slug
   * 
   * @param string $slug
   * @return array|null
   */
  private function findPostBySlug($slug)
  {
    $sql = "SELECT ID, post_title, post_slug FROM tbl_posts WHERE post_slug = ?";
    $stmt = $this->dbc->prepare($sql);
    $stmt->execute([$slug]);
    return $stmt->fetch();
  }
  
  /**
   * Create topic
   * 
   * @param string $name
   * @param string $slug
   * @return int|false
   */
  private function createTopic($name, $slug)
  {
    $result = $this->dbc->dbInsert('tbl_topics', [
      'topic_title' => $name,
      'topic_slug' => $slug
    ]);
    
    if ($result) {
      return (int) $this->dbc->dbLastInsertId();
    }
    
    return false;
  }
  
  /**
   * Create post
   * 
   * @param array $data
   * @return int|false
   */
  private function createPost($data)
  {
    $result = $this->dbc->dbInsert('tbl_posts', $data);
    
    if ($result) {
      return (int) $this->dbc->dbLastInsertId();
    }
    
    return false;
  }
  
  /**
   * Create comment
   * 
   * @param array $data
   * @return int|false
   */
  private function createComment($data)
  {
    $result = $this->dbc->dbInsert('tbl_comments', $data);
    
    if ($result) {
      return (int) $this->dbc->dbLastInsertId();
    }
    
    return false;
  }
  
  /**
   * Ensure unique slug
   * 
   * @param string $slug
   * @return string
   */
  private function ensureUniqueSlug($slug)
  {
    $originalSlug = $slug;
    $counter = 1;
    
    while ($this->findPostBySlug($slug)) {
      $slug = $originalSlug . '-' . $counter;
      $counter++;
    }
    
    return $slug;
  }
  
  /**
   * Sanitize input
   * 
   * @param string $input
   * @return string
   */
  private function sanitizeInput($input)
  {
    if (empty($input)) {
      return '';
    }
    
    return prevent_injection($input);
  }
  
  /**
   * Map post status
   * 
   * @param string $status
   * @return string
   */
  private function mapStatus($status)
  {
    $statusMap = [
      'publish' => 'publish',
      'published' => 'publish',
      'draft' => 'draft',
      'pending' => 'pending',
      'private' => 'private'
    ];
    
    return $statusMap[$status] ?? 'draft';
  }
  
  /**
   * Map comment status
   * 
   * @param string $status
   * @return string
   */
  private function mapCommentStatus($status)
  {
    $statusMap = [
      'approved' => 'approved',
      '1' => 'approved',
      'published' => 'approved',
      'pending' => 'pending',
      '0' => 'pending',
      'spam' => 'spam'
    ];
    
    return $statusMap[$status] ?? 'pending';
  }
  
  /**
   * Format date
   * 
   * @param string $date
   * @return string
   */
  private function formatDate($date)
  {
    if (empty($date)) {
      return date('Y-m-d H:i:s');
    }
    
    $timestamp = strtotime($date);
    return $timestamp ? date('Y-m-d H:i:s', $timestamp) : date('Y-m-d H:i:s');
  }
}
