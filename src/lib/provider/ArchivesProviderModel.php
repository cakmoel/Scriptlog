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

  private $pagination;

  public function __construct()
  {
    parent::__construct();
  }

  /**
   * getArchivesOnSidebar
   * 
   * retrieves archives of posts by month and year
   * and list it on sidebar
   * @return mixed
   * 
   */
  public function getArchivesOnSidebar()
  {

    $sql = "SELECT YEAR(tbl_posts.post_date) AS year_archive, 
                   MONTH(tbl_posts.post_date) AS month_archive,
                   COUNT(*) AS total_archive
            FROM tbl_posts 
            GROUP BY year_archive, month_archive
            ORDER BY year_archive DESC, month_archive";

    $this->setSQL($sql);

    $archives = $this->findAll([]);

    return (empty($archives)) ?: $archives;
  }

  /**
   * getPostsByArchive
   * 
   * @param object $perPage
   * @param object $sanitize
   * @param array $values
   * @see https://learnsql.com/cookbook/how-to-get-the-year-and-the-month-from-a-date-in-mysql/
   * @see https://stackoverflow.com/questions/21451199/get-records-in-database-by-year-and-month
   * @see https://stackoverflow.com/questions/9104704/select-mysql-based-only-on-month-and-year
   * @see https://stackoverflow.com/questions/26106362/fetch-records-from-specific-month-and-year-in-php-and-mysql
   * @see https://www.geeksforgeeks.org/php-date-format-when-inserting-into-datetime-in-mysql/
   * @see https://stackoverflow.com/questions/1440318/comparing-dates-in-php-and-mysql
   * @return mixed
   * 
   */
  public function getPostsByArchive(Paginator $perPage, Sanitize $sanitize, $values)
  {

    // collect month and year
    $month = isset($values['month_archive'])  ? prevent_injection($values['month_archive']) : "";
    $year = isset($values['year_archive'])  ? prevent_injection($values['year_archive']) : "";

    $this->linkArchives = $perPage;

    $this->linkArchives->set_total($this->totalPostsByArchives([$month, $year]));

   $sql = "SELECT p.ID, p.media_id, p.post_author, 
            p.post_date AS created_at, 
            p.post_modified AS modified_at, 
            p.post_title, p.post_slug, p.post_content, 
            p.post_summary, p.post_keyword, p.post_tags, p.post_type, p.post_status, 
            p.post_sticky, u.user_login, u.user_fullname, 
            m.media_filename, m.media_caption
            FROM tbl_posts AS p
            INNER JOIN tbl_users AS u ON p.post_author = u.ID
            INNER JOIN tbl_media AS m ON p.media_id = m.ID
            WHERE MONTH(p.post_date) = '$month' AND YEAR(p.post_date) = '$year'
            AND p.post_type = 'blog' AND p.post_status = 'publish' 
            ORDER BY DATE(p.post_date) DESC " . $this->linkArchives->get_limit($sanitize); 

    $this->setSQL($sql);

    $archivesPublished = $this->findAll([]);

    $this->pagination = $this->linkArchives->page_links($sanitize);

    return (is_iterable($archivesPublished)) ? ['archivesPublished' => $archivesPublished, 'paginationLink' => $this->pagination] : "";
  }

  /**
   * totalPostsByArchives
   *
   */
  private function totalPostsByArchives($data)
  {
    $sql = "SELECT ID FROM tbl_posts WHERE MONTH(post_date) = ? AND YEAR(post_date) = ? ";
    $this->setSQL($sql);
    return (empty($data)) ? $this->checkCountValue([]) : $this->checkCountValue($data);
  }

}
