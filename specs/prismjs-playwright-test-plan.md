# Prism.js Syntax Highlighting — Playwright Test Plan

## 0. Status

- [x] Phase 1 — Shared fixtures (`e2e/prismjs-fixtures.ts`)
- [x] Phase 2 — Suite A: asset loading (`e2e/prismjs-config.spec.ts`)
- [x] Phase 3 — Suite B: core highlighting (`e2e/prismjs-highlighting.spec.ts`)
- [x] Phase 4 — Suite C: plugins (`e2e/prismjs-plugins.spec.ts`)
- [x] Phase 5 — Suite D: inline code (`e2e/prismjs-inline-code.spec.ts`)
- [x] Phase 6 — Suite E: accessibility (`e2e/prismjs-accessibility.spec.ts`)
- [x] Phase 7 — Suite F: RTL (`e2e/prismjs-rtl.spec.ts`)
- [x] Phase 8 — Suite G: CSP (`e2e/prismjs-csp.spec.ts`)
- [x] Phase 9 — Suite H: console errors (`e2e/prismjs-console.spec.ts`)
- [x] Phase 10 — Suite I: normalize whitespace (`e2e/prismjs-normalize-whitespace.spec.ts`)
- [x] Phase 11 — Suite J: double initialization (`e2e/prismjs-double-init.spec.ts`)
- [x] Phase 12 — Full-matrix run (chromium + firefox + webkit) green

> Progress rule: each phase is implemented, run green, its box checked, and
> committed separately. No phase is marked complete without a passing run.

## 1. Overview

End-to-end verification of the Prism.js syntax highlighting implementation on
the `blog` theme. Tests run against the real vendored bundle with real
header/footer asset loading, and assert the factual runtime behavior observed
in the browser — not idealized Prism documentation.

### Application under test

| Property | Value (verified) |
|----------|------------------|
| Application | Scriptlog (PHP CMS) |
| Theme | `public/themes/blog/` |
| Prism.js version | 1.30.0 (banner comment in `prism.js`) |
| Prism theme | Default (light, `pre` background `#f5f2f0`) |
| Prism languages | markup, css, clike, javascript, apacheconf, bash, c, csharp, cpp, css-extras, diff, git, go, java, javadoc, javadoclike, jsdoc, js-extras, json, markdown, markup-templating, php, phpdoc, php-extras, python, regex, ruby, scala, sql, typescript, yaml (from the bundle download URL in the file banner) |
| Prism plugins | line-numbers, show-language, toolbar, copy-to-clipboard (banner URL) + NormalizeWhitespace (bundled in core, verified present in `prism.js`) |
| Fixture post | ID `99901`, slug `prismjs-fixture-post`, served at `/?p=99901` in the `blogware_e2e` database |
| Post body selector | `.post-single .post-body` (see `e2e/blog-selectors.ts`) |

### Test environment (verified — no config changes required)

| Resource | Value |
|----------|-------|
| Playwright config | `playwright.config.js` (already correct — do NOT change) |
| Base URL | `http://127.0.0.1:8099` (`BLOG_BASE_URL`) |
| Dev server | `sudo -n -u www-data /usr/bin/php -d variables_order=EGPCS -d display_errors=0 -S 127.0.0.1:8099 -t . e2e/router.php` (started automatically by the `webServer` block; DB forced to `blogware_e2e` by `e2e/router.php`) |
| Workers | `1` (serialized — shared dev server + rate-limiter state) |
| Browsers | chromium, firefox, webkit |

## 2. Evaluation of `plan/SYNTAX_HIGHLIGHTING_PLAN.md` (factual corrections)

The implementation plan is accurate on assets and bundle choice, but a test
plan derived from it verbatim would assert things that are factually false in
the browser. The corrections below were verified empirically against the live
bundle and are normative for every suite:

| # | Correction | Evidence |
|---|-----------|----------|
| E1 | The copy-to-clipboard button has **no** `aria-label`. It is `<button type="button" class="copy-to-clipboard-button"><span>Copy</span></button>`; on success the span reads `Copied!` and the button gains `data-copy-state="copy-success"`. Tests assert `type`, text transition, and state — and document the missing label as a known gap. | Live DOM probe |
| E2 | Line numbers render **only** on `<pre>` elements that carry the `line-numbers` class. A bare `<pre><code class="language-x">` gets a toolbar but **no** `.line-numbers-rows`. Fixture pres that exercise line numbers must include the class; one bare pre must assert the negative. | Live DOM probe (`lineRows: 0` without class, `1` with class) |
| E3 | `NormalizeWhitespace` **is** bundled (code present in `prism.js`) even though it is absent from the download-URL plugin list. Normalize tests are valid: 4-space common indent on the first line is stripped, inner relative indent preserved. | `grep NormalizeWhitespace prism.js`; probe (`"    function test() {"` → `"function test() {"`) |
| E4 | Language labels come from Prism's internal map, verified per language: `php`→`PHP`, `javascript`/`js`→`JavaScript`, `html`→`HTML`, `markup`→`Markup`, `css`→`CSS`, `bash`→`Bash`, `python`→`Python`, `sql`→`SQL`, `json`→`JSON`, `yaml`→`YAML`, `markdown`→`Markdown`, `diff`→`Diff`, `c`→`C`, `cpp`→`C++`, `csharp`→`C#`, `go`→`Go`, `java`→`Java`, `ruby`→`Ruby`, `typescript`→`TypeScript`, `apacheconf`→`Apache Configuration`, `regex`→`Regex`. Tests assert these exact strings. | Live DOM probe |
| E5 | Toolbar visibility is `opacity: 0` at rest and `opacity: 1` on `:hover` **and** `:focus-within` of `div.code-toolbar` (from `prism.css`). Tests assert computed style, not inline style. | `prism.css`; probe |
| E6 | The Prism Default theme does **not** meet WCAG 2.1 AA (4.5:1) for most token colors on `#f5f2f0`, and the override inline-code color `#e83e8c` on `#f4f4f4` (~3.5:1) does not either. Contrast tests compute real ratios and assert the factual outcome (pass for `.token.keyword` `#07a`; documented below-threshold for the rest) instead of asserting a universal pass. | WCAG formula on computed styles |
| E7 | No `playwright.config.js` changes are needed. The `baseURL`, `webServer` (`e2e/router.php`, port 8099, `blogware_e2e` DB), `workers: 1`, and `testIgnore` for tastybites/restoblog are already correct. The old draft's `blogware.site:80` / `public/` docroot setup is obsolete. | `playwright.config.js`, `e2e/router.php` |
| E8 | No admin-UI seeding is used. The `blogware_e2e` post `99901` (`prismjs-fixture-post`) carries PHP/JS/CSS/SQL blocks (two with `line-numbers`), plus plain and `language-php` inline code, through the real save→sanitize→render pipeline (`class` is not in the `sanitize_post_content` deny list). Parameterized language coverage uses injection of entity-escaped fixture HTML into the live `/?p=99901` page followed by the real `Prism.highlightAll()`. | Served HTML of `/?p=99901` |

## 3. UI/UX strategy (applies to every suite)

The highlighting layer must stay consistent, responsive, accessible,
readable, lightweight, interoperable, fast, mobile-first, premium-modern, and
elegant from 320px phones to large monitors:

- **Responsive / mobile-first**: code blocks never widen the page. `pre` keeps `overflow: auto` + `max-height: 500px` from `prism-override.min.css`; long lines scroll *inside* the block. Suites B and D assert zero page-level horizontal overflow at 320/768/1024/1440px with code blocks present, and that `pre` scrolls internally.
- **Readable**: `0.9rem` code type, `border-radius: 0.3rem`, Default light theme on the light blog background; inline code keeps the `#f4f4f4` / `#e83e8c` pill from the override.
- **Accessible**: toolbar reachable by keyboard (`:focus-within` reveals it), global `:focus-visible` outline applies to the copy button, `.line-numbers-rows` carries `aria-hidden="true"`. Known gaps (no `aria-label` on the copy button; sub-AA token contrast) are asserted factually and documented, not hidden.
- **Lightweight / fast**: single self-hosted bundle (`~58KB`), `defer`red JS, non-blocking CSS (`media="print"` + `<noscript>` fallback), no CDN hosts (CSP `'self'`).
- **Interoperable**: same-origin assets work identically across chromium/firefox/webkit (full matrix in Phase 12); clipboard tests degrade gracefully where the Clipboard API is unavailable.

## 4. Shared fixtures (`e2e/prismjs-fixtures.ts`)

Single source of truth imported by every suite — no duplication:

- `FIXTURE_POST_ID = 99901`, `FIXTURE_POST_URL = '/?p=99901'`.
- `PRISM_JS_SRI`, `PRISM_CSS_SRI`, `OVERRIDE_CSS_SRI` (expected `sha384-…` values, verified via `openssl`; override SRI covers the `:focus-visible` copy-button rule).
- `LANGUAGE_CASES`: representative entity-escaped samples + exact expected toolbar labels (see E4) for php, javascript, js, html, css, bash, python, sql, json, yaml, markdown, diff.
- `INJECT_HTML_FULL`: multi-language fixture (all with `line-numbers` except one bare pre for the negative test) + plain and `language-php` inline code + indented normalize block.
- Helpers: `gotoFixturePost(page)`, `injectFixture(page, html)`, `waitForPrism(page)`, `hoverAndClickCopy(page, index)` (centers the block clear of the fixed navbar, hovers the `pre`, waits for toolbar `opacity: 1`, then a real non-forced click), `grantClipboard(context)` (best-effort; false where unsupported), `luminance(rgb)`, `contrastRatio(fg, bg)`, `parseRgb(cssColor)`, `isReportOnlyCspNoise(text)`.

## 5. Test suites

### Suite A — Asset loading (`e2e/prismjs-config.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| A1 | `prism.css` link in `<head>` | `href` contains `vendor/prism/prism.css` with `integrity` + `crossorigin="anonymous"`; live `media` is `all` after the header activation script flips the served `media="print"` (non-blocking handoff, asserted in raw HTML) |
| A2 | `prism.css` noscript fallback | served HTML contains the `<noscript><link …prism.css>` markup with matching SRI (live DOM keeps noscript inert when JS runs) |
| A3 | `prism-override.min.css` link | `href` contains `prism-override.min.css` with `integrity` + `crossorigin` |
| A4 | `prism.js` script | `src` contains `vendor/prism/prism.js`, boolean `defer`, `integrity` + `crossorigin="anonymous"` |
| A5 | SRI hashes match disk files | SHA-384 of the served bytes equals the `integrity` attribute for all three assets |
| A6 | No Prism asset 404s | zero `>=400` responses for `assets/` URLs across home + fixture post |

### Suite B — Core highlighting (`e2e/prismjs-highlighting.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| B1 | Server-rendered fixture keeps classes | `/?p=99901` HTML contains `code.language-php/js/css/sql` and `pre.line-numbers` (sanitizer preserves `class`) |
| B2 | `Prism` global is active | `typeof Prism !== 'undefined'` with `Prism.languages` defined |
| B3–B8 | PHP / JS / CSS / SQL / Bash / Python samples tokenize | each `code.language-*` contains `≥1 span.token` after highlight (per `LANGUAGE_CASES`) |
| B9 | JSON / YAML / Markdown / Diff samples tokenize | same token assertion for the remaining cases |
| B10 | `pre` background | computed `background-color` is `rgb(245, 242, 240)` (`#f5f2f0`) |
| B11 | Responsive: no page overflow | `scrollWidth - innerWidth <= 0` at 320/768/1024/1440px with the full fixture injected; `pre` has `overflow-x: auto` computed internally |

### Suite C — Plugins (`e2e/prismjs-plugins.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| C1 | Line numbers on `line-numbers` pres | `pre.line-numbers` contains `.line-numbers-rows` with `>0 span` children |
| C2 | Line count matches | `.line-numbers-rows > span` count equals the code block's line count |
| C3 | Rows hidden from AT | `.line-numbers-rows` has `aria-hidden="true"` |
| C4 | Bare pre has no line numbers | `pre.language-css:not(.line-numbers)` has no `.line-numbers-rows` |
| C5 | Copy button exists per block | each `div.code-toolbar` has exactly one `button.copy-to-clipboard-button[type="button"]` with initial text `Copy` |
| C6 | Copy writes code text | after click, `data-copy-state="copy-success"`, span reads `Copied!`, clipboard equals the block's `textContent` (best-effort permission grant; on firefox/webkit the grant is unsupported so only button state is asserted) |
| C7 | Language labels exact | toolbar `.toolbar-item span` text equals the E4 mapping for every injected language |
| C8 | Toolbar hidden at rest | computed `opacity` of `.toolbar` is `0` |
| C9 | Toolbar reveals on hover and on focus-within | computed `opacity` becomes `1` after hover; and after `Tab` focuses the copy button |

