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

    /**
     * @var int
     */
    private $perPage = 10;

    /**
     * @var int
     */
    private $rateLimitMax = 30;

    /**
     * @var int
     */
    private $rateLimitWindow = 60;

    public function __construct(?ThemeRendererInterface $themeRenderer = null, ?SearchFinder $searchFinder = null)
    {
        $this->themeRenderer = $themeRenderer;
        $this->searchFinder = $searchFinder;
    }

    /**
     * Check rate limit for search endpoint.
     *
     * @return bool
     */
    private function checkRateLimit(): bool
    {
        if (!class_exists('Scriptlog\Core\RateLimiter')) {
            return true;
        }

        try {
            $limiter = new \Scriptlog\Core\RateLimiter();
            $result = $limiter->check(null, $this->rateLimitMax, $this->rateLimitWindow);
            return isset($result['allowed']) && $result['allowed'];
        } catch (\Throwable $e) {
            error_log('RateLimiter error: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Handle search request — returns full page or fragment.
     */
    public function search(): void
    {
        if (!$this->checkRateLimit()) {
            http_response_code(429);
            if (is_htmx_request()) {
                render_htmx_fragment('search-results', ['results' => [], 'totalRows' => 0, 'keyword' => ''], 429);
                return;
            }
            $GLOBALS['search_results'] = ['results' => [], 'totalRows' => 0];
            $GLOBALS['search_keyword'] = '';
            $GLOBALS['search_pagination'] = [];
            $GLOBALS['search_rate_limited'] = true;
            if ($this->themeRenderer) {
                $this->themeRenderer->render('search');
                return;
            }
            http_response_code(429);
            call_theme_header();
            call_theme_content('search');
            call_theme_footer();
            return;
        }
        $keyword = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['keyword']) ? trim($_GET['keyword']) : '');
        $type = isset($_GET['type']) ? $_GET['type'] : 'all';
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

        if ($page < 1) {
            $page = 1;
        }

        if (empty($keyword) || mb_strlen($keyword, 'UTF-8') < 2) {
            $emptyResults = ['results' => [], 'totalRows' => 0, 'page' => $page, 'perPage' => $this->perPage, 'totalPages' => 0, 'keyword' => ''];
            if (is_htmx_request()) {
                render_htmx_fragment('search-results', array_merge($emptyResults, ['keyword' => '']));
                return;
            }
            $this->renderFullSearch($emptyResults);
            return;
        }

        $this->searchFinder = $this->searchFinder ?: new SearchFinder();

        switch ($type) {
            case 'posts':
                $results = $this->searchFinder->searchPost($keyword, $page, $this->perPage);
                break;
            case 'pages':
                $results = $this->searchFinder->searchPage($keyword, $page, $this->perPage);
                break;
            case 'all':
            default:
                $results = $this->searchFinder->searchAll($keyword, $page, $this->perPage);
                break;
        }

        $items = [];
        $pagination = ['page' => $page, 'perPage' => $this->perPage, 'totalPages' => 0, 'totalRows' => 0, 'keyword' => $keyword, 'html' => ''];
        if (is_array($results)) {
            if (!isset($results['error'])) {
                $items = $results;
                $pagination = $this->buildPagination($results, $keyword);
            } else {
                error_log('Search error [code: ' . md5($results['error']) . ']: ' . substr($results['error'], 0, 200));
                $items = $results;
                $pagination['html'] = '';
            }
            unset($items['error']);
        }

        if (is_htmx_request()) {
            render_htmx_fragment('search-results', array_merge($items, ['pagination' => $pagination, 'keyword' => $keyword]));
            return;
        }

        $this->renderFullSearch($items, $keyword, $pagination);
    }

    /**
     * Build pagination data from search results.
     *
     * @param array $results
     * @param string $keyword
     * @return array
     */
    private function buildPagination(array $results, string $keyword): array
    {
        $page = isset($results['page']) ? (int)$results['page'] : 1;
        $perPage = isset($results['perPage']) ? (int)$results['perPage'] : $this->perPage;
        $totalRows = isset($results['totalRows']) ? (int)$results['totalRows'] : 0;
        $totalPages = isset($results['totalPages']) ? (int)$results['totalPages'] : 0;

        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        $html = '';
        if ($totalPages > 1) {
            $path = '?load=search&q=' . rawurlencode($keyword) . '&';
            if (isset($_GET['type']) && $_GET['type'] !== 'all') {
                $path .= 'type=' . rawurlencode($_GET['type']) . '&';
            }

            $html = $this->renderPaginationHtml($page, $totalPages, $path);
        }

        return [
            'page' => $page,
            'perPage' => $perPage,
            'totalRows' => $totalRows,
            'totalPages' => $totalPages,
            'keyword' => $keyword,
            'html' => $html
        ];
    }

    /**
     * Render pagination HTML matching existing theme pattern.
     *
     * @param int $currentPage
     * @param int $totalPages
     * @param string $path
     * @return string
     */
    private function renderPaginationHtml(int $currentPage, int $totalPages, string $path): string
    {
        if ($totalPages <= 1) {
            return '';
        }

        $path = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');

        $html = '<nav aria-label="' . htmlspecialchars(t('search.pagination_navigation'), ENT_QUOTES, 'UTF-8') . '">';
        $html .= '<ul class="pagination pagination-template d-flex justify-content-center">';

        if ($currentPage > 1) {
            $prevUrl = $path . 'page=' . ($currentPage - 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '"><i class="fa fa-angle-left" aria-hidden="true"></i><span class="sr-only">' . htmlspecialchars(t('search.page_prev'), ENT_QUOTES, 'UTF-8') . '</span></a></li>';
        }

        $maxLinks = 7;
        $halfLinks = (int)($maxLinks / 2);
        $startPage = max(1, $currentPage - $halfLinks);
        $endPage = min($totalPages, $startPage + $maxLinks - 1);
        if ($endPage - $startPage < $maxLinks - 1) {
            $startPage = max(1, $endPage - $maxLinks + 1);
        }

        if ($startPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $path . 'page=1">1</a></li>';
            if ($startPage > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i == $currentPage) {
                $html .= '<li class="page-item active"><a class="page-link" href="#" aria-current="page">' . $i . '</a></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $path . 'page=' . $i . '">' . $i . '</a></li>';
            }
        }

        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
            $html .= '<li class="page-item"><a class="page-link" href="' . $path . 'page=' . $totalPages . '">' . $totalPages . '</a></li>';
        }

        if ($currentPage < $totalPages) {
            $nextUrl = $path . 'page=' . ($currentPage + 1);
            $html .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '"><span class="sr-only">' . htmlspecialchars(t('search.page_next'), ENT_QUOTES, 'UTF-8') . '</span><i class="fa fa-angle-right" aria-hidden="true"></i></a></li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render full search results page.
     *
     * @param array $results
     * @param string $keyword
     * @param array $pagination
     */
    private function renderFullSearch(array $results, string $keyword = '', array $pagination = []): void
    {
        $GLOBALS['search_results'] = $results;
        $GLOBALS['search_keyword'] = $keyword;
        $GLOBALS['search_pagination'] = $pagination;

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
