# Prism.js Syntax Highlighting — Playwright Test Plan

## 1. Overview

This document defines a comprehensive Playwright end-to-end (E2E) test plan for verifying the Prism.js syntax highlighting implementation on the blog theme of Scriptlog, a PHP-based CMS. The tests cover code block rendering, plugin behavior (Line Numbers, Show Language, Copy to Clipboard, Toolbar, Normalize Whitespace), accessibility compliance (WCAG 2.1 AA), Content Security Policy (CSP) compatibility, RTL page rendering, and console error detection.

### Application Under Test

| Property | Value |
|----------|-------|
| **Application** | Scriptlog 1.5.1 (PHP CMS) |
| **Theme** | `public/themes/blog/` |
| **Prism.js version** | 1.30.0 |
| **Prism theme** | Default (light) |
| **Prism plugins** | Line Numbers, Show Language, Copy to Clipboard, Toolbar, Normalize Whitespace |
| **Test account** | `administrator` / `4dMin(*)^` |
| **App URL** | `http://blogware.site` (resolves to 127.0.0.1 via `/etc/hosts`) |
| **PHP version** | 8.5.8 |

### Test Environment

| Resource | Location |
|----------|----------|
| Playwright config | `playwright.config.js` |
| Test directory | `./e2e/` |
| Theme directory | `public/themes/blog/` |
| Prism vendor files | `public/themes/blog/assets/vendor/prism/prism.js`, `prism.css` |
| Prism override CSS | `public/themes/blog/assets/css/prism-override.min.css` |
| Seed data file | `e2e/seed.spec.ts` |
| Test plan | `specs/PRISMJS_PLAYWRIGHT_TEST_PLAN.md` |

---

## 2. Environment Setup

### 2.1 Playwright Configuration (`playwright.config.js`)

The following changes are required to the existing `playwright.config.js`:

```javascript
// Required additions/modifications in playwright.config.js
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './e2e',
  fullyParallel: true,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: process.env.CI ? 1 : undefined,
  reporter: 'html',
  use: {
    // IMPORTANT: Set baseURL so all page.goto('') calls resolve to the app
    baseURL: 'http://blogware.site',
    trace: 'on-first-retry',
    // Optional: capture console output to detect JS errors
    // (Can also be done per-test via page.on('console'))
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
    {
      name: 'firefox',
      use: { ...devices['Desktop Firefox'] },
    },
    {
      name: 'webkit',
      use: { ...devices['Desktop Safari'] },
    },
  ],
  // PHP built-in dev server
  webServer: {
    command: 'php -S 0.0.0.0:80 -t public/',
    url: 'http://blogware.site',
    reuseExistingServer: !process.env.CI,
    timeout: 30000,
  },
});
```

> **Note:** The app needs `blogware.site` to resolve to `127.0.0.1`. If the `/etc/hosts` entry is missing, add:
> ```
> 127.0.0.1	blogware.site www.blogware.site
> ```

### 2.2 Starting the PHP Dev Server

The PHP built-in development server can be started manually for debugging:

```bash
# From project root
php -S 0.0.0.0:80 -t public/
```

Or let Playwright's `webServer` configuration handle it automatically.

### 2.3 Test Data: Seeding a Post with Code Blocks

A seeded blog post is **required** for all tests. The test plan includes a seed file (`e2e/seed.spec.ts`) that uses the admin API to create a post containing code blocks in all supported languages.

#### Seed Post Content

The seeded post must contain the following code block formats, matching the languages supported by the Prism.js bundle:

| # | Language | `class` attribute | Content |
|---|----------|-------------------|---------|
| 1 | PHP | `language-php` | `<?php echo "Hello World!"; ?>` |
| 2 | JavaScript | `language-js` | `const greet = (name) => console.log(\`Hello, ${name}!\`);` |
| 3 | HTML | `language-html` | `<div class="container"><p>Hello</p></div>` |
| 4 | Bash | `language-bash` | `npm run build && npm start` |
| 5 | CSS | `language-css` | `.container { display: flex; gap: 1rem; }` |
| 6 | Python | `language-python` | `def fibonacci(n): return n if n <= 1 else fibonacci(n-1) + fibonacci(n-2)` |
| 7 | SQL | `language-sql` | `SELECT * FROM users WHERE active = 1 ORDER BY created_at DESC;` |
| 8 | JSON | `language-json` | `{ "name": "Scriptlog", "version": "1.5.1" }` |
| 9 | YAML | `language-yaml` | `database: { host: localhost, port: 3306 }` |
| 10 | Markdown | `language-markdown` | `# Heading\n**bold** text and _italic_ text` |
| 11 | Diff | `language-diff` | `- old line\n+ new line` |
| 12 | Inline code | `<code>` (no `class`) | `` This is `inline code` in a paragraph. `` |
| 13 | Inline code with lang | `<code class="language-php">` | The `$variable = 'value';` is a PHP assignment |
| 14 | Multi-line (indented) | `language-php` with extra whitespace | (test Normalize Whitespace plugin) |