### Suite D — Inline code (`e2e/prismjs-inline-code.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| D1 | Plain inline code untouched | `:not(pre) > code` without language class contains no `span.token`; computed style is `background-color rgb(244, 244, 244)`, `color rgb(232, 62, 140)` |
| D2 | `language-php` inline highlights w/o chrome | inline `code.language-php` contains `span.token` but is not inside `div.code-toolbar` and has no `.line-numbers-rows` sibling |
| D3 | No page overflow from inline code | 320px viewport: page does not overflow with inline samples present |

### Suite E — Accessibility (`e2e/prismjs-accessibility.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| E1 | Copy button contract (factual) | every copy button is `type="button"` with text `Copy`; **documents** that no `aria-label` is set (known gap, see E6 of §2) |
| E2 | Line numbers hidden from AT | all `.line-numbers-rows` have `aria-hidden="true"` |
| E3 | `pre` has no bogus role | `pre[class*="language-"]` elements carry no `role` attribute (improvement opportunity: `role="region"` + label — documented, not asserted) |
| E4 | Token contrast computed | `.token.keyword` (`#07a`) ratio `>= 4.5:1` (pass); `.token.comment` (`#708090`) and `.token.punctuation` (`#999`) ratios `< 4.5:1` (documented known limitation of the Default theme) |
| E5 | Inline-code contrast computed | `#e83e8c` on `#f4f4f4` ratio computed and asserted `< 4.5:1` (documented known gap) |
| E6 | Focus indicator visible | Tabbing to the copy button yields a non-`none` computed `outline-style` (via the `button:focus-visible` rule in `prism-override.css`, which restores what the theme `button{outline:none!important}` reset removes) |
| E7 | Keyboard reachability | `Tab` from the page reaches a `.copy-to-clipboard-button` (`document.activeElement` matches) |

### Suite F — RTL (`e2e/prismjs-rtl.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| F1 | RTL switch works | after `/?switch-lang=ar`, `<html dir="rtl">` and `rtl.min.css` is loaded while `prism.css`/`prism.js` remain loaded |
| F2 | Highlighting works in RTL | injected fixture blocks contain `span.token` under the `ar` locale |
| F3 | Line numbers intact in RTL | `.line-numbers-rows` present, `aria-hidden="true"`, not clipped (`getBoundingClientRect` inside viewport) |
| F4 | Copy works in RTL | click copies block text (same contract as C6) |
| F5 | Labels render in RTL | toolbar language spans show the exact E4 labels |

### Suite G — CSP (`e2e/prismjs-csp.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| G1 | Policy served | response `content-security-policy` contains `script-src 'self'` with a `nonce-` source and no `unsafe-inline`; `style-src 'self'` present |
| G2 | Same-origin assets | Prism `script[src]`/`link[href]` URLs are same-origin (no CDN/external hosts) |
| G3 | No violations while highlighting | zero `enforce`-disposition `SecurityPolicyViolation` events and zero enforcing console errors during load + highlight + copy (Report-Only style-src notices about pre-existing inline styles are expected and excluded) |
| G4 | Highlighting unaffected by CSP | tokens present after load with CSP enforced |

### Suite H — Console (`e2e/prismjs-console.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| H1 | Clean load | zero `console.error` + zero `pageerror` from goto through highlight completion |
| H2 | Clean copy | zero errors while clicking every copy button |
| H3 | No Prism warnings | console contains no `Unknown language` / `not found` Prism warnings |

### Suite I — Normalize whitespace (`e2e/prismjs-normalize-whitespace.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| I1 | Common indent stripped | block starting with 4-space indent renders first line with zero indent while inner relative indent is preserved |
| I2 | Normalized block highlights cleanly | normalized block contains `span.token`, is visible, and its `pre` scrolls internally without page overflow |

### Suite J — Double initialization (`e2e/prismjs-double-init.spec.ts`)

| # | Test | Expected outcome |
|---|------|------------------|
| J1 | Single Prism script tag | exactly one `script[src*="vendor/prism/prism.js"]` in the document |
| J2 | Re-highlight is idempotent | token count stable and block markup byte-identical across a second `Prism.highlightAll()` (nested `span.token` pairs are legitimate grammar output, not duplication) |
| J3 | Single toolbar per block | each `div.code-toolbar` contains exactly one `.toolbar` after re-highlight |

## 6. Running instructions

```bash
cd /var/www/blogware/public_html
npm install
npx playwright test e2e/prismjs-config.spec.ts --project=chromium
npx playwright test e2e/prismjs-*.spec.ts --project=chromium
npx playwright test e2e/prismjs-*.spec.ts
npx playwright show-report
```

