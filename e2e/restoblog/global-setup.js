// @ts-check
// global-setup.js
// Playwright global setup for the RestoBlog theme suite: flips the shared
// blogware_e2e database to the RestoBlog theme before any test runs.
import { activateRestoblogTheme } from './restoblog-fixtures.js';

export default function globalSetup() {
  activateRestoblogTheme();
}