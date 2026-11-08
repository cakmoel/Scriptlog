// global-setup.ts
// Playwright global setup for the TastyBites theme suite: flips the shared
// blogware_e2e database to the TastyBites theme before any test runs.
import { activateTastybitesTheme } from './tastybites-fixtures';

export default function globalSetup(): void {
  activateTastybitesTheme();
}