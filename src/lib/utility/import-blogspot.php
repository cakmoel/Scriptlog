<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Blogspot/Blogger XML Importer Utility
 *
 * Parses Blogger/Ghost export XML files
 *
 * @category  Utility
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class BlogspotImporter
{
    private $xml;
    private $feedId;
    private $title;
    private $subtitle;
    private $url;

    public function __construct()
    {
        $this->feedId = '';
        $this->title = '';
        $this->subtitle = '';
        $this->url = '';
    }

    /**
     * Parse XML file from string
     *
     * @param string $content
     * @return bool
     * @throws ImportException
     */
    public function parse($content)
    {
        $content = $this->cleanXmlContent($content);

        libxml_use_internal_errors(true);

        @ini_set('display_errors', '0');
        
        if (PHP_VERSION < 80000) {
            @libxml_disable_entity_loader(true);
        }
        
        $this->xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_DTDLOAD);

        if ($this->xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            $errorMsg = !empty($errors) ? $errors[0]->message : 'Invalid XML format';
            throw new ImportException('Failed to parse Blogger XML: ' . $errorMsg);
        }

        $this->extractFeedInfo();

        return true;
    }

    /**
     * Extract feed information
     *
     * @return void
     */
    private function extractFeedInfo()
    {
        $this->feedId = (string) $this->xml->id ?? '';
        $this->title = (string) ($this->xml->title ?? $this->xml->xpath('//a:title')[0] ?? '');
        $this->subtitle = (string) ($this->xml->subtitle ?? '');
        $this->url = '';

        $links = $this->xml->link;
        if (!empty($links)) {
            foreach ($links as $link) {
                $rel = (string) $link['rel'] ?? '';
                if ($rel === 'alternate') {
                    $this->url = (string) $link['href'] ?? '';
                    break;
                }
            }
        }

        if (empty($this->url)) {
            $entries = $this->xml->entry ?? [];
            if (!empty($entries)) {
                foreach ($entries as $entry) {
                    $links = $entry->link;
                    foreach ($links as $link) {
                        $rel = (string) $link['rel'] ?? '';
                        if ($rel === 'alternate') {
                            $href = (string) $link['href'] ?? '';
                            $parsed = parse_url($href);
                            if (!empty($parsed['host'])) {
                                $this->url = $parsed['scheme'] . '://' . $parsed['host'];
                                break 2;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Get site information
     *
     * @return array
     */
    public function getSiteInfo()
    {
        return [
          'feed_id' => $this->feedId,
          'title' => $this->title,
          'subtitle' => $this->subtitle,
          'url' => $this->url
        ];
    }

    /**
     * Extract all posts from Blogger XML
     *
     * @return array
     */
    public function getPosts()
    {
        $posts = [];

        $namespaces = $this->xml->getNamespaces(true);

        $entries = $this->xml->entry ?? [];

        if (empty($entries)) {
            return $posts;
        }

        foreach ($entries as $entry) {
            $entryNamespaces = $entry->getNamespaces(true);

            $categories = $entry->category ?? [];
            $kind = '';
            $isPost = false;

            foreach ($categories as $cat) {
                $scheme = (string) $cat['scheme'] ?? '';
                $term = (string) $cat['term'] ?? '';

                if ($scheme === 'http://schemas.google.com/g/2005#kind') {
                    $kind = $term;
                    if (strpos($term, 'post') !== false) {
                        $isPost = true;
                    }
                }
            }

            if (!$isPost && strpos($kind, 'post') === false) {
                continue;
            }

            $title = '';
            $titleElement = $entry->title ?? null;
            if ($titleElement !== null) {
                $titleType = (string) $titleElement['type'] ?? 'text';
                if ($titleType === 'html') {
                    $title = (string) $titleElement;
                } else {
                    $title = (string) $titleElement;
                }
            }

            $id = '';
            $idElement = $entry->id ?? null;
            if ($idElement !== null) {
                $idText = (string) $idElement;
                if (preg_match('/post-(\d+)/', $idText, $matches)) {
                    $id = $matches[1];
                } else {
                    $id = $idText;
                }
            }

            $published = '';
            $updated = '';
            $publishedElement = $entry->published ?? null;
            $updatedElement = $entry->updated ?? null;

            if ($publishedElement !== null) {
                $published = (string) $publishedElement;
            }
            if ($updatedElement !== null) {
                $updated = (string) $updatedElement;
            }

            $content = '';
            $contentElement = $entry->content ?? null;
            if ($contentElement !== null) {
                $contentType = (string) $contentElement['type'] ?? 'text';
                if ($contentType === 'html' || $contentType === 'xhtml') {
                    $content = (string) $contentElement;
                } else {
                    $content = nl2br((string) $contentElement);
                }
            }

            $summary = '';
            $summaryElement = $entry->summary ?? null;
            if ($summaryElement !== null) {
                $summary = (string) $summaryElement;
            }

            $link = '';
            $links = $entry->link ?? [];
            foreach ($links as $l) {
                $rel = (string) $l['rel'] ?? '';
                if ($rel === 'alternate') {
                    $link = (string) $l['href'] ?? '';
                    break;
                }
            }

            $author = '';
            $authorElement = $entry->author ?? null;
            if ($authorElement !== null) {
                $author = (string) $authorElement->name ?? '';
            }

            $postCategories = [];
            $postLabels = [];

            foreach ($categories as $cat) {
                $scheme = (string) $cat['scheme'] ?? '';
                $term = (string) $cat['term'] ?? '';
                $label = (string) $cat['label'] ?? '';

                if (
                    $scheme === 'http://schemas.google.com/blogger/2008/kind#category' ||
                    $scheme === 'http://www.blogger.com/atom.ns.tf/category'
                ) {
                    $categoryName = !empty($label) ? $label : $term;
                    if (!empty($categoryName)) {
                        $postCategories[] = [
                          'name' => $categoryName,
                          'slug' => make_slug($categoryName)
                        ];
                    }
                }
            }

            $postTags = $postLabels;

            $commentStatus = 'open';
            $repliesCount = 0;

            $thr = $entry->children('http://purl.org/syndication/thread/1.0');
            $repliesCount = (int) ($thr->total ?? 0);

            $appControl = $entry->children('http://purl.org/atom/app#');
            $control = $appControl->control ?? null;
            if ($control !== null) {
                $draft = $control->draft ?? null;
                if ($draft !== null && (string) $draft === 'yes') {
                    continue;
                }
            }

            $posts[] = [
              'id' => $id,
              'title' => $title,
              'slug' => $this->generateSlug($title, $id),
              'content' => !empty($content) ? $content : $summary,
              'excerpt' => $summary,
              'type' => 'blog',
              'status' => 'publish',
              'date' => $this->formatDate($published),
              'modified' => $this->formatDate($updated),
              'author' => $author,
              'link' => $link,
              'comment_status' => $commentStatus,
              'categories' => $postCategories,
              'tags' => $postTags,
              'replies_count' => $repliesCount
            ];
        }

        return $posts;
    }

    /**
     * Extract all pages from Blogger XML
     *
     * @return array
     */
    public function getPages()
    {
        $pages = [];

        $entries = $this->xml->entry ?? [];

        if (empty($entries)) {
            return $pages;
        }

        foreach ($entries as $entry) {
            $categories = $entry->category ?? [];
            $isPage = false;

            foreach ($categories as $cat) {
                $term = (string) $cat['term'] ?? '';
                if (strpos($term, 'kind#page') !== false || strpos($term, 'page') !== false) {
                    $isPage = true;
                    break;
                }
            }

            if (!$isPage) {
                continue;
            }

            $title = (string) ($entry->title ?? '');
            $content = (string) ($entry->content ?? '');

            $published = (string) ($entry->published ?? date('Y-m-d H:i:s'));
            $updated = (string) ($entry->updated ?? $published);

            $link = '';
            $links = $entry->link ?? [];
            foreach ($links as $l) {
                $rel = (string) $l['rel'] ?? '';
                if ($rel === 'alternate') {
                    $link = (string) $l['href'] ?? '';
                    break;
                }
            }

            $pages[] = [
              'title' => $title,
              'slug' => $this->generateSlug($title),
              'content' => $content,
              'type' => 'page',
              'status' => 'publish',
              'date' => $this->formatDate($published),
              'modified' => $this->formatDate($updated),
              'link' => $link
            ];
        }

        return $pages;
    }

    /**
     * Extract all categories/labels from Blogger XML
     *
     * @return array
     */
    public function getCategories()
    {
        $categories = [];
        $seen = [];

        $entries = $this->xml->entry ?? [];

        if (empty($entries)) {
            return $categories;
        }

        foreach ($entries as $entry) {
            $entryCategories = $entry->category ?? [];

            foreach ($entryCategories as $cat) {
                $scheme = (string) $cat['scheme'] ?? '';
                $term = (string) $cat['term'] ?? '';
                $label = (string) $cat['label'] ?? '';

                if (
                    $scheme === 'http://schemas.google.com/blogger/2008/kind#category' ||
                    $scheme === 'http://www.blogger.com/atom.ns.tf/category'
                ) {
                    $categoryName = !empty($label) ? $label : $term;
                    $categorySlug = make_slug($categoryName);

                    if (!empty($categoryName) && !isset($seen[$categorySlug])) {
                        $seen[$categorySlug] = true;
                        $categories[] = [
                          'name' => $categoryName,
                          'slug' => $categorySlug
                        ];
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * Generate unique slug
     *
     * @param string $title
     * @param string $id
     * @return string
     */
    private function generateSlug($title, $id = '')
    {
        $slug = make_slug($title);

        if (empty($slug)) {
            $slug = 'post-' . ($id ?: time());
        }

        return $slug;
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
