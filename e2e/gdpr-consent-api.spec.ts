// gdpr-consent-api.spec.ts
// Phase 2 of specs/gdpr.md: the REST consent endpoints.
// api/index.php routes POST gdpr/consent -> GdprApiController@consent and
// GET gdpr/consent -> GdprApiController@getConsentStatus.
import { test, expect } from '@playwright/test';
import type { APIResponse } from '@playwright/test';
import {
  BASE_URL,
  clearLoginAttempts,
  clearRateLimiters,
  dbCount,
  seedGdprFixtures,
} from './gdpr-fixtures';

const API: string = `${BASE_URL}/api/v1/gdpr/consent`;

/** Minimal consent GET payload. */
type ConsentStatusBody = {
  success: boolean;
  status: number;
  data: { consent_given: boolean; consent_type: string };
};

/** Minimal consent POST payload. */
type ConsentWriteBody = {
  success: boolean;
  data: { status: string };
};

test.describe('GDPR consent API', () => {
  test.beforeAll((): void => {
    clearRateLimiters();
    clearLoginAttempts();
    seedGdprFixtures();
  });

  test('GET returns the consent status derived from the IP', async ({
    request,
  }) => {
    const res: APIResponse = await request.get(API);
    expect(res.status()).toBe(200);
    const json = (await res.json()) as ConsentStatusBody;
    expect(json.success).toBe(true);
    expect(json.status).toBe(200);
    expect(json.data).toHaveProperty('consent_given');
    expect(json.data).toHaveProperty('consent_type');
    expect(json.data.consent_type).toBe('cookie');
  });

  test('POST with accepted stores a consent row and returns success', async ({
    request,
  }) => {
    const res: APIResponse = await request.post(API, {
      data: { status: 'accepted', consent_type: 'cookie' },
    });
    expect(res.status()).toBe(200);
    const json = (await res.json()) as ConsentWriteBody;
    expect(json.success).toBe(true);
    expect(json.data.status).toBe('accepted');

    const count: number = dbCount(
      "SELECT COUNT(*) FROM tbl_consents WHERE consent_status='accepted' AND consent_type='cookie'",
    );
    expect(count).toBeGreaterThan(0);
  });

  test('POST with rejected stores a rejection row', async ({ request }) => {
    const res: APIResponse = await request.post(API, {
      data: { status: 'rejected', consent_type: 'cookie' },
    });
    expect(res.status()).toBe(200);
    const json = (await res.json()) as ConsentWriteBody;
    expect(json.success).toBe(true);
    expect(json.data.status).toBe('rejected');

    const count: number = dbCount(
      "SELECT COUNT(*) FROM tbl_consents WHERE consent_status='rejected' AND consent_type='cookie'",
    );
    expect(count).toBeGreaterThan(0);
  });

  test('POST with an invalid status returns a 400', async ({ request }) => {
    const res: APIResponse = await request.post(API, {
      data: { status: 'maybe', consent_type: 'cookie' },
    });
    expect(res.status()).toBe(400);
    const json = (await res.json()) as { success: boolean };
    expect(json.success).toBe(false);
  });

  test('POST without a status returns a 400', async ({ request }) => {
    const res: APIResponse = await request.post(API, {
      data: { consent_type: 'cookie' },
    });
    expect(res.status()).toBe(400);
    const json = (await res.json()) as { success: boolean };
    expect(json.success).toBe(false);
  });
});