#### Seed Workflow

The seed file should:

1. Navigate to `/admin/login.php`
2. Log in with `administrator` / `4dMin(*)^`
3. Navigate to `index.php?load=posts&action=newPost`
4. Fill in the post title: "Prism.js Syntax Highlighting Test Post"
5. Switch to Summernote's `codeview` (HTML source mode) or use JavaScript to set the textarea content directly
6. Insert the HTML content containing all code blocks (see below)
7. Set post status to "publish"
8. Submit the form
9. Capture the resulting post ID and slug for use in subsequent tests
10. Store the post URL as a shared variable or environment variable

#### HTML Content for Seed Post

```html
<h2>PHP Example</h2>
<pre><code class="language-php">&lt;?php
echo "Hello World!";
$data = ['foo' =&gt; 'bar'];
foreach ($data as $key =&gt; $value) {
    echo $key . ': ' . $value;
}
?&gt;</code></pre>

<h2>JavaScript Example</h2>
<pre><code class="language-js">const greet = (name) =&gt; {
    console.log(`Hello, ${name}!`);
    return `Hello, ${name}!`;
};

document.addEventListener('DOMContentLoaded', () =&gt; {
    greet('World');
});</code></pre>

<h2>HTML Example</h2>
<pre><code class="language-html">&lt;!DOCTYPE html&gt;
&lt;html lang="en"&gt;
&lt;head&gt;
    &lt;meta charset="UTF-8"&gt;
    &lt;title&gt;Example&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
    &lt;div class="container"&gt;
        &lt;p&gt;Hello World&lt;/p&gt;
    &lt;/div&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>

<h2>Bash Example</h2>
<pre><code class="language-bash">#!/bin/bash
npm run build
npm start
echo "Deployment complete"</code></pre>

<h2>CSS Example</h2>
<pre><code class="language-css">.container {
    display: flex;
    justify-content: center;
    gap: 1rem;
    max-width: 1200px;
    margin: 0 auto;
}

@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }
}</code></pre>

<h2>Python Example</h2>
<pre><code class="language-python">def fibonacci(n):
    if n &lt;= 1:
        return n
    return fibonacci(n-1) + fibonacci(n-2)

# Generate first 10 Fibonacci numbers
result = [fibonacci(i) for i in range(10)]
print(result)</code></pre>

<h2>SQL Example</h2>
<pre><code class="language-sql">SELECT
    u.id,
    u.username,
    COUNT(o.id) as order_count
FROM users u
LEFT JOIN orders o ON u.id = o.user_id
WHERE u.active = 1
GROUP BY u.id, u.username
ORDER BY order_count DESC
LIMIT 10;</code></pre>

<h2>JSON Example</h2>
<pre><code class="language-json">{
    "name": "Scriptlog",
    "version": "1.5.1",
    "php_version": "8.5",
    "features": {
        "syntax_highlighting": true,
        "themes": ["blog", "tastybites", "valdur"]
    }
}</code></pre>

<h2>YAML Example</h2>
<pre><code class="language-yaml">database:
  host: localhost
  port: 3306
  name: blogwaredb
  user: blogwareuser

app:
  url: "https://blogware.site"
  debug: true</code></pre>

<h2>Markdown Example</h2>
<pre><code class="language-markdown"># Heading 1
## Heading 2

**Bold text** and *italic text*

- List item 1
- List item 2

1. Ordered item
2. Ordered item</code></pre>

<h2>Diff Example</h2>
<pre><code class="language-diff">- old_function_call($param1, $param2);
+ new_function_call($param1, $param2, $param3);
  unchanged_line_here();
- deprecated_method();
+ improved_method();</code></pre>

<h2>Inline Code Example</h2>
<p>This paragraph contains <code>inline code</code> that should not have line numbers or a language label. Also <code class="language-php">$variable = 'value';</code> is inline PHP code.</p>

<h2>Indented Code Example (Normalize Whitespace Test)</h2>
<pre><code class="language-php">    function test() {
        return "Too much indentation";
    }</code></pre>
```

---

## 3. Test File Structure

