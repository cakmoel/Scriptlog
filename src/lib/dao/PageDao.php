<?php

defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Page class extends Dao
 *
 *
 * @category  Dao Class
 * @author    M.Noermoehammad
 * @license   MIT
 * @version   1.0
 * @since     Since Release 1.0
 *
 */
class PageDao extends Dao
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Find pages
     *
     * @param integer $position
     * @param integer $limit
     * @param string $type
     * @param string $orderBy
     * @return boolean|array|object
     */
    public function findPages($type, $orderBy = 'ID', $author = null)
    {

        if (!is_null($author)) {
            $sql = "SELECT p.ID,
                p.media_id,
                p.post_author,
                p.post_date,
                p.post_modified,
                p.post_title,
                p.post_slug,
                p.post_content,
				p.post_summary,
                p.post_status,
                p.post_sticky,
                p.post_type,
                p.post_locale,
                u.user_login
  			FROM tbl_posts AS p
  			INNER JOIN tbl_users AS u ON p.post_author = u.ID
  			WHERE p.post_author = ?
  			AND p.post_type = ?
  			ORDER BY '$orderBy' DESC";

            $data = array($author, $type);
        } else {
            $sql = "SELECT p.ID,
                p.media_id,
                p.post_author,
                p.post_date,
                p.post_modified,
                p.post_title,
                p.post_slug,
                p.post_content,
				p.post_summary,
                p.post_status,
                p.post_sticky,
                p.post_type,
                p.post_locale,
                u.user_login
  		  FROM tbl_posts AS p
  		  INNER JOIN tbl_users AS u ON p.post_author = u.ID
  		  WHERE p.post_type = ?
  		  ORDER BY '$orderBy' DESC";

            $data = array($type);
        }

        $this->setSQL($sql);

        $pages = $this->findAll($data);

        return (empty($pages)) ?: $pages;
    }

    /**
     * Find page by id
     *
     * @param integer $pageId
     * @param string $post_type
     * @param object $sanitizing
     * @return boolean|array|object
     */
    public function findPageById($pageId, $sanitize)
    {

        $idsanitized = $this->filteringId($sanitize, $pageId, 'sql');

        $sql = "SELECT ID, 
    	           media_id, 
				   post_author,
  	  	           post_date, 
				   post_modified, 
				   post_title,
  	  	           post_slug, 
				   post_content, 
				   post_summary, 
			       post_status, 
				   post_sticky, 
				   post_type,
				   post_locale
  	  	   FROM tbl_posts
  	  	   WHERE ID = ? AND post_type = 'page' ";

        $this->setSQL($sql);

        $pageById = $this->findRow([$idsanitized]);

        return (empty($pageById)) ?: $pageById;
    }

    /**
     * createPage()
     *
     * Insert new page record
     *
     * @param array $bind
     *
     */
    public function createPage($bind)
    {

        if (!empty($bind['media_id'])) {
            $this->create("tbl_posts", [
                'media_id' => $bind['media_id'],
                'post_author' => $bind['post_author'],
                'post_date' => $bind['post_date'],
                'post_title' => $bind['post_title'],
                'post_slug' => $bind['post_slug'],
                'post_content' => $bind['post_content'],
                'post_summary' => $bind['post_summary'],
                'post_status' => $bind['post_status'],
                'post_sticky' => $bind['post_sticky'],
                'post_type' => $bind['post_type'],
                'post_locale' => $bind['post_locale'] ?? 'en',
                'comment_status' => $bind['comment_status']
            ]);
        } else {
            $this->create("tbl_posts", [
                'post_author' => $bind['post_author'],
                'post_date' => $bind['post_date'],
                'post_title' => $bind['post_title'],
                'post_slug' => $bind['post_slug'],
                'post_content' => $bind['post_content'],
                'post_summary' => $bind['post_summary'],
                'post_status' => $bind['post_status'],
                'post_sticky' => $bind['post_sticky'],
                'post_type' => $bind['post_type'],
                'post_locale' => $bind['post_locale'] ?? 'en',
                'comment_status' => $bind['comment_status']
            ]);
        }

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * UpdatePage
     *
     * Updating an existing page record
     *
     * @param array $bind
     * @param integer $id
     *
     */
    public function updatePage($sanitize, $bind, $ID)
    {

        $cleanId = $this->filteringId($sanitize, $ID, 'sql');

        if (!empty($bind['media_id'])) {
            $this->modify("tbl_posts", [
                'media_id' => $bind['media_id'],
                'post_author' => $bind['post_author'],
                'post_modified' => $bind['post_modified'],
                'post_title' => $bind['post_title'],
                'post_slug' => $bind['post_slug'],
                'post_content' => $bind['post_content'],
                'post_summary' => $bind['post_summary'],
                'post_status' => $bind['post_status'],
                'post_sticky' => $bind['post_sticky'],
                'post_type' => $bind['post_type'],
                'post_locale' => $bind['post_locale'] ?? 'en'
                ], ["ID" => (int)$cleanId]);
        } else {
            $this->modify("tbl_posts", [
                'post_author' => $bind['post_author'],
                'post_modified' => $bind['post_modified'],
                'post_title' => $bind['post_title'],
                'post_slug' => $bind['post_slug'],
                'post_content' => $bind['post_content'],
                'post_summary' => $bind['post_summary'],
                'post_status' => $bind['post_status'],
                'post_sticky' => $bind['post_sticky'],
                'post_type' => $bind['post_type'],
                'post_locale' => $bind['post_locale'] ?? 'en'
                ], ["ID" => (int)$cleanId]);
        }

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * deletePage
     *
     * Deleting an existing record based on it's ID
     *
     * @param integer $id
     * @param object $sanitizing
     * @param string $type
     */
    public function deletePage($ID, $sanitize, $type)
    {
        $cleanId = $this->filteringId($sanitize, $ID, 'sql');
        $this->deleteRecord("tbl_posts", ["ID" => (int)$cleanId, "post_type" => $type]);

        if (function_exists('page_cache_clear')) {
            page_cache_clear();
        }
    }

    /**
     * Check page id
     *
     * @param integer $id
     * @param object $sanitizing
     * @return numeric
     */
    public function checkPageId($id, $sanitizing)
    {
        $cleanId = $this->filteringId($sanitizing, $id, 'sql');
        $sql = "SELECT ID FROM tbl_posts WHERE ID = ?";
        $this->setSQL($sql);
        $stmt = $this->checkCountValue([$cleanId]);
        return $stmt > 0;
    }

    /**
     * Set post status
     *
     * @param string $selected
     * @return string
     */
    public function dropDownPostStatus($selected = "")
    {

        $name = 'post_status';
        $posts_status = array('publish' => 'Publish', 'draft' => 'Draft');
        return dropdown($name, $posts_status, $selected);
    }

    /**
     * Set comment status
     *
     * @param string $selected
     * @return string
     */
    public function dropDownCommentStatus($selected = '')
    {

        $name = 'comment_status';
        $comment_status = array('open' => 'Open', 'close' => 'Close');
        return dropdown($name, $comment_status, $selected);
    }

    /**
    * Total page records
    *
    * @param array $data
    * @return numeric
    */
    public function totalPageRecords(array $data = []): ?int
    {
        $sql = "SELECT ID FROM tbl_posts WHERE post_type = 'page'";

        $this->setSQL($sql);

        return $this->checkCountValue($data) ?? 0;
    }

    /**
     * Drop down locale
     *
     * @param string $selected
     * @return string
     *
     */
    public function dropDownLocale($selected = "")
    {
        $name = 'post_locale';

        $locales = [
          'en' => 'English',
          'es' => 'Spanish',
          'fr' => 'French',
          'de' => 'German',
          'it' => 'Italian',
          'pt' => 'Portuguese',
          'ru' => 'Russian',
          'zh' => 'Chinese',
          'ja' => 'Japanese',
          'ko' => 'Korean',
          'ar' => 'Arabic',
          'hi' => 'Hindi',
          'id' => 'Indonesian',
          'ms' => 'Malay',
          'tr' => 'Turkish',
          'nl' => 'Dutch',
          'pl' => 'Polish',
          'vi' => 'Vietnamese',
          'th' => 'Thai',
          'he' => 'Hebrew'
        ];

        return dropdown($name, $locales, $selected);
    }
}
