<?php

namespace Scriptlog\Controller;
defined('SCRIPTLOG') || die("Direct access not permitted");

/**
 * Class SearchController
 *
 * Handles frontend search requests with HTMX fragment support.
 *
 * @category Controller
 * @author   Scriptlog
 * @license  MIT
 * @version  1.0
 */

use Scriptlog\Core\SearchFinder;
use Scriptlog\Core\ThemeRendererInterface;

class SearchController
{
    /**
     * @var SearchFinder|null
     */
    private $searchFinder;

    /**
     * @var ThemeRendererInterface|null
     */
    private $themeRenderer;

    public function __construct(?ThemeRendererInterface $themeRenderer = null)
    {
        $this->themeRenderer = $themeRenderer;
    }

    /**
     * Handle search request — returns full page or fragment.
     */
    public function search(): void
    {
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['keyword']) ? trim($_GET['keyword']) : '');
        $type = isset($_GET['type']) ? $_GET['type'] : 'all';

        if (empty($keyword) || mb_strlen($keyword, 'UTF-8') < 2) {
            if (is_htmx_request()) {
                render_htmx_fragment('search-results', ['results' => [], 'keyword' => '']);
                return;
            }
            $this->renderFullSearch([]);
            return;
        }

        $this->searchFinder = $this->searchFinder ?: new SearchFinder();

        switch ($type) {
            case 'posts':
                $results = $this->searchFinder->searchPost($keyword);
                break;
            case 'pages':
                $results = $this->searchFinder->searchPage($keyword);
                break;
            case 'all':
            default:
                $results = $this->searchFinder->searchAll($keyword);
                break;
        }

        $items = [];
        if (is_array($results) && !isset($results['error'])) {
            $items = $results;
        }

        if (is_htmx_request()) {
            render_htmx_fragment('search-results', ['results' => $items, 'keyword' => $keyword]);
            return;
        }

        $this->renderFullSearch($items, $keyword);
    }

    /**
     * Render full search results page.
     *
     * @param array $results
     * @param string $keyword
     */
    private function renderFullSearch(array $results, string $keyword = ''): void
    {
        $GLOBALS['search_results'] = $results;
        $GLOBALS['search_keyword'] = $keyword;

        if ($this->themeRenderer) {
            $this->themeRenderer->render('search');
            return;
        }

        http_response_code(200);
        call_theme_header();
        call_theme_content('search');
        call_theme_footer();
    }
}
