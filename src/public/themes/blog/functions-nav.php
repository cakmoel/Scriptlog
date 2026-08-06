<?php

/**
 * Blog Theme Navigation Helpers
 *
 * Navigation and menu helpers for the Bootstrap Blog theme.
 * Extracted from the monolithic functions.php (Phase 5 remediation).
 * All functions use function_exists() guards to avoid redeclaration errors.
 *
 * @category Theme Function
 * @package Scriptlog
 */

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * request_path() - Get request path object
 */
if (!function_exists('request_path')) {
    function request_path()
    {
        return class_exists('RequestPath') ? new RequestPath() : "";
    }
}

/**
 * build_menu_tree() - Build a recursive MenuViewModel tree from raw menu rows.
 *
 * Converts each raw item once (link via convert_menu_link(), label escaped),
 * attaches children through MenuViewModel::setChildren(), and returns only the
 * roots reachable from $rootId. The rendering layer then walks the typed tree
 * instead of re-parsing raw arrays.
 *
 * @param array<int, MenuViewModel> $items Menu items keyed by ID
 * @param array<int, array<int>> $parents Parent-id => child-id map
 * @param int $rootId Parent id whose children form the tree root
 * @return MenuViewModel[]
 */
if (!function_exists('build_menu_tree')) {
    function build_menu_tree(array $items, array $parents, int $rootId = 0): array
    {
        $tree = array();

        if (!isset($parents[$rootId])) {
            return $tree;
        }

        foreach ($parents[$rootId] as $itemId) {
            if (!isset($items[$itemId])) {
                continue;
            }

            $node = $items[$itemId];
            $node->setChildren(build_menu_tree($items, $parents, (int)$itemId));
            $tree[] = $node;
        }

        return $tree;
    }
}

/**
 * render_menu_tree() - Render a MenuViewModel tree to HTML.
 *
 * Produces the exact dropdown markup front_navigation() used to emit, but
 * reads already-escaped values off MenuViewModel getters.
 *
 * @param MenuViewModel[] $nodes
 * @return string
 */
if (!function_exists('render_menu_tree')) {
    function render_menu_tree(array $nodes): string
    {
        $html = "";

        foreach ($nodes as $node) {
            if ($node->hasChildren()) {
                $html .= "<li class='dropdown'><a class='dropdown-toggle' data-toggle='dropdown' href='" . $node->url() . "'>" . $node->label() . "</a>";
                $html .= '<ul class="dropdown-menu">';
                $html .= render_menu_tree($node->children());
                $html .= '</ul>';
                $html .= "</li>";
            } else {
                $html .= "<li><a href='" . $node->url() . "'>" . $node->label() . "</a></li>";
            }
        }

        return $html;
    }
}

/**
 * front_navigation() - Render navigation menu from a MenuViewModel tree.
 *
 * Normalizes theme_navigation()'s raw items/parents into typed menu view
 * models (single escape boundary) and renders the recursive tree. Keeping the
 * original ($parent, $menu) signature preserves the header.php call site.
 *
 * @param int $parent Root menu parent id (usually 0)
 * @param array<string, mixed> $menu Raw menu from theme_navigation()
 * @return string
 */
if (!function_exists('front_navigation')) {
    function front_navigation($parent, $menu)
    {
        $permalinkEnabled = function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes';

        $items = array();
        $rawItems = (isset($menu['items']) && is_array($menu['items'])) ? $menu['items'] : array();

        foreach ($rawItems as $id => $item) {
            $label = isset($item['menu_label']) ? theme_escape_html((string)$item['menu_label']) : "";
            $link = isset($item['menu_link']) ? (string)$item['menu_link'] : "#";
            $convertedLink = theme_escape_html(convert_menu_link($link, $permalinkEnabled));

            $items[$id] = ThemeHelper::factory()->makeMenuFromPrepared(array(
                'id'     => (string)$id,
                'label'  => $label,
                'url'    => $convertedLink,
                'parent' => isset($item['parent_id']) ? (string)$item['parent_id'] : "0",
            ));
        }

        $parents = (isset($menu['parents']) && is_array($menu['parents'])) ? $menu['parents'] : array();

        $tree = build_menu_tree($items, $parents, (int)$parent);

        return render_menu_tree($tree);
    }
}

/**
 * convert_menu_link() - Convert menu link between URL formats
 */
if (!function_exists('convert_menu_link')) {
    function convert_menu_link(string $link, bool $permalinkEnabled): string
    {
        if (empty($link) || $link === '#' || strpos($link, '://') !== false || strpos($link, 'mailto:') !== false || strpos($link, '#') === 0) {
            return $link;
        }

        if (strpos($link, 'http://') === 0 || strpos($link, 'https://') === 0) {
            return $link;
        }

        if ($permalinkEnabled) {
            if (preg_match('/^\?p=(\d+)$/', $link, $matches)) {
                $id = $matches[1];
                $converted = permalinks($id);
                return $converted['post'] ?? $link;
            }

            if (preg_match('/^\?pg=(\d+)$/', $link, $matches)) {
                $id = $matches[1];
                $converted = permalinks($id);
                return $converted['page'] ?? $link;
            }

            if (preg_match('/^\?cat=(\d+)$/', $link, $matches)) {
                $id = $matches[1];
                $converted = permalinks($id);
                return $converted['cat'] ?? $link;
            }

            if (preg_match('/^\?a=(\d+)$/', $link, $matches)) {
                $id = $matches[1];
                $converted = permalinks($id);
                return $converted['archive'] ?? $link;
            }

            if (strpos($link, '/') === 0) {
                return $link;
            }

            $cleanLink = str_replace('.php', '', $link);
            if (strpos($cleanLink, '/') !== 0) {
                $cleanLink = '/' . $cleanLink;
            }

            return $cleanLink;
        } else {
            if (preg_match('/^\/post\/(\d+)\/[\w-]+$/', $link, $matches)) {
                return '?p=' . $matches[1];
            }

            if (preg_match('/^\/page\/([\w-]+)$/', $link, $matches)) {
                $frontService = function_exists('front_service') ? front_service() : null;
                if ($frontService) {
                    $page = $frontService->getPublishedPage($matches[1]);
                    return '?pg=' . ($page['ID'] ?? 1);
                }
                return '?pg=1';
            }

            if (preg_match('/^\/category\/([\w-]+)$/', $link, $matches)) {
                $frontService = function_exists('front_service') ? front_service() : null;
                if ($frontService) {
                    $cat = $frontService->getPublishedTopic($matches[1]);
                    return '?cat=' . ($cat['ID'] ?? 1);
                }
                return '?cat=1';
            }

            if (preg_match('/^\/archive\/(\d{2})\/(\d{4})$/', $link, $matches)) {
                return '?a=' . $matches[2] . $matches[1];
            }

            if (strpos($link, '/') === 0) {
                return '?' . ltrim($link, '/');
            }

            return $link;
        }
    }
}

/**
 * retrieve_site_url() - Get site URL from config
 */
if (!function_exists('retrieve_site_url')) {
    function retrieve_site_url()
    {
        $config_file = read_config(invoke_config());
        return isset($config_file['app']['url']) ? $config_file['app']['url'] : "";
    }
}
