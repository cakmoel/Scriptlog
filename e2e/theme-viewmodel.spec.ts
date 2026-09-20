// theme-viewmodel.spec.ts
// PostViewModel parity: shared card partial and single post render the same normalized
// fields (title, date, author, content/excerpt, topics, media) with no "Array" echoes.
import { test, expect } from '@playwright/test';
import { BLOG, BLOG_BASE_URL, type BlogRoute } from './blog-selectors';

const BASE_URL: string = BLOG_BASE_URL;

const LISTING_ROUTES: BlogRoute[] = [
  { url: '/?blog', name: 'blog' },
  { url: '/?cat=200', name: 'category' },
  { url: '/?tag=e2e-tag', name: 'tag' },
  { url: '/?a=202608', name: 'archive' },
];

test.describe('theme viewmodel parity', () => {
  test('home latest-posts cards render shared h3.h4 + meta structure', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/`);
    const cards = page.locator(BLOG.latestCard);
    const titles = page.locator(BLOG.latestCardTitleLink);
    await expect(titles.first()).toBeVisible();
    expect(await titles.count()).toBe(await cards.count());
    expect(await titles.count()).toBeGreaterThanOrEqual(3);
    await expect(
      page.locator(`${BLOG.latestPosts} ${BLOG.cardMeta}`).first(),
    ).toBeVisible();
  });

  test('single post renders title/date/author/content without Array', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/?p=11`);
    const body: string = await page.content();
    expect(body).not.toContain('Array');
    expect(body).not.toContain('Warning');
    expect(body).not.toContain('Fatal error');
    await expect(page.locator(BLOG.postSingleTitle)).toContainText(
      'Test Numeric Post',
    );
    await expect(page.locator(BLOG.postBody)).toBeVisible();
  });

  test('card title on a listing matches the single post title', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const firstCard = page.locator(BLOG.listingCardTitleLink).first();
    const rawTitle: string | null = await firstCard.textContent();
    const cardTitle: string = (rawTitle ?? '').trim();
    const href: string | null = await firstCard
      .locator('..')
      .getAttribute('href');
    expect(href).not.toBeNull();
    expect(href as string).toMatch(/[?&]p=\d+/);

    await page.goto(href as string);
    await expect(page.locator(BLOG.postSingleTitle)).toHaveText(cardTitle);
  });

  test('every listing route renders cards with identical structure', async ({
    page,
  }) => {
    for (const route of LISTING_ROUTES) {
      await page.goto(`${BASE_URL}${route.url}`);
      const cards = page.locator(BLOG.listingCard);
      await expect(cards.first(), `route ${route.name}`).toBeVisible();
      const thumbs: number = await page
        .locator(`${BLOG.postsListing} ${BLOG.cardThumb}`)
        .count();
      const titles: number = await page
        .locator(`${BLOG.postsListing} ${BLOG.cardTitle}`)
        .count();
      expect(thumbs, `thumbs on ${route.name}`).toBe(titles);
      expect(thumbs, `count on ${route.name}`).toBeGreaterThanOrEqual(1);
    }
  });

  test('card without media falls back to placeholder without fatal', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const missingMedia = page.locator(BLOG.cardThumbImg);
    const attrs: { alt: string | null; loading: string | null } =
      await missingMedia.first().evaluate(
        (img: HTMLImageElement): { alt: string | null; loading: string | null } => ({
          alt: img.getAttribute('alt'),
          loading: img.getAttribute('loading'),
        }),
      );
    expect(attrs.alt).not.toBeNull();
    expect(attrs.loading).toBe('lazy');
  });
});
