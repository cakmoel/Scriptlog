// Dedicated Playwright config for the TastyBites theme suite.
//
// The suite flips tbl_themes in the shared blogware_e2e database, so it must
// never run together with the default suite or the RestoBlog suite. Run it
// explicitly with:
//
//   npx playwright test --config=playwright.tastybites.config.ts
//
// The default playwright.config.ts ignores e2e/tastybites/**.
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './e2e/tastybites',
  globalSetup: './e2e/tastybites/global-setup.ts',
  globalTeardown: './e2e/tastybites/global-teardown.ts',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,
  workers: 1,
  reporter: 'html',
  use: {
    baseURL: 'http://127.0.0.1:8099',
    trace: 'on-first-retry',
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
  webServer: {
    command: 'php -S 127.0.0.1:8099 e2e/router.php',
    url: 'http://127.0.0.1:8099/admin/login.php',
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
    env: { PHP_CLI_SERVER_WORKERS: '4' },
  },
});