```
e2e/
├── seed.spec.ts                          # Test data seeder (run first)
├── prismjs-config.spec.ts                # Verify header/footer loading of Prism assets
├── prismjs-highlighting.spec.ts          # Core syntax highlighting tests
├── prismjs-plugins.spec.ts               # Plugin behavior tests (copy, line numbers, language label)
├── prismjs-inline-code.spec.ts           # Inline code unaffected tests
├── prismjs-accessibility.spec.ts         # WCAG / ARIA / contrast tests
├── prismjs-rtl.spec.ts                   # RTL page rendering tests
├── prismjs-csp.spec.ts                   # CSP compatibility tests
├── prismjs-console.spec.ts               # Console error detection
├── prismjs-normalize-whitespace.spec.ts  # Normalize Whitespace plugin tests
├── prismjs-double-init.spec.ts           # Double initialization detection
└── fixtures/
    └── code-blocks.html                  # Shared HTML fragment for code blocks
```

---

## 4. Page Objects (Recommended)

For maintainability, create a shared page object file at `e2e/page-objects/blog-post.page.ts`:

```typescript
// e2e/page-objects/blog-post.page.ts
import { Page, Locator } from '@playwright/test';

export class BlogPostPage {
  readonly page: Page;
  readonly postContent: Locator;
  readonly codeBlocks: Locator;
  readonly preBlocks: Locator;
  readonly inlineCode: Locator;

  constructor(page: Page) {
    this.page = page;
    this.postContent = page.locator('.post-body');
    this.codeBlocks = page.locator('code[class*="language-"]');
    this.preBlocks = page.locator('pre[class*="language-"]');
    this.inlineCode = page.locator(':not(pre) > code');
  }

  async gotoPost(slug: string, postId: number) {
    // Supports both permalink and query string URL formats
    await this.page.goto(`/post/${postId}/${slug}`);
    await this.page.waitForLoadState('networkidle');
  }

  async getCodeBlock(language: string): Promise<Locator> {
    return this.page.locator(`code.language-${language}`);
  }

  async getPreBlock(language: string): Promise<Locator> {
    return this.page.locator(`pre.language-${language}`);
  }

  async getLanguageLabel(language: string): Promise<Locator> {
    return this.page.locator(`pre.language-${language}`).locator('.toolbar-item span');
  }

  async getCopyButton(language: string): Promise<Locator> {
    return this.page.locator(`pre.language-${language}`).locator('.copy-to-clipboard-button');
  }

  async isPrismActive(): Promise<boolean> {
    return this.page.evaluate(() => {
      return typeof Prism !== 'undefined' && Prism.languages !== undefined;
    });
  }

  async getLineNumberCount(language: string): Promise<number> {
    return this.page.locator(`pre.language-${language}.line-numbers .line-numbers-rows > span`).count();
  }

  async getPrismInitCount(): Promise<number> {
    return this.page.evaluate(() => {
      // Check if Prism.highlightAll was called more than once
      return (window as any).__prismHighlightCount || 0;
    });
  }
}
```

Also create an admin login page object:

```typescript
// e2e/page-objects/admin-login.page.ts
import { Page } from '@playwright/test';

export class AdminLoginPage {
  readonly page: Page;

  constructor(page: Page) {
    this.page = page;
  }

  async goto() {
    await this.page.goto('/admin/login.php');
    await this.page.waitForLoadState('networkidle');
  }

  async login(username: string, password: string) {
    await this.goto();
    await this.page.fill('#inputLogin', username);
    await this.page.fill('#inputPassword', password);
    await this.page.click('input[type="submit"][value="Log In"]');
    await this.page.waitForURL(/index\.php\?load=dashboard/);
  }
}
```

---

## 5. Test Scenarios

### Suite A: Prism.js Asset Loading (`e2e/prismjs-config.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| A1 | Prism CSS is loaded in header | 1. Navigate to any blog page. 2. Check `<link>` elements for `prism.css`. | A `<link>` with `href` containing `prism.css` exists in `<head>`, with `media="print"`, `integrity`, and `crossorigin="anonymous"`. |
| A2 | Prism CSS noscript fallback exists | 1. Check `<noscript>` inside `<head>`. | A `<noscript>` tag contains a `<link>` to `prism.css` with the same integrity/crossorigin attributes. |
| A3 | Prism override CSS is loaded | 1. Check `<link>` elements for `prism-override.min.css`. | A `<link>` with `href` containing `prism-override.min.css` exists with integrity and crossorigin attributes. |
| A4 | Prism JS is loaded in footer | 1. Navigate to any blog page. 2. Check `<script>` elements in the document. | A `<script>` with `src` containing `prism.js`, `defer`, `integrity`, and `crossorigin="anonymous"` exists. |
| A5 | Prism JS is deferred | 1. Check the `defer` attribute on the Prism script tag. | The `<script>` element for `prism.js` has the `defer` boolean attribute. |
| A6 | Integrity hashes match actual files | 1. Compute SHA-384 hash of `prism.js` and `prism.css` via `openssl dgst -sha384 -binary`. 2. Compare with integrity attributes in HTML. | The `sha384-...` values in HTML match the computed hashes of the actual files on disk. |

