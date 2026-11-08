// global-teardown.ts
// Playwright global teardown for the TastyBites theme suite: removes disposable
// fixture rows and restores the "blog" theme so the default suite stays green.
import { cleanupTastybitesFixtures, restoreBlogTheme } from './tastybites-fixtures';

export default function globalTeardown(): void {
  cleanupTastybitesFixtures();
  restoreBlogTheme();
}