// tb-selectors.ts
// Single source of truth for TastyBites theme DOM selectors used by the e2e
// suite. Values verified against the rendered markup in August 2026 (Step 0
// probe of plan/TASTYBITES_E2E_TEST_PLAN.md). Dynamic ids use attribute
// prefixes; listing pagination is scoped to avoid the nested-UL hazard.
// `as const` keeps every value a string literal so misspelled selectors fail
// the type gate instead of silently matching nothing.
export const TB = {
  // Shell (header.php / footer.php)
  main: 'main#main-content',
  footer: 'footer.main-footer',
  navbar: 'nav.navbar',

  // Home (home.php)
  hero: 'section.hero',
  heroSearchForm: '#hero-search-form',
  heroSearchInput: 'input#hero-search-input[name="q"]',
  heroSearchResults: '#hero-search-results',
  heroSearchItem: '.hero-search-item',
  latestPosts: 'section.latest-posts',
  homeCard: '.latest-posts .card',
  homeCardTitleLink: '.latest-posts .card-title a[href]',

  // Listings (blog.php/category.php/tag.php/archive.php)
  archiveHeader: '.archive-header h1',
  archiveDescription: '.archive-description',
  postsListing: '.posts-listing',
  listingCard: '.posts-listing .post.col-xl-6',
  pageItemLink: '.posts-listing li.page-item > a.page-link',
  activePageLink: '.posts-listing li.page-item > a.page-link.active',
  archiveEmptyState: '.widget.text-center',

  // Single post (single.php)
  articleRole: 'article[role="article"]',
  postSingle: '.post-single',
  postTitle: '.post-single .post-title, article[role="article"] h1',
  postsNav: 'nav.posts-nav',
  prevPostLink: 'nav.posts-nav a.prev-post',
  nextPostLink: 'nav.posts-nav a.next-post',
  commentsSection: '#comments-section',
  loadMoreButton: '#load-more',

  // Comment form (comments.php / comment-submission.js)
  commentForm: '#commentForm',
  commentTextarea: '#comment[maxlength="320"]',
  commentName: '#name',
  commentEmail: '#email',
  commentSuccess: '#success_message',

  // Protected post (single.php + unlock flow)
  passwordBoxPrefix: '[id^="password-protected-"]',
  unlockForm: 'form.unlock-post-form[data-post-id]',
  unlockInput: 'input.post-password-input',
  unlockButton: 'button.unlock-post-btn',
  unlockError: '.unlock-post-error',
  unlockedContentPrefix: '[id^="unlocked-content-"]',

  // Search (sidebar.php / search.php / front.js)
  sidebarSearchInput: '#search-keyword',
  sidebarSearchWidgetTitle: '.widget.search h3',
  sidebarSearchResults: '#search-results',
  sidebarResultItem: '#search-results .search-result-item',
  searchVerify: '#search-verify',
  searchPageResults: '.search-results-list',
  searchPageHeading: '.archive-header h1',
  searchPageAlert: 'main .alert-info',

  // Cookie consent (cookie-consent.js)
  cookieBanner: '#cookie-consent-banner',
  cookieAccept: '.cookie-btn-accept',
  cookieReject: '.cookie-btn-reject',
  cookieLearnMore: '.cookie-btn-learn-more',

  // i18n switcher (header.php)
  languageMenu: '#languageMenu',
  langNativeItem: '.dropdown-menu .lang-native',

  // 404 (404.php)
  notFoundDisplay: 'span.display-1',
  notFoundHeading: 'h1.sr-only',
  backHomeButton: '.btn.btn-outline-primary.btn-lg',

  // Security surfaces
  titleImgXss: '.post-single h1 img, .card-title img',

  // Page template (page.php)
  pageContent: '.post-body',
  pageAuthor: '.post-footer .author',
  pageDate: '.post-footer .date',

  // Download page (download.php)
  downloadCard: '.card',
  downloadCardHeader: '.card-header',
  downloadFileInfo: '.file-info',
  downloadButton: '.download-action .btn-primary',
  copyLinkBtn: '#copy-link-btn',
  copyStatus: '#copy-status',
  downloadShareUrl: '#download-share-url',
  downloadError: '.alert-danger',

  // Footer (footer.php)
  copyright: '.copyright',
  socialLinks: '.social-links',

  // CSS loading pattern
  asyncStylesheet: 'link[onload]',
} as const;

/** Shape of a single VALID_ROUTES entry in tb-baseline.spec.ts. */
export type TastybitesRoute = {
  url: string;
  selector: string;
  name: string;
};