<?php

/**
 * Blog Theme Post Helpers
 *
 * Post retrieval, topic, tag, page and archive helpers for the Bootstrap
 * Blog theme. Extracted from the monolithic functions.php (Phase 5
 * remediation). Raw SQL has been routed through PostDao so templates never
 * reach into the database layer directly. All functions use
 * function_exists() guards to avoid redeclaration errors.
 *
 * @category Theme Function
 * @package Scriptlog
 */

defined('SCRIPTLOG') || die('Direct access not permitted');

/**
 * previous_post() - Get previous post link
 */
if (!function_exists('previous_post')) {
    function previous_post($id)
    {
        $html = null;

        $postDao = class_exists('PostDao') ? new PostDao() : null;
        if (!$postDao) {
            return $html;
        }

        $row = $postDao->findAdjacentPost((int)$id, 'previous');

        if (!empty($row)) {
            $html .= '<a href="' . permalinks($row['ID'])['post'] . '" class="prev-post text-left d-flex align-items-center">';
            $html .= '<div class="icon prev"><i class="fa fa-angle-left" aria-hidden="true"></i></div>';
            $html .= '<div class="text"><strong class="text-primary">Previous Post </strong>';
            $html .= '<h6>' . theme_escape_html($row['post_title']) . '</h6>';
            $html .= '</div>';
            $html .= '</a>';
        }

        return $html;
    }
}

/**
 * next_post() - Get next post link
 */
if (!function_exists('next_post')) {
    function next_post($id)
    {
        $html = null;

        $postDao = class_exists('PostDao') ? new PostDao() : null;
        if (!$postDao) {
            return $html;
        }

        $row = $postDao->findAdjacentPost((int)$id, 'next');

        if (!empty($row)) {
            $html .= '<a href="' . permalinks($row['ID'])['post'] . '"  class="next-post text-right d-flex align-items-center justify-content-end">';
            $html .= '<div class="text"><strong class="text-primary">Next Post </strong>';
            $html .= '<h6>' . theme_escape_html($row['post_title']) . '</h6>';
            $html .= '</div>';
            $html .= '<div class="icon next"><i class="fa fa-angle-right" aria-hidden="true"></i></div>';
            $html .= '</a>';
        }

        return $html;
    }
}

/**
 * initialize_page()
 */
if (!function_exists('initialize_page')) {
    function initialize_page()
    {
        return class_exists('PageModel') ? new PageModel() : "";
    }
}

/**
 * initialize_post()
 */
if (!function_exists('initialize_post')) {
    function initialize_post()
    {
        return class_exists('PostModel') ? new PostModel() : "";
    }
}

/**
 * initialize_comment()
 */
if (!function_exists('initialize_comment')) {
    function initialize_comment()
    {
        return class_exists('CommentModel') ? new CommentModel() : "";
    }
}

/**
 * initialize_archive()
 */
if (!function_exists('initialize_archive')) {
    function initialize_archive()
    {
        return class_exists('ArchivesModel') ? new ArchivesModel() : "";
    }
}

/**
 * initialize_topic()
 */
if (!function_exists('initialize_topic')) {
    function initialize_topic()
    {
        return class_exists('TopicModel') ? new TopicModel() : "";
    }
}

/**
 * initialize_tag()
 */
if (!function_exists('initialize_tag')) {
    function initialize_tag()
    {
        return class_exists('TagModel') ? new TagModel() : "";
    }
}

/**
 * initialize_gallery()
 */
if (!function_exists('initialize_gallery')) {
    function initialize_gallery()
    {
        return class_exists('GalleryModel') ? new GalleryModel() : "";
    }
}

/**
 * featured_post() - Get random headline posts
 */
if (!function_exists('featured_post')) {
    function featured_post()
    {
        $headlines = class_exists('FrontContentModel') ? FrontContentModel::frontRandomHeadlines(initialize_post()) : "";
        return is_iterable($headlines) ? $headlines : array();
    }
}

/**
 * sticky_page() - Get random sticky page
 */
if (!function_exists('sticky_page')) {
    function sticky_page()
    {
        $sticky_page = class_exists('FrontContentModel') ? FrontContentModel::frontRandomStickyPage(initialize_page()) : "";
        return is_iterable($sticky_page) ? $sticky_page : array();
    }
}

/**
 * random_posts() - Get random posts
 */