### Suite B: Core Syntax Highlighting (`e2e/prismjs-highlighting.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| B1 | PHP code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-php` element. 3. Check for Prism-generated `<span>` tokens inside. | The `<code>` element contains `<span>` elements with `token` classes (e.g., `token keyword`, `token string`, `token function`). |
| B2 | JavaScript code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-js` element. 3. Check for `<span>` token elements. | Tokens like `token keyword` (`const`), `token function` (`greet`), `token string` (`Hello`) are present. |
| B3 | HTML code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-html` element. 3. Check for `<span>` token elements. | Tokens like `token tag` (`<div>`), `token attr-name` (`class`), `token attr-value` (`container`) are present. |
| B4 | Bash code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-bash` element. 3. Check for `<span>` token elements. | Tokens like `token function` (built-in commands) are present. |
| B5 | CSS code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-css` element. 3. Check for `<span>` token elements. | Tokens like `token selector` (`.container`), `token property` (`display`), `token punctuation` (`:`) are present. |
| B6 | Python code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-python` element. 3. Check for `<span>` token elements. | Tokens like `token keyword` (`def`), `token function` (`fibonacci`), `token number` are present. |
| B7 | SQL code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-sql` element. 3. Check for `<span>` token elements. | Tokens like `token keyword` (`SELECT`, `FROM`, `WHERE`) are present. |
| B8 | JSON code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-json` element. 3. Check for `<span>` token elements. | Tokens like `token property` (`"name"`), `token string` (`"Scriptlog"`), `token punctuation` are present. |
| B9 | YAML code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-yaml` element. 3. Check for `<span>` token elements. | YAML keys and values are tokenized with appropriate classes. |
| B10 | Markdown code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-markdown` element. 3. Check for `<span>` token elements. | Markdown headers, bold, italic text are tokenized. |
| B11 | Diff code block has syntax tokens | 1. Navigate to the test post. 2. Locate `code.language-diff` element. 3. Check for `<span>` token elements. | Diff tokens like `token deleted` (`-` lines), `token inserted` (`+` lines) are present. |
| B12 | All code blocks contain syntax tokens | 1. Count all `code[class*="language-"]` elements. 2. For each, verify they contain at least one `span.token` child. | Every code block in the post has been highlighted by Prism. |
| B13 | Code block background is applied | 1. Check the computed background color of a `<pre class="language-*">` element. | The pre element has background color `#f5f2f0` (Prism default theme) or the theme's override. |

### Suite C: Plugin Behavior (`e2e/prismjs-plugins.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| C1 | Line Numbers plugin adds line numbers | 1. Navigate to test post. 2. Check a `<pre class="language-php">` element. | The `<pre>` has class `line-numbers` and contains a `.line-numbers-rows` element with `<span>` children. |
| C2 | Line numbers count matches code lines | 1. Count the lines in a code block. 2. Count the `<span>` elements in `.line-numbers-rows`. | The number of line number spans equals the number of lines in the code block. |
| C3 | Line numbers have aria-hidden="true" | 1. Check `.line-numbers-rows` attribute. | The `.line-numbers-rows` element (or each `<span>` within) has `aria-hidden="true"`. |
| C4 | Copy to Clipboard button exists | 1. Navigate to test post. 2. Hover over a `<pre class="language-*">` block to make toolbar visible. 3. Check for a button with class `copy-to-clipboard-button`. | A `<button>` with class `copy-to-clipboard-button` exists inside `.toolbar` within each `div.code-toolbar`. |
| C5 | Copy button has aria-label="Copy code" | 1. Locate the `.copy-to-clipboard-button`. 2. Check its `aria-label` attribute. | The button has `aria-label="Copy code"`. |
| C6 | Copy button copies text to clipboard | 1. Click the `.copy-to-clipboard-button` on a code block. 2. Read the clipboard contents using `navigator.clipboard.readText()`. | The clipboard contains the source code text from the code block (without HTML markup). |
| C7 | Copy button label changes to "Copied!" | 1. Click the copy button. 2. Check the button text content. | After clicking, the button text temporarily reads "Copied!" (or similar) before reverting. |
| C8 | Show Language plugin displays language label | 1. Navigate to test post. 2. Check the `.toolbar` area of a highlighted `<pre>`. | A `<span>` element within `.toolbar-item` displays the language name (e.g., "PHP", "JavaScript", "HTML"). |
| C9 | Language label matches code language | 1. For each `<code class="language-php">`, check the corresponding toolbar span. | The displayed language label text matches the expected language name for the class. |
| C10 | Toolbar is hidden by default | 1. Navigate to test post. 2. Check the opacity of `.toolbar` when not hovering. | The `.toolbar` element has `opacity: 0` via CSS (check computed style, not inline). |
| C11 | Toolbar appears on hover | 1. Hover over a `<pre class="language-*">` block. 2. Check the opacity of `.toolbar`. | The `.toolbar` element has `opacity: 1` (computed style changes to visible). |
| C12 | Toolbar appears on focus-within | 1. Focus the copy button using keyboard (Tab). 2. Check the toolbar visibility. | The `.toolbar` element becomes visible (opacity 1) when the copy button receives focus. |

