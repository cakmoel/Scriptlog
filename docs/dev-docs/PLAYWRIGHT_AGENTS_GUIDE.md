# Playwright Test Agents Guide - Scriptlog

**Version:** 1.0.0 | **Last Updated:** October 2026 | **Playwright:** 1.61.1 (`@playwright/test` in root `package.json`)

> This guide covers the **agentic E2E workflow only** (planner → generator → healer).
> For PHPUnit/PHPStan/CI see [TESTING_GUIDE.md](TESTING_GUIDE.md).
> For routes, architecture, and the test account see `AGENTS.md` and [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md).

---

## 1. The three agents

Playwright ships three test agents. Use independently, sequentially, or as chained calls in an agentic loop. Sequential use produces test coverage:

| # | Agent | Input | Output | Role |
|---|-------|-------|--------|------|
| 1 | 🎭 **planner** | Clear request + seed test (`e2e/seed.spec.ts`) + optional PRD | Markdown plan in `specs/` | Explores the app, produces a human-readable but precise test plan |
| 2 | 🎭 **generator** | Markdown plan from `specs/` | Executable spec in `e2e/` | Transforms the plan into Playwright tests, verifying selectors/assertions live |
| 3 | 🎭 **healer** | Failing test name | Passing test (or skipped if functionality is broken) | Replays failing steps, inspects current UI, patches locator/wait/data, re-runs until green or guardrails stop the loop |

File-based handoff: plan in `specs/` → tests in `e2e/` → fixes written back to the same `.spec` file. Commit each spec + its generated test together.

---

## 2. One-time setup: agent definitions

Agent definitions are versioned instruction + tool bundles. Regenerate whenever Playwright is updated:

```bash
npx playwright init-agents --loop=opencode
```

Flags: `--loop <claude|codex|copilot|opencode|vscode|vscode-legacy>`, `-c/--config <file>` (config used to find the seed-test project), `--project <project>`, `--prompts` (include prompts). This repo has no committed agent definitions yet — run the command above when starting agentic E2E work.

---

## 3. Repo conventions (differs from Playwright defaults)

| Upstream default | This repo | Why |
|------------------|-----------|-----|
| Tests in `tests/` | Tests in **`e2e/`** (`testDir: './e2e'` in `playwright.config.js`) | Existing convention; `tests/` is PHPUnit |
| Single `playwright.config.ts` | **Three configs**: `playwright.config.js` (blog baseline), `playwright.tastybites.config.ts`, `playwright.restoblog.config.js` | Theme suites flip `tbl_themes` in globalSetup and must not run under the default config |
| Seed in `tests/seed.spec.ts` | Seed in **`e2e/seed.spec.ts`** (minimal stub) | Planner uses it for env setup + as the style example for generated tests |

Specs inventory: `specs/` holds 18 plans (e.g. `posts.md` v1.1, `protected-posts.md`, `comments-reply-system.md`, `theme-baseline.md`, …) plus a stub `README.md`. Reference example: `specs/posts.md` declares `Bootstrap: e2e/seed.spec.ts` and documents purpose, URL formats, fixtures, expected behaviours, and failure scenarios — the shape every new plan should follow.

---

## 4. Planner workflow

**Prompt pattern:** state the scenario, point at the seed, attach a PRD if one exists.

> "Generate a plan for guest checkout." + `e2e/seed.spec.ts` in context (or mention the file name).

The planner runs the seed to execute global setup, project dependencies, fixtures, and hooks, then explores the app and writes e.g. `specs/<feature>.md` containing: purpose, key files under test, URL formats, datasets/fixtures, step-by-step scenarios with expected outcomes, security/accessibility/responsive expectations, failure + recovery scenarios, console/network expectations.

**Repo-specific inputs to give the planner:** test account (`administrator` / `4dMin(*)^`), target config (default vs tastybites vs restoblog), permalink mode, and the route table in `AGENTS.md` (Dispatcher owns 404s via `validateContentExists()` — never assert 404 handling in templates; slug mismatch → 404).

---

## 5. Generator workflow

**Prompt pattern:** point at the approved plan.

> "Generate tests from `specs/<feature>.md`." (include the file in context or mention its name).

The generator performs the scenarios live, verifies selectors and assertions against the running app, and writes `e2e/<feature>.spec.ts` (theme suites: `e2e/tastybites/tb-*.spec.ts`, `e2e/restoblog/rb-*.spec.js`). Generated tests may contain initial errors — that is expected; the healer fixes them. Use the catalog of Playwright assertions and generation hints for structural/behavioral validation.

**Commands:**

```bash
npx playwright test --project=chromium                                          # default suite, fast loop
npx playwright test --config=playwright.tastybites.config.ts --project=chromium # tastybites only
npx playwright test --config=playwright.restoblog.config.js --project=chromium  # restoblog only
npm run test:e2e:tb:typecheck   # tsc --noEmit for the TS suite
```

---

## 6. Healer workflow

**Prompt pattern:** give the failing test name.

> "Heal `e2e/theme-protected.spec.ts`."

The healer replays the failing steps, inspects the current UI for equivalent elements/flows, proposes a patch (locator update, wait adjustment, data fix), and re-runs until green. If it judges the functionality itself broken, it leaves a skipped test — treat that as a bug report, not a pass.

---

## 7. Harness rules (must-follow)

1. **Base URL + server:** `http://127.0.0.1:8099`; configs boot PHP via `sudo -n -u www-data php -S 127.0.0.1:8099 -t . e2e/router.php` (`reuseExistingServer: !process.env.CI`).
2. **Concurrency:** `workers: 1`. The suites share one dev server, one e2e database, and the file-backed unlock rate limiter — parallel runs are non-deterministic. **Never run two configs at the same time.**
3. **Config scoping:** default config ignores `**/tastybites/**` and `**/restoblog/**`. RestoBlog additionally needs an active premium license/theme seed or every page falls back to the `blog` theme (see `TROUBLESHOOTING.md` §42).
4. **CSRF:** admin form tokens are single-use with ~10 min expiry. Double-submit, replay, or missing-token POSTs → HTTP 400. Unknown extra POST fields → HTTP 413. Tests must fetch a fresh token per submission.
5. **Auth:** unauthenticated admin access redirects to `admin/login.php`; unauthorized role → 403. Login-gated scenarios need the `administrator` fixture (lower-privilege RBAC scenarios are skipped when no such fixture exists — see `specs/posts.md` §15).
6. **Commit:** one Conventional Commit per spec + generated test (`test(e2e): …`), same as any other task (`git add -A && git commit -m "type(scope): description"`).

---

## 8. Further reading

- Plan-level precedent (theme-specific planner → generator → healer breakdown): `plan/THEME_REMEDIATION_PLAN.md`
- TastyBites suite plan: `plan/TASTYBITES_E2E_TEST_PLAN.md`
- CI execution: [TESTING_GUIDE.md](TESTING_GUIDE.md) §8 (`.github/workflows/playwright.yml`)
- Search architecture (for search specs): [DEVELOPER_GUIDE.md](DEVELOPER_GUIDE.md) §25
