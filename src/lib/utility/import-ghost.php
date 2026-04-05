<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Ghost JSON Importer Utility
 *
 * Parses Ghost export JSON files
 *
 * @category  Utility
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class GhostImporter
{
    private $data;
    private $schema;

    public function __construct()
    {
        $this->data = null;
        $this->schema = [];
    }

    /**
     * Parse JSON file from string
     *
     * @param string $content
     * @return bool
     * @throws ImportException
     */
    public function parse($content)
    {
        $this->data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ImportException('Failed to parse JSON: ' . json_last_error_msg());
        }

        if (!is_array($this->data)) {
            throw new ImportException('Invalid JSON structure: expected object or array');
        }

        $this->detectSchema();

        return true;
    }

    /**
     * Detect Ghost schema version
     *
     * @return void
     */
    private function detectSchema()
    {
        if (isset($this->data['posts'])) {
            $this->schema = 'v3';
        } elseif (isset($this->data['db'])) {
            $dbData = $this->data['db'];
            if (isset($dbData['posts'])) {
                $this->schema = 'v2';
                $this->data = $dbData;
            } else {
                $this->schema = 'v1';
            }
        } else {
            $this->schema = 'unknown';
        }
    }

    /**
     * Get detected schema version
     *
     * @return string
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Get site information
     *
     * @return array
     */
    public function getSiteInfo()
    {
        $title = '';
        $url = '';

        if ($this->schema === 'v3') {
            $title = $this->data['title'] ?? '';
            $url = $this->data['url'] ?? '';
        } elseif (isset($this->data['meta'])) {
            $title = $this->data['meta']['title'] ?? '';
            $url = $this->data['meta']['url'] ?? '';
        }

        return [
          'schema' => $this->schema,
          'title' => $title,
          'url' => $url
        ];
    }

    /**
     * Extract all posts/pages from Ghost export
     *
     * @return array
     */
    public function getPosts()
    {
        $posts = [];

        $postsData = [];

        if ($this->schema === 'v3') {
            $postsData = $this->data['posts'] ?? [];
        } elseif ($this->schema === 'v2') {
            $postsData = $this->data['posts'] ?? [];
        } else {
            $postsData = $this->data['posts'] ?? [];
        }

        if (empty($postsData)) {
            return $posts;
        }

        foreach ($postsData as $post) {
            $title = $post['title'] ?? '';
            $slug = $post['slug'] ?? make_slug($title);
            $htmlContent = $post['html'] ?? '';
            $plainContent = $post['plaintext'] ?? '';
            $content = !empty($htmlContent) ? $htmlContent : nl2br($plainContent);

            $status = isset($post['status']) ? $this->mapPostStatus($post['status']) : 'publish';

            $publishedAt = isset($post['published_at']) ? $this->formatDate($post['published_at']) : date('Y-m-d H:i:s');
            $createdAt = isset($post['created_at']) ? $this->formatDate($post['created_at']) : $publishedAt;
            $updatedAt = isset($post['updated_at']) ? $this->formatDate($post['updated_at']) : null;

            $featureImage = $post['feature_image'] ?? '';

            $tags = [];
            $categories = [];

            if (isset($post['tags'])) {
                foreach ($post['tags'] as $tag) {
                    if (is_array($tag)) {
                        $tagName = $tag['name'] ?? '';
                        $tagSlug = $tag['slug'] ?? make_slug($tagName);
                        $tagVisibility = $tag['visibility'] ?? 'public';
                    } else {
                        $tagName = $tag;
                        $tagSlug = make_slug($tag);
                        $tagVisibility = 'public';
                    }

                    if (!empty($tagName)) {
                        if ($tagVisibility === 'internal') {
                            $categories[] = [
                              'name' => $tagName,
                              'slug' => $tagSlug
                            ];
                        } else {
                            $tags[] = $tagSlug;
                        }
                    }
                }
            }

            if (isset($post['primary_tag'])) {
                $primaryTag = is_array($post['primary_tag']) ? $post['primary_tag']['name'] : $post['primary_tag'];
                if (!empty($primaryTag)) {
                    $categories[] = [
                      'name' => $primaryTag,
                      'slug' => make_slug($primaryTag)
                    ];
                }
            }

            if (isset($post['primary_category'])) {
                $primaryCat = is_array($post['primary_category']) ? $post['primary_category']['name'] : $post['primary_category'];
                if (!empty($primaryCat)) {
                    $categories[] = [
                      'name' => $primaryCat,
                      'slug' => make_slug($primaryCat)
                    ];
                }
            }

            $commentStatus = ($post['comment_id'] ?? '') !== false ? 'open' : 'closed';

            $excerpt = $post['custom_excerpt'] ?? $post['excerpt'] ?? '';
            if (empty($excerpt) && !empty($plainContent)) {
                $excerpt = substr(strip_tags($plainContent), 0, 200);
            }

            $author = '';
            if (isset($post['author'])) {
                $author = is_array($post['author']) ? ($post['author']['name'] ?? '') : $post['author'];
            }

            $comments = $this->extractComments($post);

            $posts[] = [
              'id' => $post['id'] ?? '',
              'title' => $title,
              'slug' => $slug,
              'content' => $content,
              'excerpt' => $excerpt,
              'type' => ($post['type'] ?? 'post') === 'page' ? 'page' : 'blog',
              'status' => $status,
              'date' => $publishedAt,
              'created_at' => $createdAt,
              'modified' => $updatedAt,
              'author' => $author,
              'feature_image' => $featureImage,
              'comment_status' => $commentStatus,
              'categories' => $categories,
              'tags' => $tags,
              'url' => $post['url'] ?? '',
              'comments' => $comments
            ];
        }

        return $posts;
    }

    /**
     * Extract comments from post
     *
     * @param array $post
     * @return array
     */
    private function extractComments($post)
    {
        $comments = [];

        if (isset($post['comments']) && is_array($post['comments'])) {
            foreach ($post['comments'] as $comment) {
                $commentData = [
                  'author_name' => $comment['author']['name'] ?? $comment['author_name'] ?? 'Anonymous',
                  'author_email' => $comment['author']['email'] ?? $comment['author_email'] ?? '',
                  'author_url' => $comment['author']['url'] ?? $comment['author_url'] ?? '',
                  'author_ip' => $comment['ip'] ?? '127.0.0.1',
                  'date' => $this->formatDate($comment['created_at'] ?? date('Y-m-d H:i:s')),
                  'content' => $comment['html'] ?? $comment['content'] ?? '',
                  'status' => $this->mapCommentStatus($comment['status'] ?? 'published'),
                  'parent' => $comment['parent_id'] ?? '0'
                ];

                if (!empty($commentData['content'])) {
                    $comments[] = $commentData;
                }
            }
        }

        return $comments;
    }

    /**
     * Extract tags from Ghost export
     *
     * @return array
     */
    public function getTags()
    {
        $tags = [];

        $tagsData = [];

        if ($this->schema === 'v3') {
            $tagsData = $this->data['tags'] ?? [];
        } elseif ($this->schema === 'v2') {
            $tagsData = $this->data['db']['tags'] ?? [];
        }

        if (empty($tagsData)) {
            return $tags;
        }

        foreach ($tagsData as $tag) {
            $tags[] = [
              'name' => $tag['name'] ?? '',
              'slug' => $tag['slug'] ?? make_slug($tag['name'] ?? ''),
              'description' => $tag['description'] ?? '',
              'visibility' => $tag['visibility'] ?? 'public'
            ];
        }

        return $tags;
    }

    /**
     * Extract categories from Ghost export
     *
     * @return array
     */
    public function getCategories()
    {
        $categories = [];

        $tagsData = [];

        if ($this->schema === 'v3') {
            $tagsData = $this->data['tags'] ?? [];
        } elseif ($this->schema === 'v2') {
            $tagsData = $this->data['db']['tags'] ?? [];
        }

        if (empty($tagsData)) {
            return $categories;
        }

        foreach ($tagsData as $tag) {
            $visibility = $tag['visibility'] ?? 'public';

            if ($visibility === 'internal') {
                $categories[] = [
                  'name' => $tag['name'] ?? '',
                  'slug' => $tag['slug'] ?? make_slug($tag['name'] ?? ''),
                  'description' => $tag['description'] ?? ''
                ];
            }
        }

        return $categories;
    }

    /**
     * Map Ghost post status to Blogware status
     *
     * @param string $status
     * @return string
     */
    private function mapPostStatus($status)
    {
        $statusMap = [
          'published' => 'publish',
          'draft' => 'draft',
          'scheduled' => 'pending',
          'private' => 'private'
        ];

        return $statusMap[$status] ?? 'draft';
    }

    /**
     * Map Ghost comment status to Blogware status
     *
     * @param string $status
     * @return string
     */
    private function mapCommentStatus($status)
    {
        $statusMap = [
          'published' => 'approved',
          'draft' => 'pending',
          'spam' => 'spam'
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
}