### Suite D: Inline Code (`e2e/prismjs-inline-code.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| D1 | Inline `<code>` without language class is unaffected | 1. Locate `:not(pre) > code:not([class*="language-"])`. 2. Check its styling. | The inline code does not have Prism syntax highlighting spans. Its styling comes from the theme's override CSS (`background: #f4f4f4`, `color: #e83e8c`). |
| D2 | Inline `<code>` with language class is highlighted | 1. Locate `:not(pre) > code.language-php`. 2. Check its content. | Inline code with a language class has Prism syntax tokens applied but does NOT have line numbers or a toolbar. |
| D3 | Inline code does not get line numbers | 1. Locate `:not(pre) > code[class*="language-"]`. 2. Check for `.line-numbers-rows` sibling. | Inline code blocks do NOT have `.line-numbers-rows` or `line-numbers` class on their parent. |
| D4 | Inline code does not get toolbar | 1. Locate `:not(pre) > code[class*="language-"]`. 2. Check for `.toolbar` parent. | Inline code blocks are NOT wrapped in `div.code-toolbar`. |

### Suite E: Accessibility & WCAG (`e2e/prismjs-accessibility.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| E1 | Copy button has aria-label | 1. Locate all `.copy-to-clipboard-button` elements. 2. Check each has `aria-label`. | Every copy-to-clipboard button has `aria-label="Copy code"`. |
| E2 | Line numbers are hidden from screen readers | 1. Locate `.line-numbers-rows` elements. 2. Check `aria-hidden` attribute. | All `.line-numbers-rows` have `aria-hidden="true"`. |
| E3 | Pre elements have role attribute | 1. Locate all `pre[class*="language-"]` elements. 2. Check for `role` attribute. | (Optional) `<pre>` elements should ideally have no role or `role="region"` with an `aria-label`. This is a potential improvement opportunity. |
| E4 | Color contrast ratio >= 4.5:1 for Prism tokens | 1. Extract all unique Prism token CSS classes (`.token.keyword`, `.token.string`, `.token.comment`, etc.) from the Prism CSS file. 2. For each token class, compute foreground vs background color contrast ratio using WCAG formula. | All token foreground colors against the `#f5f2f0` background meet WCAG 2.1 AA minimum ratio of 4.5:1 for normal text (or 3:1 for large text). |
| E5 | Color contrast ratio for override CSS inline code | 1. Check the inline code style (`color: #e83e8c` on `background: #f4f4f4`). 2. Compute the contrast ratio. | The contrast ratio of `#e83e8c` on `#f4f4f4` meets WCAG 2.1 AA (4.5:1). If not, flag it as a known issue. |
| E6 | Focus indicator visible on copy button | 1. Tab to the copy button. 2. Check the `:focus-visible` outline or other focus indicator. | The copy button has a visible focus indicator (outline, box-shadow, or border change) when focused via keyboard. |
| E7 | Code blocks are keyboard accessible | 1. Tab through all focusable elements on the page. 2. Verify the copy button can receive focus. | The copy button is reachable via sequential keyboard navigation. |

### Suite F: RTL Pages (`e2e/prismjs-rtl.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| F1 | RTL page loads Prism assets | 1. Navigate to a page with Arabic locale (`/?switch-lang=ar` or similar). 2. Check that Prism CSS and JS are loaded. | Prism assets are present on RTL pages — the RTL condition in `header.php` only adds `rtl.min.css` and `rtl.min.js` on top of Prism assets, without removing them. |
| F2 | Code blocks render on RTL page | 1. Navigate to the test post on an RTL locale. 2. Check that code blocks have syntax tokens. | All code blocks on RTL pages are highlighted with Prism tokens. |
| F3 | Line numbers display correctly on RTL | 1. Navigate to the test post on an RTL locale. 2. Check the `.line-numbers-rows` position. | Line numbers are positioned correctly (on the right side for RTL, via `rtl.min.css` override if applicable). The numbers are not clipped or misaligned. |
| F4 | Copy button works on RTL pages | 1. Navigate to test post on RTL. 2. Click the copy button. 3. Read clipboard. | The copy button copies the code block text to clipboard correctly on RTL pages. |
| F5 | Language label displayed on RTL | 1. Navigate to test post on RTL. 2. Check the language label in the toolbar. | The language label is displayed correctly (e.g., "PHP" should show regardless of page direction). |

