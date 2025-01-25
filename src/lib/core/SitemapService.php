<?php defined('SCRIPTLOG') || die("Direct access not permitted");
/**
 * Class SiteMapService
 * 
 * @category Core Class
 * @author nirmalakhanza  <nirmala.adiba.khanza@email.com>
 * @uses Sitemap::addItem()
 * @uses Sitemap::createSitemapIndex()
 * @license MIT
 * @version 1.0
 * 
 */
class SiteMapService
{
    /**
     * sitemap
     *
     * @var object
     */
    private $sitemap;

    /**
     * domain
     *
     * @var string
     */
    private $domain;

    /**
     * __construct
     *
     * @param Sitemap $sitemap
     */
    public function __construct()
    {
        $this->domain = function_exists('app_url') ? app_url() . DIRECTORY_SEPARATOR : "";
        $this->sitemap = class_exists('Sitemap') ? new Sitemap($this->domain) : "";
    }

    /**
     * add URL to sitemap
     * 
     * @method private addURL()
     */
    private function addURL()
    {
        $this->sitemap->addItem("", "", "");
    }

    /**
     * adding site pages or posts to sitemap
     *
     * @method private addPages()
     * @param object $pages
     * 
     */
    private function addPages($pages)
    {
        if (!empty($pages)) {

            foreach($pages as $key => $value) {
                $this->sitemap->addItem($pages[$key]);
            }
        }
    }

    /**
     * generating sitemap
     *
     * @param object $pages
     * @return "1" |  "0"
     * 
     */
    public function generateSitemap($pages)
    {
        $this->addURL();
        $this->addPages($pages);

        return empty($this->sitemap->createSitemapIndex($this->domain, 'Now')) ? "1" : "0";

    }
}