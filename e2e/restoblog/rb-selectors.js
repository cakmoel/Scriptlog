// @ts-check
// rb-selectors.js
// Single source of truth for RestoBlog (Epicurean Dark) theme DOM selectors
// used by the e2e suite. Values verified against the rendered markup in
// August 2026 (Phase 5 of plan/RESTOBLOG_THEME_IMPLEMENTATION_PLAN.md).
// Dynamic ids use attribute prefixes; the pagination selectors are scoped to
// the shared Paginator output (ul.pagination > li.page-item > a.page-link).
//
// Notable differences from the TastyBites suite:
// - Cookies consent is LOCALSTORAGE-driven (main.js), not a GDPR API POST.
// - The sidebar search form is a plain GET form (?q= on the app root), not an
//   AJAX /api/v1/search widget - so no API response is asserted.
// - Vanilla JS end-to-end (no jQuery anywhere for the theme; Fancybox v5 UMD
//   is used for the home gallery lightbox and, like the blog theme's asset
//   convention, ships with SRI + crossorigin). The gallery assets are loaded
//   ONLY on pages that render a [data-fancybox] gallery (home), so assertions
//   exist for both presence (home) and absence everywhere else.
// - The 404 surface uses .error-404-* instead of a Bootstrap display-1.

export const RB = {
  // Shell (header.php / footer.php)
  main: 'main#main-content',
  footer: 'footer.main-footer',
  headerNav: 'nav.header-nav',
  skipLink: 'a.skip-link',
  brand: '.header-nav .brand',
  brandTitle: '.brand-title',
  navSearchForm: '.nav-search-form',
  navSearchInput: '.nav-search-input',
  pageHeader: '.page-header h1',

  // Home (home.php)
  hero: 'section.hero',
  heroTitle: '.hero h1.hero-title',
  heroButtons: '.hero-buttons',
  heroExploreButton: '.hero-buttons a.btn-secondary',
  specialMenu: '#special-menu',
  menuCard: '.menu-card',
  articlesGrid: '.articles-grid',
  articleCard: '.articles-grid .article-card',
  featuredArticle: '.articles-grid .article-card-featured',
  articleRowCard: '.articles-grid .article-card.article-row',
  articleTitleLink: '.articles-grid .article-card h3 a[href]',
  gallerySection: '#gallery',
  galleryGrid: '.gallery-grid',
  galleryItem: '.gallery-grid .gallery-item',
  galleryFeatured: '.gallery-grid .gallery-item.featured',
  galleryFancyboxItem: '.gallery-grid .gallery-item[data-fancybox="gallery"]',
  galleryLightbox: '.fancybox__container',
  galleryLightboxClose: '.fancybox__container button[data-fancybox-close]',
  galleryAssetScript: 'script[src*="fancybox.umd"]',
  galleryAssetStyle: 'link[href*="fancybox"]',
  introSection: '#intro',

  // Listings (blog.php/category.php/tag.php/archive.php)
  listing: '.archive-list',
  listingCard: '.archive-list .post-card',
  paginationNav: '.pagination-wrap',
  pagination: '.pagination-wrap .pagination',
  pageItemLink: '.pagination-wrap .pagination li.page-item > a.page-link',
  activePageLink: '.pagination-wrap .pagination li.page-item > a.page-link.active',

  // Single post (single.php)
  postDetail: 'article.post-detail',
  postTitle: '.post-detail h1.font-headline-lg',
  postContent: '.post-detail-content',
  postNav: '.posts-nav',
  prevPostLink: '.posts-nav a.prev-post',
  nextPostLink: '.posts-nav a.next-post',

  // Comments (single.php + partials/comments.php + load-comment.js)
  commentsSection: '#comments-section',
  commentsList: '#comments[data-post-id]',
  loadMoreButton: '#load-more',
  commentForm: '#commentForm',
  commentTextarea: '#comment[maxlength="320"]',
  commentName: '#name',
  commentEmail: '#email',
  commentCsrf: '#commentForm input[name="csrf"]',

  // Protected post (single.php + unlock-post.js)
  passwordBoxPrefix: '[id^="password-protected-"]',
  unlockForm: 'form.unlock-post-form[data-post-id]',
  unlockInput: 'input.post-password-input',
  unlockButton: 'button.unlock-post-btn',
  unlockError: '.unlock-post-error',
  unlockedContentPrefix: '[id^="unlocked-content-"]',

  // Search (sidebar.php / search.php)
  sidebarSearchWidgetTitle: '.widget:first-of-type .widget-title',
  sidebarSearchForm: '#ajax-search-form',
  sidebarSearchInput: '#search-keyword',
  searchVerify: '#ajax-search-form input[name="search_verify"]',
  searchResultCard: '.archive-list .post-card',
  searchResultLink: '.archive-list .post-card h2 a[href]',
  searchNoResultsCard: '#search-results-page .glass-card',

  // Cookie consent (cookie-consent.php + main.js)
  cookieBanner: '#cookieBanner',
  cookieAccept: '#btnAcceptCookies',
  cookieReject: '#btnRejectCookies',
  cookieLearnMore: '#cookieBanner a.btn-secondary',

  // i18n switcher (header.php)
  languageMenu: '#languageMenu',
  langDropdownItem: '.language-switcher .dropdown-menu .dropdown-item',
  langNative: '.language-switcher .dropdown-menu .lang-native',
  langText: '#languageMenu .lang-text',

  // Mobile navigation drawer (header.php + main.js)
  menuToggle: '#menuToggle',
  navDrawer: '#navDrawer',
  navDrawerClose: '#navDrawerClose',
  drawerOverlay: '#drawerOverlay',
  drawerHomeLink: '.nav-drawer-links > .nav-drawer-link:first-of-type',
  drawerLangLabel: '#navDrawerLangLabel',
  drawerLangList: '.nav-drawer-lang-list',
  drawerLangItems: '.nav-drawer-lang-list .nav-drawer-link',
  drawer: '.nav-drawer',

  // Privacy (privacy.php)
  privacyBody: '.privacy-body',
  privacyHeader: '.privacy-header',

  // 404 (404.php)
  notFoundContainer: '.error-404-container',
  notFoundCode: '.error-404-code',
  backHomeButton: '.error-404-container a.btn-primary',

  // Security surfaces
  titleImgXss: '.post-detail h1 img, .archive-list .post-card h2 img, .articles-grid h3 img',
};