// prismjs-csp.spec.ts
// Suite G: Content-Security-Policy compatibility (self-hosted, no CDN).
import { test, expect } from '@playwright/test';
import {
  BASE_URL,
  FIXTURE_POST_URL,
  INJECT_HTML_FULL,
  gotoFixturePost,
  grantClipboard,
  hoverAndClickCopy,
  injectFixture,
  isReportOnlyCspNoise,
} from './prismjs-fixtures';

test.describe('prismjs csp', () => {
  test('G1: enforced policy is nonce-based without unsafe-inline scripts', async ({
    page,
  }) => {
    const response = await page.goto(
      `${BASE_URL}${FIXTURE_POST_URL}`,
    );
    expect(response?.status()).toBe(200);
    const headers = response?.headers() ?? {};
    const csp: string = headers['content-security-policy'] ?? '';
    expect(csp, 'enforced CSP present').toContain("script-src 'self'");
    expect(csp).toContain('nonce-');
    const scriptSrc: string =
      csp
        .split(';')
        .map((d: string): string => d.trim())
        .find((d: string): boolean => d.startsWith('script-src')) ?? '';
    expect(scriptSrc, 'no unsafe-inline in script-src').not.toContain(
      'unsafe-inline',
    );
  });

  test('G2: prism assets are same-origin (no cdn)', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    const origin: string = new URL(page.url()).origin;
    const urls: string[] = await page.evaluate(
      (): string[] => {
        const script: HTMLScriptElement | null = document.querySelector(
          'script[src*="vendor/prism/prism.js"]',
        );
        const links: NodeListOf<HTMLLinkElement> =
          document.querySelectorAll('link[href*="prism"]');
        const out: string[] = [];
        if (script) {
          out.push(script.src);
        }
        links.forEach((l: HTMLLinkElement): void => {
          out.push(l.href);
        });
        return out;
      },
    );
    expect(urls.length).toBeGreaterThan(0);
    for (const u of urls) {
      expect(new URL(u).origin, u).toBe(origin);
    }
  });

  test('G3: no csp violations while highlighting and copying', async ({
    page,
    context,
  }) => {
    // Best effort: unsupported on firefox/webkit, where the click below
    // still exercises the copy path via the execCommand fallback.
    await grantClipboard(context);
    const violations: string[] = [];
    await page.addInitScript((): void => {
      document.addEventListener(
        'securitypolicyviolation',
        (e: Event): void => {
          const ev = e as SecurityPolicyViolationEvent;
          (window as unknown as { __csp: string[] }).__csp =
            (window as unknown as { __csp?: string[] }).__csp ?? [];
          // Disposition matters: the Report-Only header (style-src without
          // 'unsafe-inline') reports pre-existing inline-style usage that
          // the enforced header (with 'unsafe-inline') explicitly allows.
          // Only "enforce" dispositions are real blocks.
          (window as unknown as { __csp: string[] }).__csp.push(
            ev.disposition + ' ' + ev.violatedDirective + ' ' + ev.blockedURI,
          );
        },
      );
    });
    page.on('console', (msg): void => {
      const text: string = msg.text();
      // Report-Only notices (all three browser wordings) mirror the
      // Report-Only header's stricter style-src; the enforced header
      // allows 'unsafe-inline' styles, so only enforcing messages count.
      if (
        /content security policy|refused to (load|execute|apply)/i.test(
          text,
        ) &&
        !isReportOnlyCspNoise(text) &&
        !/no further action/i.test(text)
      ) {
        violations.push(text);
      }
    });
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    try {
      await hoverAndClickCopy(page, 0);
    } catch {
      // Copy itself is covered by C6/F4; here only violations matter.
    }
    const domViolations: string[] = await page.evaluate(
      (): string[] =>
        (window as unknown as { __csp?: string[] }).__csp ?? [],
    );
    const enforced: string[] = domViolations.filter((v: string): boolean =>
      v.startsWith('enforce'),
    );
    expect(enforced).toEqual([]);
    expect(violations).toEqual([]);
  });

  test('G4: highlighting works with csp enforced', async ({
    page,
  }) => {
    await gotoFixturePost(page);
    await injectFixture(page, INJECT_HTML_FULL);
    for (const lang of ['php', 'js', 'css']) {
      const tokens: number = await page
        .locator(`.post-single .post-body code.language-${lang} span.token`)
        .count();
      expect(tokens, `language-${lang}`).toBeGreaterThan(0);
    }
  });
});