### Suite G: Content Security Policy (`e2e/prismjs-csp.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| G1 | Prism JS loads without CSP violation | 1. Set up a page console listener for CSP violations. 2. Navigate to the test post. 3. Capture any `SecurityPolicyViolation` events. | No CSP violation events are reported for `script-src` directive related to Prism JS. |
| G2 | Prism CSS loads without CSP violation | 1. Set up a page console listener. 2. Navigate to the test post. 3. Capture any CSP violation events for `style-src`. | No CSP violation events for `style-src` related to Prism CSS. |
| G3 | Prism assets are served from 'self' | 1. Check the `src` attributes of Prism `<script>` and `<link>` tags. | All Prism asset URLs are relative paths (starts with `assets/vendor/prism/`) or on the same origin (`'self'`), not from CDN/external hosts. |
| G4 | CSP headers allow Prism to function | 1. Navigate to test post. 2. Verify Prism completely highlights code blocks. 3. Check console for any errors. | Syntax highlighting works fully; no console errors related to CSP blocking inline styles or scripts. |

### Suite H: Console Errors (`e2e/prismjs-console.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| H1 | No JS errors on page load | 1. Set up page console listener capturing all `console.error` and JS exceptions. 2. Navigate to the test post. 3. Wait for page to be fully loaded and Prism to complete highlighting. | Zero `console.error` messages and zero unhandled JS exceptions during or after page load. |
| H2 | No JS errors on copy action | 1. Navigate to test post. 2. Attach console listener. 3. Click the copy-to-clipboard button on each code block. | No console errors are generated when copying code. |
| H3 | No Prism-related warnings | 1. Navigate to test post. 2. Check console for Prism-specific warnings (e.g., "Unknown language", "Language not found"). | No Prism-related warnings appear in the console. |

### Suite I: Normalize Whitespace (`e2e/prismjs-normalize-whitespace.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| I1 | Excessive leading whitespace is normalized | 1. Navigate to test post. 2. Locate the indented PHP code block. 3. Check the text content of the first line. | Leading whitespace in the code block has been normalized (excessive indentation removed). The code should start at a reasonable indent level. |
| I2 | Code visibility after normalization | 1. Navigate to test post. 2. Check the indented code block for proper rendering. | The normalized code is visible and properly highlighted, with no clipping or overflow issues. |

### Suite J: Double Initialization (`e2e/prismjs-double-init.spec.ts`)

| # | Test Name | Steps | Expected Outcome |
|---|-----------|-------|------------------|
| J1 | Prism.highlightAll is called once | 1. Override `Prism.highlightAll` before page loads to increment a counter. 2. Navigate to test post. 3. Check the counter value. | `Prism.highlightAll` (or the automatic highlighting triggered by Prism on load) is invoked exactly once. |
| J2 | No duplicate token application | 1. Navigate to test post. 2. For a given code block, check that token spans are not duplicated. | Each token in a code block appears exactly once. (E.g., `echo` should have one `<span class="token keyword">echo</span>`, not two nested or adjacent identical spans.) |
| J3 | No double toolbar creation | 1. Navigate to test post. 2. Count `.toolbar` elements per `div.code-toolbar`. | Each `div.code-toolbar` contains exactly one `.toolbar` element. |

---

## 6. Shared Test Fixtures

### `e2e/fixtures/code-blocks.html`

This file contains the HTML used in the seed post (see Section 2.3). It can be imported by the seed script to avoid duplication.

### `e2e/fixtures/test-data.ts`

A shared TypeScript file that exports constants:

```typescript
export const ADMIN_USER = 'administrator';
export const ADMIN_PASS = '4dMin(*)^';
export const TEST_POST_TITLE = 'Prism.js Syntax Highlighting Test Post';
export const SUPPORTED_LANGUAGES = [
  'php', 'js', 'html', 'bash', 'css', 'python',
  'sql', 'json', 'yaml', 'markdown', 'diff'
];
export const LANG_LABELS: Record<string, string> = {
  php: 'PHP', js: 'JavaScript', html: 'HTML', bash: 'Bash',
  css: 'CSS', python: 'Python', sql: 'SQL', json: 'JSON',
  yaml: 'YAML', markdown: 'Markdown', diff: 'Diff'
};
```

---

## 7. Running Instructions

### 7.1 Prerequisites

```bash
# Ensure dependencies are installed
cd /var/www/blogware/public_html
npm install

# Ensure PHP is available
php -v
# Should output PHP 8.5.8 or similar

# Ensure blogware.site resolves locally
grep blogware.site /etc/hosts
# Should have: 127.0.0.1 blogware.site www.blogware.site

# If missing, add it:
echo "127.0.0.1 blogware.site www.blogware.site" | sudo tee -a /etc/hosts
```

