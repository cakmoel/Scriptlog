<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Scriptlog Importer Utility
 *
 * Parses Scriptlog native export JSON files
 *
 * @category  Utility
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class ScriptlogImporter
{
    private $data;
    private $version;
    private $siteUrl;
    private $siteTitle;

    public function __construct()
    {
        $this->version = '';
        $this->siteUrl = '';
        $this->siteTitle = '';
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

        if (!isset($this->data['_meta'])) {
            throw new ImportException('Invalid Scriptlog export file: missing metadata');
        }

        if (!isset($this->data['_meta']['format']) || $this->data['_meta']['format'] !== 'scriptlog-native') {
            throw new ImportException('Invalid Scriptlog export file: not a native export');
        }

        $this->version = $this->data['_meta']['version'] ?? '';
        $this->siteUrl = $this->data['_meta']['exported_from'] ?? '';

        if (isset($this->data['site'])) {
            $this->siteTitle = $this->data['site']['title'] ?? '';
        }

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
            'version' => $this->version,
            'site_url' => $this->siteUrl,
            'title' => $this->siteTitle,
            'format' => 'scriptlog-native'
        ];
    }

    /**
     * Get all users
     *
     * @return array
     */
    public function getUsers()
    {
        return $this->data['users'] ?? [];
    }

    /**
     * Get all topics/categories
     *
     * @return array
     */
    public function getTopics()
    {
        return $this->data['topics'] ?? [];
    }

    /**
     * Get all posts
     *
     * @return array
     */
    public function getPosts()
    {
        return $this->data['posts'] ?? [];
    }

    /**
     * Get all pages
     *
     * @return array
     */
    public function getPages()
    {
        return $this->data['pages'] ?? [];
    }

    /**
     * Get all comments
     *
     * @return array
     */
    public function getComments()
    {
        return $this->data['comments'] ?? [];
    }

    /**
     * Get all menus
     *
     * @return array
     */
    public function getMenus()
    {
        return $this->data['menus'] ?? [];
    }

    /**
     * Get all settings
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->data['settings'] ?? [];
    }

    /**
     * Get post-topic relationships
     *
     * @return array
     */
    public function getPostTopics()
    {
        return $this->data['_post_topic'] ?? [];
    }

    /**
     * Get tags (extracted from posts)
     *
     * @return array
     */
    public function getTags()
    {
        $tags = [];
        $posts = $this->getPosts();
        $pages = $this->getPages();

        foreach (array_merge($posts, $pages) as $content) {
            if (!empty($content['post_tags'])) {
                $tagList = explode(',', $content['post_tags']);
                foreach ($tagList as $tag) {
                    $tag = trim($tag);
                    if (!empty($tag)) {
                        $slug = make_slug($tag);
                        $tags[$slug] = [
                            'name' => $tag,
                            'slug' => $slug
                        ];
                    }
                }
            }
        }

        return array_values($tags);
    }
}