if (!function_exists('random_posts')) {
    function random_posts($start, $end)
    {
        $random_posts = class_exists('FrontContentModel') ? FrontContentModel::frontRandomPosts($start, $end, initialize_post()) : "";
        return is_iterable($random_posts) ? $random_posts : array();
    }
}

/**
 * latest_posts() - Get latest posts
 */
if (!function_exists('latest_posts')) {
    function latest_posts($limit, $position = null)
    {
        $latest_posts = class_exists('FrontContentModel') ? FrontContentModel::frontLatestPosts($limit, initialize_post(), $position) : "";
        return is_iterable($latest_posts) ? $latest_posts : array();
    }
}

/**
 * prepare_post_card() - Normalize a raw post row into a display-ready card.
 *
 * Centralizes the escaping/formatting that list templates used to repeat
 * inline (title, image, date, author, comment count, topics) so the shared
 * card partial can render from a single, already-safe PostViewModel. Content is
 * sanitized via paragraph_trim() + safe_html() and intentionally NOT
 * html_entity_decode()'d - decoding sanitized content would undo escaping.
 *
 * @param array<string, mixed> $entry
 * @return PostViewModel already-safe card fields: id, title, url, content,
 *                          media (img), media_caption (img_caption), date,
 *                          author, comments, topics
 */
if (!function_exists('prepare_post_card')) {
    function prepare_post_card(array $entry): \Scriptlog\Core\Theme\PostViewModel
    {
        $id = isset($entry['ID']) ? (int)$entry['ID'] : 0;
        $title = isset($entry['post_title']) ? theme_escape_html($entry['post_title']) : "";
        $img = (isset($entry['media_filename']) && $entry['media_filename'] !== '')
            ? theme_escape_html($entry['media_filename'])
            : "";
        $img_caption = (isset($entry['media_caption']) && $entry['media_caption'] !== '')
            ? theme_escape_html($entry['media_caption'])
            : $title;
        $content = (!isset($entry['post_content']) || $entry['post_content'] === '')
            ? ""
            : paragraph_l2br(safe_html(paragraph_trim($entry['post_content'])));
        $created = isset($entry['created_at']) ? $entry['created_at'] : "";
        $modified = isset($entry['modified_at']) ? $entry['modified_at'] : $created;
        $date = ($modified !== '') ? make_date($modified) : make_date($created);
        $author = (isset($entry['user_login']) && $entry['user_login'] !== '')
            ? theme_escape_html($entry['user_login'])
            : theme_escape_html($entry['user_fullname'] ?? '');
        $comments = (isset($entry['total_comments']) && is_numeric($entry['total_comments']))
            ? (int)$entry['total_comments']
            : (function_exists('total_comment') && $id > 0 ? (int)total_comment($id)['total'] : 0);
        $topics = (isset($entry['topics_data']) && $entry['topics_data'] !== '')
            ? format_topics($entry['topics_data'])
            : (function_exists('retrieves_topic_simple') && $id > 0 ? retrieves_topic_simple($id) : "");
        $url = (isset($entry['url']) && $entry['url'] !== '')
            ? theme_escape_html($entry['url'])
            : theme_post_url($entry);

        return ThemeHelper::factory()->makePostFromPrepared(array(
            'id'            => $id,
            'title'         => $title,
            'url'           => $url,
            'content'       => $content,
            'media'         => $img,
            'media_caption' => $img_caption,
            'date'          => theme_escape_html($date),
            'author'        => $author,
            'comments'      => $comments,
            'topics'        => $topics,
        ));
    }
}

/**
 * theme_post_url() - Build a post URL without a per-row database round-trip.
 *
 * Prepared post rows already carry ID + post_slug, so the rewrite or
 * query-string URL can be composed here. App-level config (app_url and the
 * permalink setting) is read at most once per request and memoized in a
 * static, so list loops stop calling the DB-bound permalinks() helper for
 * every item (Phase 7 N+1 fix). Output matches permalinks()' post URL shape.
 *
 * @param array<string, mixed> $row Prepared post row (ID, post_slug, optional url)
 * @return string Escaped absolute post URL, or "#" when no usable ID
 */
