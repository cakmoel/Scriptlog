<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * class ArchivesProviderModel extends Dao
 * 
 * @category Provider class
 * @author M.Noermoehammad
 * @license MIT
 * @version 1.0
 * 
 */
class ArchivesProviderModel extends Dao
{

 private $linkArchives;

 private $pagination = null;

 public function __construct()
 {
    parent::__construct();
 }

 /**
 * getArchivesByMonthYear
 * 
 * retrieves archives of posts by month and year
 * and list it on sidebar
 * 
 * @return mixed
 * 
 */
public function getArchivesByMonthYear()
{

$sql = "SELECT MONTH(p.post_date) AS month, YEAR(p.post_date) AS year, COUNT(p.ID) AS total
        FROM tbl_posts p GROUP BY month, year 
        ORDER BY month DESC";

$this->setSQL($sql);

$archives = $this->findAll();

return (empty($archives)) ?: $archives;

}

/**
 * getArchivesPublished
 * 
 * @param object $perPage
 * @param object $sanitize
 * @param array $values
 * 
 */
public function getArchivesPublished(Paginator $perPage, Sanitize $sanitize, array $values)
{
 
 // collect month and year
 $month = ( isset($values['month']) && $values['month'] == $_GET['month'] ? Sanitize::mildSanitizer($values['month']) : null );
 $year = ( isset($values['year']) && $values['year'] == $_GET['year'] ? Sanitize::mildSanitizer($values['year']) : null );
 
 // set from and to dates
 $from = date('Y-m-01 00:00:00', strtotime("$year-$month"));
 $to = date('Y-m-31 23:59:59', strtotime("$year-$month"));

 $this->linkArchives = $perPage;
 
 $stmt = $this->dbc->dbQuery("SELECT ID FROM tbl_posts WHERE post_date >= $from post_date <= $to ");

 $this->linkArchives->set_total($stmt->rowCount());

 $sql = "SELECT p.ID, p.media_id, p.post_author, p.post_date, p.post_modified, p.post_title, p.post_slug, 
        p.post_content, p.post_summary, p.post_keyword, p.post_tags, p.post_type, p.post_status, 
        p.post_sticky, u.user_login, u.user_fullname,
        m.media_filename, m.media_caption
      FROM tbl_posts AS p
      INNER JOIN tbl_users AS u ON p.post_author = u.ID
      INNER JOIN tbl_media AS m ON p.media_id = m.ID
      WHERE p.post_date >= $from AND p.post_date <= $to 
      AND p.post_type = 'blog' AND p.post_status = 'publish'
      ORDER BY p.post_date DESC " . $this->linkArchives->get_limit($sanitize);

$this->setSQL($sql);

$archivesPublished = $this->findAll();

$this->pagination = $this->linkArchives->page_links($sanitize);

return ( empty($archivesPublished) ) ?: ['archivesPublished' => $archivesPublished, 'paginationLink' => $this->pagination];

}

}