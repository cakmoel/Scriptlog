<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * WordPress WXR Importer Utility
 * 
 * Parses WordPress eXtended RSS (WXR) export files
 * 
 * @category  Utility
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class WordPressImporter
{
  private $xml;
  private $wxrVersion;
  private $siteUrl;
  private $title;
  
  public function __construct()
  {
    $this->wxrVersion = '';
    $this->siteUrl = '';
    $this->title = '';
  }
  
  /**
   * Parse WXR file from string
   * 
   * @param string $content
   * @return bool
   * @throws ImportException
   */
  public function parse($content)
  {
    $content = $this->cleanXmlContent($content);
    
    libxml_use_internal_errors(true);
    $this->xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA);
    
    if ($this->xml === false) {
      $errors = libxml_get_errors();
      libxml_clear_errors();
      $errorMsg = !empty($errors) ? $errors[0]->message : 'Invalid XML format';
      throw new ImportException('Failed to parse WXR file: ' . $errorMsg);
    }
    
    $this->wxrVersion = (string) $this->xml->xpath('//wp:wxr_version')[0] ?? '1.0';
    $this->siteUrl = (string) $this->xml->xpath('//channel/link')[0] ?? '';
    $this->title = (string) $this->xml->xpath('//channel/title')[0] ?? '';
    
    return true;
  }
  
  /**
   * Get site information
   * 
   * @return array
   */
  public function getSiteInfo()
  {
    return [
      'wxr_version' => $this->wxrVersion,
      'site_url' => $this->siteUrl,
      'title' => $this->title
    ];
  }
  
  /**
   * Extract all categories from WXR
   * 
   * @return array
   */
  public function getCategories()
  {
    $categories = [];
    
    $xmlCategories = $this->xml->xpath('//wp:category');
    
    if (empty($xmlCategories)) {
      return $categories;
    }
    
    foreach ($xmlCategories as $cat) {
      $catName = (string) $cat->xpath('.//wp:cat_name')[0] ?? '';
      $catSlug = (string) $cat->xpath('.//wp:category_nicename')[0] ?? '';
      $catParent = (string) $cat->xpath('.//wp:category_parent')[0] ?? '';
      
      if (!empty($catName)) {
        $categories[] = [
          'name' => $catName,
          'slug' => !empty($catSlug) ? $catSlug : make_slug($catName),
          'parent' => $catParent
        ];
      }
    }
    
    return $categories;
  }
  
  /**
   * Extract all tags from WXR
   * 
   * @return array
   */
  public function getTags()
  {
    $tags = [];
    
    $xmlTags = $this->xml->xpath('//wp:tag');
    
    if (empty($xmlTags)) {
      return $tags;
    }
    
    foreach ($xmlTags as $tag) {
      $tagName = (string) $tag->xpath('.//wp:tag_name')[0] ?? '';
      $tagSlug = (string) $tag->xpath('.//wp:tag_slug')[0] ?? '';
      
      if (!empty($tagName)) {
        $tags[] = [
          'name' => $tagName,
          'slug' => !empty($tagSlug) ? $tagSlug : make_slug($tagName)
        ];
      }
    }
    
    return $tags;
  }
  
  /**
   * Extract all posts/pages from WXR
   * 
   * @return array
   */
  public function getPosts()
  {
    $posts = [];
    
    $xmlItems = $this->xml->channel->item;
    
    if (empty($xmlItems)) {
      return $posts;
    }
    
    foreach ($xmlItems as $item) {
      $children = $item->children();
      $namespaces = $item->getNamespaces(true);
      
      $postType = (string) $children->xpath('.//wp:post_type')[0] ?? 'post';
      
      if (!in_array($postType, ['post', 'page'])) {
        continue;
      }
      
      $title = (string) $item->title ?? '';
      $link = (string) $item->link ?? '';
      $pubDate = (string) $item->pubDate ?? date('Y-m-d H:i:s');
      $creator = (string) $children->xpath('.//dc:creator')[0] ?? '';
      $content = (string) $children->xpath('.//content:encoded')[0] ?? '';
      $excerpt = (string) $children->xpath('.//excerpt:encoded')[0] ?? '';
      $postId = (string) $children->xpath('.//wp:post_id')[0] ?? '';
      $postName = (string) $children->xpath('.//wp:post_name')[0] ?? '';
      $status = (string) $children->xpath('.//wp:status')[0] ?? 'publish';
      $parent = (string) $children->xpath('.//wp:post_parent')[0] ?? '0';
      $menuOrder = (string) $children->xpath('.//wp:menu_order')[0] ?? '0';
      $commentStatus = (string) $children->xpath('.//wp:comment_status')[0] ?? 'open';
      
      $categories = [];
      foreach ($item->category as $cat) {
        $catDomain = (string) $cat['domain'] ?? '';
        $catNicename = (string) $cat['nicename'] ?? '';
        $catName = (string) $cat ?? '';
        
        if ($catDomain === 'category' && !empty($catNicename)) {
          $categories[] = [
            'slug' => $catNicename,
            'name' => $catName
          ];
        }
      }
      
      $tags = [];
      foreach ($item->category as $cat) {
        $catDomain = (string) $cat['domain'] ?? '';
        $catNicename = (string) $cat['nicename'] ?? '';
        
        if ($catDomain === 'post_tag' && !empty($catNicename)) {
          $tags[] = $catNicename;
        }
      }
      
      $postCategories = [];
      $postTags = [];
      
      foreach ($item->category as $cat) {
        $catDomain = (string) $cat['domain'] ?? '';
        $catNicename = (string) $cat['nicename'] ?? '';
        $catName = (string) $cat ?? '';
        
        if ($catDomain === 'category' && !empty($catNicename)) {
          $postCategories[] = [
            'slug' => $catNicename,
            'name' => $catName
          ];
        } elseif ($catDomain === 'post_tag' && !empty($catNicename)) {
          $postTags[] = $catNicename;
        }
      }
      
      $comments = $this->getCommentsFromItem($item, $namespaces);
      
      $posts[] = [
        'id' => $postId,
        'title' => $title,
        'slug' => !empty($postName) ? $postName : make_slug($title),
        'content' => $content,
        'excerpt' => $excerpt,
        'type' => $postType,
        'status' => $this->mapPostStatus($status),
        'date' => $this->formatDate($pubDate),
        'author' => $creator,
        'link' => $link,
        'parent' => $parent,
        'menu_order' => $menuOrder,
        'comment_status' => $commentStatus === 'open' ? 'open' : 'closed',
        'categories' => $postCategories,
        'tags' => $postTags,
        'comments' => $comments
      ];
    }
    
    return $posts;
  }
  
  /**
   * Extract comments from post item
   * 
   * @param SimpleXMLElement $item
   * @param array $namespaces
   * @return array
   */
  private function getCommentsFromItem($item, $namespaces)
  {
    $comments = [];
    
    $wp = $item->children('wp', true);
    
    if (!isset($wp->comment)) {
      return $comments;
    }
    
    foreach ($wp->comment as $comment) {
      $commentData = [
        'author_name' => (string) $comment->comment_author ?? '',
        'author_email' => (string) $comment->comment_author_email ?? '',
        'author_url' => (string) $comment->comment_author_url ?? '',
        'author_ip' => (string) $comment->comment_author_ip ?? '',
        'date' => $this->formatDate((string) $comment->comment_date ?? ''),
        'content' => (string) $comment->comment_content ?? '',
        'status' => $this->mapCommentStatus((string) $comment->comment_approved ?? '1'),
        'parent' => (string) $comment->comment_parent ?? '0'
      ];
      
      if (!empty($commentData['content'])) {
        $comments[] = $commentData;
      }
    }
    
    return $comments;
  }
  
  /**
   * Map WordPress post status to Blogware status
   * 
   * @param string $status
   * @return string
   */
  private function mapPostStatus($status)
  {
    $statusMap = [
      'publish' => 'publish',
      'future' => 'publish',
      'draft' => 'draft',
      'pending' => 'pending',
      'private' => 'private',
      'trash' => 'draft'
    ];
    
    return $statusMap[$status] ?? 'draft';
  }
  
  /**
   * Map WordPress comment status to Blogware status
   * 
   * @param string $status
   * @return string
   */
  private function mapCommentStatus($status)
  {
    $statusMap = [
      '1' => 'approved',
      '0' => 'pending',
      'spam' => 'spam',
      'trash' => 'spam'
    ];
    
    return $statusMap[$status] ?? 'pending';
  }
  
  /**
   * Format date string
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
  
  /**
   * Clean XML content
   * 
   * @param string $content
   * @return string
   */
  private function cleanXmlContent($content)
  {
    $content = preg_replace('/<\?xml[^>]*encoding="[^"]*"[^>]*\?>/i', '', $content);
    $content = preg_replace('/<\?xml[^>]*version="[^"]*"[^>]*\?>/i', '', $content);
    
    if (stripos($content, '<?xml') === 0) {
      $firstTagEnd = strpos($content, '?>');
      if ($firstTagEnd !== false) {
        $content = substr($content, $firstTagEnd + 2);
      }
    }
    
    return $content;
  }
}
