<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
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
        
        if (PHP_VERSION_ID < 80100 && function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader(true);
        }

        $this->xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_DTDLOAD);

        if ($this->xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : 'Invalid XML format';
            throw new ImportException('Failed to parse WXR file: ' . $errorMsg);
        }

        $wxrVersionResults = $this->xml->xpath('//wp:wxr_version');
        $this->wxrVersion = !empty($wxrVersionResults) ? (string) $wxrVersionResults[0] : '1.0';

        $linkResults = $this->xml->xpath('//channel/link');
        $this->siteUrl = !empty($linkResults) ? (string) $linkResults[0] : '';

        $titleResults = $this->xml->xpath('//channel/title');
        $this->title = !empty($titleResults) ? (string) $titleResults[0] : '';

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
            $catNameResults = $cat->xpath('.//wp:cat_name');
            $catName = !empty($catNameResults) ? (string) $catNameResults[0] : '';

            $catSlugResults = $cat->xpath('.//wp:category_nicename');
            $catSlug = !empty($catSlugResults) ? (string) $catSlugResults[0] : '';

            $catParentResults = $cat->xpath('.//wp:category_parent');
            $catParent = !empty($catParentResults) ? (string) $catParentResults[0] : '';

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
            $tagNameResults = $tag->xpath('.//wp:tag_name');
            $tagName = !empty($tagNameResults) ? (string) $tagNameResults[0] : '';

            $tagSlugResults = $tag->xpath('.//wp:tag_slug');
            $tagSlug = !empty($tagSlugResults) ? (string) $tagSlugResults[0] : '';

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

            $postTypeResults = $children->xpath('.//wp:post_type');
            $postType = !empty($postTypeResults) ? (string) $postTypeResults[0] : 'post';

            if (!in_array($postType, ['post', 'page'])) {
                continue;
            }

            $title = (string) $item->title ?? '';
            $link = (string) $item->link ?? '';
            $pubDate = (string) $item->pubDate ?? date('Y-m-d H:i:s');

            $creatorResults = $children->xpath('.//dc:creator');
            $creator = !empty($creatorResults) ? (string) $creatorResults[0] : '';

            $contentResults = $children->xpath('.//content:encoded');
            $content = !empty($contentResults) ? (string) $contentResults[0] : '';

            $excerptResults = $children->xpath('.//excerpt:encoded');
            $excerpt = !empty($excerptResults) ? (string) $excerptResults[0] : '';

            $postIdResults = $children->xpath('.//wp:post_id');
            $postId = !empty($postIdResults) ? (string) $postIdResults[0] : '';

            $postNameResults = $children->xpath('.//wp:post_name');
            $postName = !empty($postNameResults) ? (string) $postNameResults[0] : '';

            $statusResults = $children->xpath('.//wp:status');
            $status = !empty($statusResults) ? (string) $statusResults[0] : 'publish';

            $parentResults = $children->xpath('.//wp:post_parent');
            $parent = !empty($parentResults) ? (string) $parentResults[0] : '0';

            $menuOrderResults = $children->xpath('.//wp:menu_order');
            $menuOrder = !empty($menuOrderResults) ? (string) $menuOrderResults[0] : '0';

            $commentStatusResults = $children->xpath('.//wp:comment_status');
            $commentStatus = !empty($commentStatusResults) ? (string) $commentStatusResults[0] : 'open';

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
