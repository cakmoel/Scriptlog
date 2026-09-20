// blog-selectors.ts
// Single source of truth for blog (default) theme DOM selectors used by the
// top-level e2e suite. Values verified against public/themes/blog/ templates
// (header.php, home.php, single.php, search.php, cookie-consent.php,
// privacy.php). Mirrors e2e/tastybites/tb-selectors.ts conventions: `as const`
// keeps every value a string literal so misspelled selectors fail the type
// gate instead of silently matching nothing.
export const BLOG = {
  // Shell (header.php / footer.php)
  html: 'html',
  banner: 'header[role="banner"]',
  main: 'main#main-content',
  footer: 'footer.main-footer',
  skipLink: '.skip-link',
  navbarMenu: '#navbar-menu',
  navToggle: 'button#al',
  languageMenu: '#languageMenu',

  // Home (home.php)
  latestPosts: '.latest-posts',
  latestCard: '.latest-posts .post',
  latestCardTitle: '.latest-posts .post h3.h4',
  latestCardTitleLink: '.latest-posts .post a h3.h4',

  // Listings (blog.php / category.php / tag.php / archive.php)
  postsListing: '.posts-listing',
  listingCard: '.posts-listing .post',
  listingCardTitle: '.posts-listing .post h3.h4',
  listingCardTitleLink: '.posts-listing .post a h3.h4',
  listingCardThumb: '.posts-listing .post .post-thumbnail',
  listingCardMeta: '.posts-listing .post .post-meta',
  cardThumbImg: '.post .post-thumbnail img',

  // Shared card partial (partials/card.php)
  card: '.post',
  cardTitle: '.post h3.h4',
  cardThumb: '.post .post-thumbnail',
  cardMeta: '.post .post-meta',

  // Single post (single.php)
  postSingle: '.post-single',
  postSingleTitle: '.post-single h1',
  postBody: '.post-single .post-body',
  postTags: '.post-tags a',
  postsNav: '.posts-nav',
  postsNavLink: '.posts-nav a',

  // Protected post unlock (single.php)
  unlockForm: '.unlock-post-form',
  unlockInput: '.unlock-post-form .post-password-input',
  unlockButton: '.unlock-post-btn',

  // Search (search.php / sidebar.php)
  searchResultsPage: '#search-results-page',
  searchHeading: '#search-heading',
  sidebarSearchForm: '#ajax-search-form, form[role="search"], form.search',

  // Cookie consent (cookie-consent.php / footer.php)
  cookieBanner: '.cookie-consent-banner',
  cookieBannerById: '#cookie-consent-banner',
  cookieAccept: '.cookie-btn-accept',
  cookieReject: '.cookie-btn-reject',
  cookieLearnMore: '.cookie-btn-learn-more',

  // Privacy (privacy.php)
  privacyCard: '.privacy-card',
  privacyHeaderTitle: '.privacy-header h1',
  privacyBody: '.privacy-body',
  privacyBackBtn: '.privacy-back-btn',

  // 404 (404.php)
  notFoundHeading: 'h1.sr-only',
  backHomeButton: 'a.btn.btn-outline-primary.btn-lg',

  // Sidebar (sidebar.php)
  sidebar: '.sidebar, aside, .widget',
} as const;

/** Shape of a single valid-route entry (baseline smoke + partials parity). */
export type BlogRoute = {
  url: string;
  selector?: string;
  name?: string;
};

/** Base URL for the e2e dev server (overridable via env, mirrors JS specs). */
export const BLOG_BASE_URL: string =
  process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8099';