- The dev server (port 8099, `blogware_e2e` DB) is started automatically by `playwright.config.js`; for manual debugging: `sudo -n -u www-data /usr/bin/php -d variables_order=EGPCS -d display_errors=0 -S 127.0.0.1:8099 -t . e2e/router.php`.
- Fixture post `99901` lives in the `blogware_e2e` database only (scratch e2e DB — never production). If missing, re-insert from `e2e/prismjs-fixtures.ts` (`FIXTURE_SQL` reference) — suites that depend on it fail fast with the post-ID in the message.
- Clipboard tests use a best-effort grant (`grantClipboard()` helper): chromium compares real clipboard text; firefox/webkit (no `clipboard-read` permission) assert button state (`Copied!` / `data-copy-state`) and skip the text comparison. Report-Only CSP style notices (pre-existing theme inline styles) are excluded from "no console errors" assertions on every browser.

## 7. Execution order

```
1. e2e/prismjs-fixtures.ts (shared — no tests)
2. e2e/prismjs-config.spec.ts (no fixture-post dependency)
3. e2e/prismjs-highlighting.spec.ts
4. e2e/prismjs-plugins.spec.ts
5. e2e/prismjs-inline-code.spec.ts
6. e2e/prismjs-accessibility.spec.ts
7. e2e/prismjs-rtl.spec.ts
8. e2e/prismjs-csp.spec.ts
9. e2e/prismjs-console.spec.ts
10. e2e/prismjs-normalize-whitespace.spec.ts
11. e2e/prismjs-double-init.spec.ts
```

Every spec is independently executable; none depends on another spec's side
effects. Fixture HTML is injected per test and the server-rendered post
`99901` is read-only.

## 8. Verification checklist mapping (`plan/SYNTAX_HIGHLIGHTING_PLAN.md` §6)

| Checklist item | Suite(s) | Status |
|----------------|----------|--------|
| `prism.js` exists (bundled per §3) | A4, A5 | - [x] |
| `prism.css` exists (Default theme) | A1, A2, A5 | - [x] |
| `header.php` loads `prism.css` non-blocking + SRI | A1–A3, A5 | - [x] |
| `footer.php` loads `prism.js` deferred + SRI | A4, A5 | - [x] |
| `prism-override.min.css` exists and is loaded | A3, A5 | - [x] |
| Code blocks highlight | B1–B9 | - [x] |
| Copy button appears | C5, C6 | - [x] |
| Line numbers display | C1, C2 (only on `line-numbers` pres) | - [x] |
| Language label shows | C7 | - [x] |
| Inline `<code>` styling unaffected | D1–D3 | - [x] |
| Copy button accessible name (known gap: none) | E1 (factual) | - [x] |
| Line numbers `aria-hidden` | C3, E2 | - [x] |
| Contrast WCAG 2.1 AA (known theme gaps) | E4, E5 (factual) | - [x] |
| RTL renders correctly | F1–F5 | - [x] |
| No JS console errors | H1–H3 | - [x] |
| `php -l` on touched PHP files | Phase 1 (fixtures touch no PHP) | - [x] |
| `php tmp/minify.php` succeeds | N/A (no asset changes) | - [x] |
| CSP `'self'`, no CDN | G1–G4 | - [x] |
| Normalize Whitespace works | I1, I2 | - [x] |
| Single initialization | J1–J3 | - [x] |
| Responsive 320–1440px, no page overflow | B11, D3, I2 | - [x] |

*End of test plan*

---

## 9. Final report (2026-10-16)

- Full matrix: **151 passed, 2 skipped (by design), 0 failed** across
  chromium + firefox + webkit (10 spec files, shared `e2e/prismjs-fixtures.ts`),
  re-verified after hardening (real hover/click path with viewport centering;
  best-effort clipboard grants; Report-Only noise exclusion on all browsers).
- The 2 skips are the clipboard-*text* comparisons on browsers without the
  `clipboard-read` grant (button state is still asserted).
- Pre-existing, out-of-scope failure (verified untouched by this change set —
  no commits under `lib/`, `index.php`, `api/`, or
  `e2e/security-remediation.spec.ts`): `security-remediation › enforced CSP
  header carries script-src-attr none` expects
  `script-src 'self' 'unsafe-inline'`, while the implementation serves
  nonce-only `script-src` by design (see `build_csp_headers()` docblock).
  Left alone per scope rules.