if (!function_exists('theme_post_url')) {
    function theme_post_url(array $row): string
    {
        static $base = null;
        static $rewrite = null;

        $id = isset($row['ID']) ? (int)$row['ID'] : 0;

        if ($id <= 0) {
            return "#";
        }

        if ($base === null) {
            $base = (string)app_url();
            $rewrite = (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes');
        }

        if ($rewrite) {
            $slug = isset($row['post_slug']) ? $row['post_slug'] : '';
            $url = $base . DS . 'post' . DS . $id . ($slug !== '' ? DS . $slug : '');
        } else {
            $url = $base . DS . '?p=' . $id;
        }

        return theme_escape_html($url);
    }
}

/**
 * theme_topic_url() - Build a category URL without a per-row DB round-trip.
 *
 * Mirrors theme_post_url() for topics: the row carries topic_id + topic_slug
 * and the app-level permalink config is memoized per request.
 *
 * @param array<string, mixed> $row Prepared topic row (ID, topic_slug, optional url)
 * @return string Escaped absolute category URL, or "#" when no usable ID
 */
if (!function_exists('theme_topic_url')) {
    function theme_topic_url(array $row): string
    {
        static $base = null;
        static $rewrite = null;

        $id = isset($row['ID']) ? (int)$row['ID'] : 0;

        if ($id <= 0) {
            return "#";
        }

        if ($base === null) {
            $base = (string)app_url();
            $rewrite = (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes');
        }

        if ($rewrite) {
            $slug = isset($row['topic_slug']) ? $row['topic_slug'] : '';
            $url = $base . DS . 'category' . DS . ($slug !== '' ? $slug : $id);
        } else {
            $url = $base . DS . '?cat=' . $id;
        }

        return theme_escape_html($url);
    }
}

/**
 * theme_page_url() - Build a static page URL without a per-row DB round-trip.
 *
 * Mirrors theme_post_url() for pages: the row carries ID + post_slug and the
 * permalink config is memoized per request. Matches permalinks()' page shape
 * (rewrite: /page/{slug}, query-string: ?pg={id}).
 *
 * @param array<string, mixed> $row Prepared page row (ID, post_slug)
 * @return string Escaped absolute page URL, or "#" when no usable ID
 */
if (!function_exists('theme_page_url')) {
    function theme_page_url(array $row): string
    {
        static $base = null;
        static $rewrite = null;

        $id = isset($row['ID']) ? (int)$row['ID'] : 0;

        if ($id <= 0) {
            return "#";
        }

        if ($base === null) {
            $base = (string)app_url();
            $rewrite = (function_exists('is_permalink_enabled') && is_permalink_enabled() === 'yes');
        }

        if ($rewrite) {
            $slug = isset($row['post_slug']) ? $row['post_slug'] : '';
            $url = $base . DS . 'page' . DS . ($slug !== '' ? $slug : $id);
        } else {
            $url = $base . DS . '?pg=' . $id;
        }

        return theme_escape_html($url);
    }
}

/**
 * theme_tag_url() - Build a tag archive URL without a per-row DB round-trip.
 *
 * Mirrors tag_path()/tag_query() but returns a single escaped URL for one tag
 * instead of an HTML <li> list, so callers can compose structured sidebar tag
 * links.
 *
 * @param string $tag Raw tag value (display form)
 * @return string Escaped absolute tag URL
 */
if (!function_exists('theme_tag_url')) {
    function theme_tag_url(string $tag): string
    {
        static $base = null;
        static $rewrite = null;

        if ($base === null) {
            $base = (string)app_url();
            $rewrite = (function_exists('rewrite_status') && rewrite_status() === 'yes');
        }

        $url = ($rewrite === true)
            ? $base . DS . 'tag' . DS . $tag
            : $base . DS . '?tag=' . $tag;

        return theme_escape_html($url);
    }
}

/**
 * theme_month_name() - Localized month number to English month name.
 *
 * @param string $month Month as number (1 or 2 digits, e.g. "3" or "03")
 * @return string English month name, or the raw input on failure
 */
if (!function_exists('theme_month_name')) {
    function theme_month_name(string $month): string
    {
        $padded = str_pad($month, 2, '0', STR_PAD_LEFT);

        if (class_exists('DateTime')) {
            $obj = DateTime::createFromFormat('!m', $padded);
            if ($obj !== false && method_exists($obj, 'format')) {
                $name = $obj->format('F');
                if ($name !== '') {
                    return $name;
                }
            }
        }

        return $month;
    }
}

/**
 * theme_archive_url() - Build a monthly archive URL without a per-row DB
 * round-trip.
 *
 * @param string $month Archive month (1 or 2 digits)
 * @param string $year  Archive year (4 digits)
 * @return string Escaped absolute archive URL
 */
if (!function_exists('theme_archive_url')) {
    function theme_archive_url(string $month, string $year): string
    {
        static $base = null;
        static $rewrite = null;

        if ($base === null) {
            $base = (string)app_url();
            $rewrite = (function_exists('rewrite_status') && rewrite_status() === 'yes');
        }

        $padded = str_pad($month, 2, '0', STR_PAD_LEFT);

        $url = ($rewrite === true)
            ? $base . DS . 'archive' . DS . $padded . DS . $year
            : $base . DS . '?a=' . $year . $padded;

        return theme_escape_html($url);
    }
}

/**
 * prepare_page() - Normalize a raw page row into a display-ready PageViewModel.
 *
 * Escapes or sanitizes every value exactly once at this boundary, then hands
 * the prepared array to the shared factory. Mirrors the old page.php inline
 * logic: author falls back from user_fullname to user_login, date prefers
 * post_date over post_modified, and content is sanitized through the same
 * html()/htmLawed()/html_entity_decode() pipeline before being stored as-is.
 *
 * @param array<string, mixed> $entry Raw page row from retrieve_page()
 * @return PageViewModel
 */
if (!function_exists('prepare_page')) {
    function prepare_page(array $entry)
    {
        $page_id = isset($entry['ID']) ? (int)$entry['ID'] : 0;

        $page_author = (isset($entry['user_login']) && $entry['user_login'] !== '')
            ? theme_escape_html($entry['user_login'])
            : ((isset($entry['user_fullname']) && $entry['user_fullname'] !== '') ? theme_escape_html($entry['user_fullname']) : '');

        $page_created = '';
        if (isset($entry['post_date']) && $entry['post_date'] !== '') {
            $page_created = theme_escape_html(make_date($entry['post_date']));
        } elseif (isset($entry['post_modified']) && $entry['post_modified'] !== '') {
            $page_created = theme_escape_html(make_date($entry['post_modified']));
        }

        $page_content = (isset($entry['post_content']) && $entry['post_content'] !== '')
            ? html_entity_decode(htmLawed(html($entry['post_content'])), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401)
            : '';

        return ThemeHelper::factory()->makePageFromPrepared(array(
            'id'            => (string)$page_id,
            'title'         => isset($entry['post_title']) ? theme_escape_html($entry['post_title']) : '',
            'url'           => theme_page_url($entry),
            'slug'          => isset($entry['post_slug']) ? theme_escape_html($entry['post_slug']) : '',
            'author'        => $page_author,
            'date'          => $page_created,
            'excerpt'       => isset($entry['post_summary']) ? theme_escape_html($entry['post_summary']) : '',
            'content'       => $page_content,
            'media'         => isset($entry['media_filename']) ? theme_escape_html($entry['media_filename']) : '',
            'media_caption' => isset($entry['media_caption']) ? theme_escape_html($entry['media_caption']) : '',
            'tags'          => isset($entry['post_tags']) ? theme_escape_html($entry['post_tags']) : '',
        ));
    }
}

/**
 * prepare_archive() - Normalize a raw archive row into a display-ready
 * ArchiveViewModel.
 *
 * @param array<string, mixed> $entry Raw archive row (year_archive,
 *                                    month_archive, total_archive)
 * @return ArchiveViewModel
 */
if (!function_exists('prepare_archive')) {
    function prepare_archive(array $entry)
    {
        $year = isset($entry['year_archive']) ? (string)$entry['year_archive'] : '';
        $month = isset($entry['month_archive']) ? (string)$entry['month_archive'] : '';
        $count = isset($entry['total_archive']) ? (string)$entry['total_archive'] : '';

        return ThemeHelper::factory()->makeArchiveFromPrepared(array(
            'url'   => theme_archive_url($month, $year),
            'label' => theme_escape_html(theme_month_name($month)),
            'year'  => theme_escape_html($year),
            'month' => theme_escape_html(str_pad($month, 2, '0', STR_PAD_LEFT)),
            'count' => theme_escape_html($count),
        ));
    }
}

/**
 * prepare_sidebar() - Normalize raw sidebar aggregates into a display-ready
 * SidebarViewModel.
 *
 * Gathers latest posts, categories, archives and tags through the existing
 * theme helpers, escaping each value exactly once, then delegates to the
 * shared factory.
 *
 * @return SidebarViewModel
 */
if (!function_exists('prepare_sidebar')) {
    function prepare_sidebar()
    {
        $latest = array();
        if (function_exists('latest_posts')) {
            $posts = latest_posts(5, 'sidebar');
            $items = (isset($posts['sidebarPosts']) && is_array($posts['sidebarPosts'])) ? $posts['sidebarPosts'] : array();

            foreach ($items as $latest_post) {
                $post_id = isset($latest_post['ID']) ? abs((int)$latest_post['ID']) : 0;
                $author = (isset($latest_post['user_login']) && $latest_post['user_login'] !== '')
                    ? theme_escape_html($latest_post['user_login'])
                    : ((isset($latest_post['user_fullname']) && $latest_post['user_fullname'] !== '') ? theme_escape_html($latest_post['user_fullname']) : '');

                $total_comment = (isset($latest_post['total_comments']) && is_numeric($latest_post['total_comments']))
                    ? (string)(int)$latest_post['total_comments']
                    : (($post_id > 0 && function_exists('total_comment')) ? (string)(int)total_comment($post_id)['total'] : '0');

                $latest[] = ThemeHelper::factory()->makePostFromPrepared(array(
                    'id'       => (string)$post_id,
                    'title'    => isset($latest_post['post_title']) ? theme_escape_html($latest_post['post_title']) : '',
                    'url'      => $post_id > 0 ? theme_post_url($latest_post) : '#',
                    'author'   => $author,
                    'comments' => $total_comment,
                ));
            }
        }

        $categories = array();
        if (function_exists('sidebar_topics')) {
            foreach (sidebar_topics() as $category) {
                $categories[] = array(
                    'title' => isset($category['topic_title']) ? theme_escape_html($category['topic_title']) : '',
                    'url'   => (isset($category['ID']) && (int)$category['ID'] > 0) ? theme_topic_url($category) : '#',
                    'count' => isset($category['total_posts']) ? (string)$category['total_posts'] : '',
                );
            }
        }

        $archives = array();
        if (function_exists('retrieve_archives')) {
            foreach (retrieve_archives() as $archives_row) {
                $month = isset($archives_row['month_archive']) ? (string)$archives_row['month_archive'] : '';
                $year = isset($archives_row['year_archive']) ? (string)$archives_row['year_archive'] : '';
                $archives[] = array(
                    'label' => theme_escape_html(theme_month_name($month) . ' ' . $year),
                    'url'   => theme_archive_url($month, $year),
                    'count' => isset($archives_row['total_archive']) ? theme_escape_html((string)$archives_row['total_archive']) : '',
                );
            }
        }

        $tags = array();
        $frontService = function_exists('front_service') ? front_service() : null;
        if ($frontService && method_exists($frontService, 'getTagLists')) {
            foreach ($frontService->getTagLists() as $tag) {
                $tag = (string)$tag;
                $tags[] = array(
                    'label' => theme_escape_html($tag),
                    'url'   => theme_tag_url($tag),
                );
            }
        }

        return ThemeHelper::factory()->makeSidebarFromPrepared(array(
            'latest_posts'  => $latest,
            'categories'    => $categories,
            'archives'      => $archives,
            'tags'          => $tags,
            'search_action' => (string)app_url() . '/search',
        ));
    }
}

/**
 * Format topics data into HTML category links
 *
 * Parses pipe-delimited topic strings (id:title:slug) from the database
 * and returns comma-separated HTML anchor links. Handles colons in
 * topic titles by extracting ID from the start and slug from the end.
 *
 * @param string|null $topics_data Pipe-delimited topic data from GROUP_CONCAT
 * @return string HTML links joined by ', ' or empty string
 */
if (!function_exists('format_topics')) {
    function format_topics($topics_data)
    {
        if (empty($topics_data)) {
            return "";
        }

        $topics = explode('|', $topics_data);
        $links = [];

        foreach ($topics as $topic) {
            $topic = trim($topic);

            if ($topic === '') {
                continue;
            }

            $parts = explode(':', $topic);

            if (count($parts) < 3) {
                continue;
            }

            $id = array_shift($parts);
            $slug = array_pop($parts);
            $title = implode(':', $parts);

            if ($id === '' || $slug === '') {
                continue;
            }

            $permalink = (function_exists('theme_topic_url'))
                ? theme_topic_url(['ID' => $id, 'topic_slug' => $slug])
                : ((function_exists('rewrite_status') && rewrite_status() === 'yes')
                    ? permalinks($slug)['cat']
                    : permalinks($id)['cat']);

            $title_esc = theme_escape_html($title);
            $permalink_esc = theme_escape_html($permalink);
            $links[] = "<a href='{$permalink_esc}'>{$title_esc}</a>";
        }

        return $links ? implode(' ', $links) : "";
    }
}

/**
 * retrieves_topic_simple() - Get topic links for post
 */
if (!function_exists('retrieves_topic_simple')) {
    function retrieves_topic_simple($id)
    {
        $categories = array();

        $postDao = class_exists('PostDao') ? new PostDao() : null;
        if (!$postDao) {
            return "";
        }

        $topics = $postDao->findActiveTopicsByPostId((int)$id);

        foreach ($topics as $result) {
            $permalinks = (function_exists('rewrite_status') && rewrite_status() === 'yes')
                ? (permalinks($result['topic_slug'])['cat'] ?? '#')
                : (permalinks($result['ID'])['cat'] ?? '#');

            $topic_title = theme_escape_html($result['topic_title']);

            $categories[] = "<a href='{$permalinks}'>{$topic_title}</a>";
        }

        return implode("", $categories);
    }
}

/**
 * retrieves_topic_prepared() - Get topic links with status check
 */
if (!function_exists('retrieves_topic_prepared')) {
    function retrieves_topic_prepared($id)
    {
        $topics = null;

        $postDao = class_exists('PostDao') ? new PostDao() : null;
        if (!$postDao) {
            return "";
        }

        $items = $postDao->findActiveTopicsByPostId((int)$id);

        foreach ($items as $item) {
            $permalinks = ((function_exists('rewrite_status')) && (rewrite_status() === 'yes') ? permalinks($item['topic_slug'])['cat'] : permalinks($item['ID'])['cat']);
            $topics[] = "<a href='" . $permalinks . "'>" . theme_escape_html($item['topic_title']) . "</a>";
        }

        return implode("", $topics ?? []);
    }
}

/**
 * sidebar_topics() - Get topics for sidebar
 */
if (!function_exists('sidebar_topics')) {
    function sidebar_topics()
    {
        $sidebar_topics = class_exists('FrontContentModel') ? FrontContentModel::frontSidebarTopics(initialize_topic()) : "";
        return is_iterable($sidebar_topics) ? $sidebar_topics : array();
    }
}

/**
 * retrieve_tags() - Get tags for sidebar
 */
if (!function_exists('retrieve_tags')) {
    function retrieve_tags()
    {
        return (function_exists('outputting_tags')) ? outputting_tags() : "";
    }
}

/**
 * link_tag() - Generate tag links
 */
if (!function_exists('link_tag')) {
    function link_tag($id)
    {
        // Add validation like link_topic() has
        $id_int = filter_var($id, FILTER_VALIDATE_INT);

        if ($id_int === false || $id_int <= 0) {
            return "";  // Return empty string for invalid IDs
        }
        return (class_exists('FrontContentModel')) ? FrontContentModel::frontLinkTag($id, initialize_tag()) : "";
    }
}

/**
 * link_topic() - Generate topic link
 */
if (!function_exists('link_topic')) {
    function link_topic($id): string
    {
        // Validate as integer
        $id_int = filter_var($id, FILTER_VALIDATE_INT);

        if ($id_int === false || $id_int <= 0) {
            return "";
        }

        if (!class_exists('FrontContentModel')) {
            return "";
        }

        return FrontContentModel::frontLinkTopic($id_int, initialize_topic());
    }
}

/**
 * retrieve_blog_posts() - Get all blog posts
 */
if (!function_exists('retrieve_blog_posts')) {
    function retrieve_blog_posts()
    {
        $posts = class_exists('FrontContentModel') ? FrontContentModel::frontBlogPosts(initialize_post()) : "";
        return is_iterable($posts) ? $posts : array();
    }
}

/**
 * retrieve_detail_post() - Get single post by ID
 */
if (!function_exists('retrieve_detail_post')) {
    function retrieve_detail_post($id)
    {
        // Validate ID first
        $id_int = filter_var($id, FILTER_VALIDATE_INT);
        if ($id_int === false || $id_int <= 0) {
            error_log("retrieve_detail_post called with invalid ID: " . print_r($id, true));
            return array(); // Return empty array
        }

        $detail_post = class_exists('FrontContentModel') ? FrontContentModel::frontPostById($id, initialize_post()) : "";

        // Ensure we return an array and validate the result
        if (is_iterable($detail_post) && !empty($detail_post) && isset($detail_post['ID']) && (int)$detail_post['ID'] > 0) {
            return $detail_post;
        }

        return array();
        // $detail_post = class_exists('FrontContentModel') ? FrontContentModel::frontPostById($id, initialize_post()) : "";
        // return is_iterable($detail_post) ? $detail_post : array();
    }
}

/**
 * posts_by_archive() - Get posts by archive
 */
if (!function_exists('posts_by_archive')) {
    function posts_by_archive(array $values)
    {
        $archives = class_exists('FrontContentModel') ? FrontContentModel::frontPostsByArchive($values, initialize_archive()) : "";
        return is_iterable($archives) ? $archives : array();
    }
}

/**
 * archive_index() - Get all archives for index
 */
if (!function_exists('archive_index')) {
    function archive_index()
    {
        $archives = class_exists('FrontContentModel') ? FrontContentModel::frontArchiveIndex(initialize_archive()) : "";
        return is_iterable($archives) ? $archives : array();
    }
}

/**
 * posts_by_tag() - Get posts by tag
 */
if (!function_exists('posts_by_tag')) {
    function posts_by_tag($tag)
    {
        $tags = class_exists('FrontContentModel') ? FrontContentModel::frontPostsByTag($tag, initialize_tag()) : "";
        return is_iterable($tags) ? $tags : array();
    }
}

/**
 * searching_by_tag() - Full-text tag search
 */
if (!function_exists('searching_by_tag')) {
    function searching_by_tag($tag)
    {
        $frontService = function_exists('front_service') ? front_service() : null;
        $tags = $frontService ? $frontService->searchTag($tag) : "";
        return is_iterable($tags) ? $tags : array();
    }
}

/**
 * posts_by_category() - Get posts by category
 */
if (!function_exists('posts_by_category')) {
    function posts_by_category($topicId)
    {
        $entries = FrontContentModel::frontPostsByTopic($topicId, initialize_topic())['entries'];
        $pagination = FrontContentModel::frontPostsByTopic($topicId, initialize_topic())['pagination'];

        return is_iterable($entries) ? array('entries' => $entries, 'pagination' => $pagination) : array();
    }
}

/**
 * retrieve_archives() - Get archives for sidebar
 */
if (!function_exists('retrieve_archives')) {
    function retrieve_archives()
    {
        $archives = class_exists('FrontContentModel') ? FrontContentModel::frontSidebarArchives(initialize_archive()) : "";
        return is_iterable($archives) ? $archives : array();
    }
}

/**
 * retrieve_page() - Get page by ID or slug
 */
if (!function_exists('retrieve_page')) {
    function retrieve_page($arg, $rewrite)
    {
        if ($rewrite == 'no') {
            $page = class_exists('FrontContentModel') ? FrontContentModel::frontPageById($arg, initialize_page()) : "";
            return is_iterable($page) ? $page : [];
        } else {
            $page = class_exists('FrontContentModel') ? FrontContentModel::frontPageBySlug($arg, initialize_page()) : "";
            return is_iterable($page) ? $page : [];
        }
    }
}

/**
 * nothing_found() - Display "no posts" message
 */
if (!function_exists('nothing_found')) {
    function nothing_found()
    {
        $site_url = function_exists('app_url') ? app_url() . "/admin/login.php" : "";

        return <<<_NOTHING_FOUND
<div class="alert alert-warning" role="alert">
  <h4 class="alert-heading">Whoops!</h4>
  <p>I haven't posted to my blog yet!</p>
  <hr>
  <p class="mb-0">Please go to <a href="$site_url" target="_blank" rel="noopener noreferrer" title="administrator panel">administrator panel</a> to populate your blog.</p>
</div>
_NOTHING_FOUND;
    }
}