### 7.2 Working Directory

All Playwright commands should be run from the project root:

```bash
cd /var/www/blogware/public_html
```

### 7.3 Start PHP Dev Server (Manual)

```bash
# Start the PHP built-in server
php -S 0.0.0.0:80 -t public/ > /tmp/php-server.log 2>&1 &
echo $!  # Save the PID for later
```

### 7.4 Run Playwright Tests

```bash
# Run all Prism.js tests
npx playwright test e2e/prismjs-*.spec.ts

# Run specific test file
npx playwright test e2e/prismjs-highlighting.spec.ts

# Run with UI mode
npx playwright test --ui

# Run in specific browser
npx playwright test --project=chromium e2e/prismjs-highlighting.spec.ts

# Run with trace on (useful for debugging failures)
npx playwright test --trace on

# View the HTML report after run
npx playwright show-report
```

### 7.5 Seeding Test Data First

The seed file must be run **before** any other tests to ensure the test post exists:

```bash
# Seed the database with test data
npx playwright test e2e/seed.spec.ts

# Then run all other tests
npx playwright test e2e/prismjs-*.spec.ts --ignore=e2e/seed.spec.ts
```

Alternatively, use Playwright test dependencies:

```typescript
// In each spec file, use test.describe.serial and depend on seed
test.describe.serial(() => {
  // tests here
});
```

### 7.6 Cleanup

```bash
# Kill the PHP server when done
kill <PHP_SERVER_PID>

# Or find and kill it
pkill -f "php -S 0.0.0.0:80"
```

---

## 8. Test Execution Order

The recommended execution order (considering test independence and seed dependency):

```
1. e2e/seed.spec.ts                        ← MUST run first
2. e2e/prismjs-config.spec.ts              ← No post dependency
3. e2e/prismjs-highlighting.spec.ts        ← Needs seeded post
4. e2e/prismjs-plugins.spec.ts             ← Needs seeded post
5. e2e/prismjs-inline-code.spec.ts         ← Needs seeded post
6. e2e/prismjs-accessibility.spec.ts       ← Can run standalone (CSS analysis)
7. e2e/prismjs-rtl.spec.ts                 ← Needs seeded post + locale switching
8. e2e/prismjs-csp.spec.ts                 ← Can run standalone
9. e2e/prismjs-console.spec.ts             ← Needs seeded post
10. e2e/prismjs-normalize-whitespace.spec.ts ← Needs seeded post
11. e2e/prismjs-double-init.spec.ts        ← Needs seeded post
```

> **Note:** All spec files ending with `*.spec.ts` are designed to be independently executable after the seed has been run. The seed should only need to run once per test environment setup.

---

## 9. Known Limitations & Edge Cases

| # | Issue | Mitigation |
|---|-------|------------|
| 1 | Clipboard API requires secure context (HTTPS) or `localhost`. Since `blogware.site` is served over HTTP, clipboard access may be denied. | Use `browserContext.grantPermissions(['clipboard-read', 'clipboard-write'])` in test setup, or verify copy button behavior via DOM state changes rather than actual clipboard reads. |
| 2 | Language label text depends on Prism's internal language name mapping (e.g., `js` → `JavaScript`, `bash` → `Bash`). The exact label text should be verified against Prism's output. | Pre-define language label mapping in test fixtures and validate against it. |
| 3 | Color contrast verification cannot be automated purely with Playwright — it requires fetching token CSS classes from Prism CSS and computing contrast ratios programmatically. | Use `page.evaluate()` to extract computed styles and compute WCAG contrast ratios using the formula defined in WCAG 2.1. |
| 4 | The CSP tests rely on detecting `SecurityPolicyViolation` events, which may not be triggered in all browsers the same way. | Supplement with manual verification of CSP headers via `page.evaluate()` reading `document.securityPolicy` or by checking response headers directly. |
| 5 | RTL page testing depends on the locale being switched to an RTL language (Arabic, Farsi, etc.). The app must have at least one RTL locale configured. | Verify that the `ar` locale (or other RTL) is available in the language switcher dropdown. Switch to it before testing. |
| 6 | The summernote editor in `admin-layout.php` uses `codeview` for HTML editing. Setting post content programmatically via JavaScript may be more reliable than using the WYSIWYG UI. | Use `page.evaluate()` to directly set the textarea content and bypass summernote's overlay. |
| 7 | Double initialization detection requires instrumenting Prism before it loads. This can be done by injecting a script before the Prism script tag. | Use `page.addInitScript()` to set up counters/instrumentation before navigation. |

---

## 10. WCAG Color Contrast Reference

The Prism Default theme uses the following foreground colors on `#f5f2f0` background:

