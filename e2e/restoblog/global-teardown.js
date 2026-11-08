// @ts-check
// global-teardown.js
// Playwright global teardown for the RestoBlog theme suite: removes disposable
// fixture rows and restores the "blog" theme so the default suite stays green.
import { cleanupRestoblogFixtures, restoreBlogTheme } from './restoblog-fixtures.js';

export default function globalTeardown() {
  cleanupRestoblogFixtures();
  restoreBlogTheme();
}