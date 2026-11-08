// @ts-check
// Dedicated Playwright config for the RestoBlog theme suite.
//
// The suite flips tbl_themes in the shared blogware_e2e database, so it must
// never run together with the default suite or the TastyBites suite. Run it
// explicitly with:
//
//   npx playwright test --config=playwright.restoblog.config.js
//
// The default playwright.config.ts ignores e2e/restoblog/**.
import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
  testDir: './e2e/restoblog',
  globalSetup: './e2e/restoblog/global-setup.js',
  globalTeardown: './e2e/restoblog/global-teardown.js',
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