| Token Class | Color | Contrast Ratio | WCAG AA Pass? |
|-------------|-------|----------------|---------------|
| `.token.comment`, `.token.prolog`, `.token.doctype`, `.token.cdata` | `#708090` (SlateGray) | ~3.6:1 | ❌ (4.5:1 required) |
| `.token.punctuation` | `#999` | ~2.3:1 | ❌ |
| `.token.property`, `.token.tag`, `.token.boolean`, `.token.number`, `.token.constant`, `.token.symbol`, `.token.deleted` | `#905` | ~3.2:1 | ❌ |
| `.token.selector`, `.token.attr-name`, `.token.string`, `.token.char`, `.token.builtin`, `.token.inserted` | `#690` | ~4.1:1 | ❌ |
| `.token.operator`, `.token.entity`, `.token.url` | `#9a6e3a` | ~3.6:1 | ❌ |
| `.token.atrule`, `.token.attr-value`, `.token.keyword` | `#07a` | ~4.8:1 | ✅ |
| `.token.function`, `.token.class-name` | `#dd4a68` | ~3.8:1 | ❌ |
| `.token.regex`, `.token.important`, `.token.variable` | `#e90` | ~2.5:1 | ❌ |

> **Note:** The Prism Default theme does **not** meet WCAG 2.1 AA for most token types. This is a known limitation of the theme itself, not the implementation. Tests in Suite E should verify the actual contrast ratios and **document** the failures rather than assert strict passing. This may drive a future decision to switch to a high-contrast Prism theme.

The contrast ratio formula is:

```
L1 = relative luminance of lighter color
L2 = relative luminance of darker color
contrast_ratio = (L1 + 0.05) / (L2 + 0.05)
```

Where relative luminance `L = 0.2126 * R + 0.7152 * G + 0.0722 * B` (with sRGB linearization).

---

## 11. Deliverables

When implementation is complete, the following files should exist:

| File | Purpose |
|------|---------|
| `e2e/seed.spec.ts` | Test data seeder |
| `e2e/prismjs-config.spec.ts` | Asset loading verification |
| `e2e/prismjs-highlighting.spec.ts` | Core syntax highlighting tests |
| `e2e/prismjs-plugins.spec.ts` | Plugin behavior tests |
| `e2e/prismjs-inline-code.spec.ts` | Inline code tests |
| `e2e/prismjs-accessibility.spec.ts` | WCAG/accessibility tests |
| `e2e/prismjs-rtl.spec.ts` | RTL page tests |
| `e2e/prismjs-csp.spec.ts` | CSP compatibility tests |
| `e2e/prismjs-console.spec.ts` | Console error tests |
| `e2e/prismjs-normalize-whitespace.spec.ts` | Normalize Whitespace tests |
| `e2e/prismjs-double-init.spec.ts` | Double initialization tests |
| `e2e/page-objects/blog-post.page.ts` | Blog post page object |
| `e2e/page-objects/admin-login.page.ts` | Admin login page object |
| `e2e/fixtures/code-blocks.html` | Test data HTML fragment |
| `e2e/fixtures/test-data.ts` | Shared test constants |
| `specs/PRISMJS_PLAYWRIGHT_TEST_PLAN.md` | This test plan |

---

## 12. Appendix: Verification Checklist Mapping

This section maps the verification items from Section 6 of `SYNTAX_HIGHLIGHTING_PLAN.md` to the corresponding test suites in this plan.

| Checklist Item | Test Suite(s) | Status |
|----------------|---------------|--------|
| Code blocks render with syntax highlighting | B1–B13 | Planned |
| Copy-to-clipboard button appears on code blocks | C4, C6 | Planned |
| Line numbers display correctly | C1, C2 | Planned |
| Language label shows on `<pre>` blocks | C8, C9 | Planned |
| Inline `<code>` styling is unaffected | D1–D4 | Planned |
| Copy-to-clipboard button has `aria-label="Copy code"` | E1 | Planned |
| Line numbers have `aria-hidden="true"` | E2 | Planned |
| Color contrast of Prism theme meets WCAG 2.1 AA | E4, E5 | Planned |
| RTL pages render correctly with highlighting | F1–F5 | Planned |
| No JS console errors | H1–H3 | Planned |
| CSP allows `'self'` for scripts/styles | G1–G4 | Planned |
| Normalize Whitespace plugin works | I1, I2 | Planned |
| Prism loads only once (no double initialization) | J1–J3 | Planned |
| `prism.css` loaded in header with non-blocking pattern | A1, A2 | Planned |
| `prism.js` loaded in footer with `defer` | A4, A5 | Planned |
| Integrity hashes match actual files | A6 | Planned |
| `prism-override.min.css` exists and is loaded | A3 | Planned |

---

*End of test plan*
