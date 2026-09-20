// theme-partials.spec.ts
// Shared partials consistency: partials/card.php and partials/paginator.php render
// identical structure on home, blog, category, tag, and archive. No per-template card
// markup drift. Paginator appears at most once per listing.
import { test, expect } from '@playwright/test';
import { BLOG, BLOG_BASE_URL, type BlogRoute } from './blog-selectors';

const BASE_URL: string = BLOG_BASE_URL;

type ListingProbe = BlogRoute & { cardContainer: string };

const LISTINGS: ListingProbe[] = [
  { url: '/', cardContainer: BLOG.latestPosts, name: 'home' },
  { url: '/?blog', cardContainer: BLOG.postsListing, name: 'blog' },
  { url: '/?cat=200', cardContainer: BLOG.postsListing, name: 'category' },
  { url: '/?tag=e2e-tag', cardContainer: BLOG.postsListing, name: 'tag' },
  { url: '/?a=202608', cardContainer: BLOG.postsListing, name: 'archive' },
];

test.describe('theme shared partials', () => {
  for (const listing of LISTINGS) {
    test(`cards on ${listing.name} share the identical structure`, async ({
      page,
    }) => {
      const response = await page.goto(`${BASE_URL}${listing.url}`);
      expect(response?.status()).toBe(200);

      const container = page.locator(listing.cardContainer);
      await expect(container.first()).toBeVisible();

      const cards = container.locator(BLOG.card);
      const titleCount: number = await container
        .locator(BLOG.cardTitle)
        .count();
      const thumbCount: number = await container
        .locator(BLOG.cardThumb)
        .count();
      const metaCount: number = await container
        .locator(BLOG.cardMeta)
        .count();

      expect(titleCount).toBe(await cards.count());
      expect(thumbCount).toBe(await cards.count());
      expect(metaCount).toBe(await cards.count());
      expect(await cards.count()).toBeGreaterThanOrEqual(1);
    });
  }

  test('every card contains exactly one h3.h4 and one post-meta', async ({
    page,
  }) => {
    await page.goto(`${BASE_URL}/?blog`);
    const cards = page.locator(BLOG.listingCard);
    const count: number = await cards.count();
    for (let i = 0; i < count; i++) {
      const card = cards.nth(i);
      await expect(card.locator('h3.h4')).toHaveCount(1);
      await expect(card.locator('.post-meta')).toHaveCount(1);
    }
  });

  test('paginator appears at most once per listing page', async ({ page }) => {
    await page.goto(`${BASE_URL}/?cat=200`);
    const paginationCount: number = await page
      .locator('nav[aria-label], .pagination, .pager')
      .count();
    expect(paginationCount).toBeLessThanOrEqual(1);
  });

  test('cards from home and blog are structurally identical', async ({
    page,
  }) => {
    const shape = async (url: string): Promise<string[]> => {
      await page.goto(`${BASE_URL}${url}`);
      const card = page.locator(BLOG.card).first();
      return card.evaluate((el: HTMLElement): string[] => {
        const order: string[] = [];
        el.childNodes.forEach((node: ChildNode): void => {
          if (node.nodeType === 1) {
            order.push((node as HTMLElement).className);
          }
        });
        return order;
      });
    };
    const homeShape: string[] = await shape('/');
    const blogShape: string[] = await shape('/?blog');
    expect(blogShape).toEqual(homeShape);
  });
});
