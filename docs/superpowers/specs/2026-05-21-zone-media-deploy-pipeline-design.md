# Zone Media Deploy Pipeline — Design

**Date:** 2026-05-21
**Status:** Approved for implementation planning
**Scope:** Replace manual FileZilla deploys with an automated GitHub Actions pipeline that runs tests against an isolated environment and deploys to Zone Media on every push to `main`.

---

## Goal

When a feature branch is merged into `development`, then `development` is merged into `main`, the resulting push to `main` automatically:

1. Spins up an isolated test environment (PHP + MySQL) inside the CI runner.
2. Runs the full Playwright suite against it.
3. If tests pass, deploys the working tree to the Zone Media web root via SSH + rsync.
4. Leaves the previous version on the server as a backup for fast rollback.

The branching model is unchanged: `feature/*` → `development` → `main`. Only the act of landing on `main` becomes a deploy trigger.

## Non-goals

Explicitly out of scope for this project (can be added later):

- Staging environment / pre-production slot
- Blue-green or zero-downtime deploys
- Automated DB schema migrations (schema changes remain manual)
- Slack / email notifications
- Post-deploy automated health checks beyond rsync exit code
- Refactoring tests to be DB-independent (they will use a real ephemeral DB)
- Auto-merge of Dependabot PRs (manual review/merge; can add a tiny auto-merge workflow later if the volume becomes annoying)

---

## Architecture

A single GitHub Actions workflow file `.github/workflows/deploy.yml` triggered on `push: branches: [main]`. Two sequential jobs:

```
┌────────────────────────────────────────────────────────────────┐
│  Job 1: test                                                   │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Services: mysql:8                                       │  │
│  │  Steps:                                                  │  │
│  │    - checkout                                            │  │
│  │    - setup-node + npm ci                                 │  │
│  │    - install PHP + Playwright browsers                   │  │
│  │    - import db/schema.sql into mysql service             │  │
│  │    - write CI-only src/db/laoseis.php → localhost mysql  │  │
│  │    - seed Login table with TestUser                      │  │
│  │    - start `php -S localhost:8000` in background         │  │
│  │    - npx playwright test                                 │  │
│  │    - upload playwright-report artifact (always)          │  │
│  └──────────────────────────────────────────────────────────┘  │
│                            │ success                            │
│                            ▼                                    │
│  Job 2: deploy  (needs: test, if: success)                     │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  Steps:                                                  │  │
│  │    - checkout                                            │  │
│  │    - load ZONE_SSH_KEY into agent                        │  │
│  │    - ssh-keyscan ZONE_SSH_HOST → known_hosts             │  │
│  │    - ssh: snapshot current webroot → webroot.bak-<sha>   │  │
│  │    - ssh: prune backups older than the 3 most recent     │  │
│  │    - rsync -avz --delete --exclude-from=.deployignore    │  │
│  │      ./ user@host:webroot/                               │  │
│  └──────────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────────┘
```

The existing `.github/workflows/playwright.yml` is kept for PRs into `development` and `main` (test-only, no DB needed unless we extend it the same way). The new workflow runs the *full* test+deploy chain only on push to `main`.

Decision: keep two separate workflow files for clarity. PR feedback stays fast; the heavier deploy workflow only runs when it matters.

---

## CI test environment (Job 1)

The test job spins up a complete throwaway environment so tests that write to the database (e.g. `lisa_too.spec.ts`) can run for real, gated by their assertions, without ever touching Zone.

**MySQL via GitHub Actions service container:**

```yaml
services:
  mysql:
    image: mysql:8
    env:
      MYSQL_ROOT_PASSWORD: testroot
      MYSQL_DATABASE: laoseis_test
    ports:
      - 3306:3306
    options: >-
      --health-cmd="mysqladmin ping"
      --health-interval=10s
      --health-timeout=5s
      --health-retries=5
```

**Schema bootstrap (prerequisite — one-time setup):**

The repo does not currently contain a SQL schema dump. As a prerequisite to this pipeline working, the user runs once locally against the Zone dev DB:

```bash
mysqldump --no-data --routines --triggers -u <dev_user> -p <dev_db> > db/schema.sql
```

…and commits `db/schema.sql`. The CI job imports it into the service container before running tests. Going forward, any schema change is reflected by re-running this command and committing the updated dump.

**CI-only `src/db/laoseis.php`:**

Written by the workflow inside the runner *after* checkout, so it never enters git:

```php
<?php
$conn = mysqli_connect('127.0.0.1', 'root', 'testroot', 'laoseis_test');
if (!$conn) { die('Connection failed: ' . mysqli_connect_error()); }
```

The runner is destroyed at job end. This file cannot leak into the deploy job (different job, different runner) and is excluded from rsync via `.deployignore` even if it somehow appeared.

**Test user seed:**

`tests/helpers.ts` expects a `TestUser` row in the `Login` table. The workflow runs an `INSERT` with a `password_hash('testtest', PASSWORD_DEFAULT)` value before starting Playwright. (We compute the hash once locally and commit it as a fixture, or compute it inline with a one-line `php -r`.)

**PHP server:**

`php -S localhost:8000 -t .` started in the background, with a short wait/poll until the port responds before tests start. `workers: 1` in `playwright.config.ts` already accounts for the single-threaded built-in server.

---

## Deploy (Job 2)

Runs only if Job 1 succeeded.

**Secrets (set once in repo Settings → Secrets and variables → Actions):**

| Secret | Purpose |
|---|---|
| `ZONE_SSH_HOST` | Server hostname (e.g. `username.zonevs.eu`) |
| `ZONE_SSH_USER` | SSH username |
| `ZONE_SSH_KEY` | Private key — newly generated, dedicated to deploys only |
| `ZONE_DEPLOY_PATH` | Absolute path to web root on server (e.g. `/data01/virt12345/domeenid/www.example.ee/htdocs`) |

The deploy key is a fresh ed25519 keypair created specifically for this purpose. The public key is added to `~/.ssh/authorized_keys` on Zone. The private key never leaves GitHub Secrets and never touches the user's laptop.

**Backup before deploy:**

```bash
ssh user@host "
  cd $ZONE_DEPLOY_PATH/.. &&
  cp -a webroot webroot.bak-$GITHUB_SHA &&
  ls -1dt webroot.bak-* | tail -n +4 | xargs -r rm -rf
"
```

Keeps the 3 most recent backups, deletes anything older. `cp -a` preserves permissions and is fast on the same filesystem.

**rsync:**

```bash
rsync -avz --delete \
  --exclude-from=.deployignore \
  -e "ssh -o StrictHostKeyChecking=yes" \
  ./ "$ZONE_SSH_USER@$ZONE_SSH_HOST:$ZONE_DEPLOY_PATH/"
```

`--delete` ensures the server tree matches `main` exactly — files removed from the repo are removed from the server. Combined with the exclusion list, the server's `src/db/laoseis.php`, `error_log`, and any other server-only files are preserved.

**`.deployignore` (new file in repo root):**

```
.git/
.github/
.gitignore
.idea/
.deployignore
node_modules/
tests/
playwright-report/
test-results/
playwright.config.ts
package.json
package-lock.json
README.md
docs/
error_log
src/db/laoseis.php
```

---

## Rollback / recovery

Three layers, fastest to most-correct:

**Layer 1 — Instant rollback from server backup (~10 seconds, no CI involved):**

The deploy job leaves the previous version on the server next to the current one:

```
webroot/                          # current (broken) version
webroot.bak-4941023fab3.../       # previous deploy (working)
webroot.bak-2927639cde7.../       # one before that
```

A small `rollback.sh` script lives in the webroot. SSH in, run `./rollback.sh`, site is restored to the previous deploy in seconds. The broken version is moved aside as `webroot.broken-<timestamp>` for inspection, not deleted.

**Layer 2 — Git revert (~3-5 min, restores history alignment):**

After Layer 1 has restored service, fix it properly in git:

```bash
git revert <bad-commit>
git push origin main
```

The pipeline runs on the reverted code, tests pass, the rolled-back state is deployed officially. `main`, the server, and the backup chain all agree again.

**Layer 3 — Manual FileZilla (last resort):**

If GitHub Actions itself is unavailable or the deploy key is compromised, the user retains the ability to upload from their local clone via FileZilla. The pipeline is additive, not a hard replacement.

**Database:**

The pipeline never touches the DB. A code-only rollback is fully safe. The one risk case is a deploy that bundled a manual schema change: rolling back the code may leave the schema ahead of the code. Mitigation:

- **Rule:** schema changes are additive only (add columns, add tables; do not drop or rename). Old code stays compatible with the new schema, so code rollback is always safe.
- **Safety net:** before any deploy that requires a destructive schema change, run `mysqldump` on the server prod DB to get a restore point.

---

## Dependency maintenance (Dependabot)

The project has a small JS devDependency surface — `@playwright/test` and `@types/node` — used only by the test job inside CI. They are never deployed to the server (`.deployignore` excludes `node_modules/`, `package.json`, `package-lock.json`). Vulnerabilities here cannot reach production, so the goal of dependency maintenance is **keeping Playwright current** (new browser versions, bug fixes, fewer Dependabot alerts cluttering the repo), not blocking security risk to prod.

**Approach:** add `.github/dependabot.yml` configured for the npm ecosystem on a weekly schedule, with all devDependency updates grouped into a single PR per week. Dependabot opens the PR, the existing PR test workflow runs against it, the user reviews and merges manually.

```yaml
version: 2
updates:
  - package-ecosystem: npm
    directory: /
    schedule:
      interval: weekly
    open-pull-requests-limit: 3
    groups:
      dev-dependencies:
        dependency-type: development
```

**Why grouped:** prevents one PR per dep per week. With only two devDependencies a single grouped PR is essentially "bump Playwright" most weeks.

**Why weekly, not daily:** Playwright releases ~once a month; daily checks are noise.

**Auto-merge:** out of scope for this project. If the volume becomes annoying later, a 15-line workflow can auto-merge Dependabot PRs that pass CI for patch/minor versions on devDependencies only.

---

## Rollout plan

The pipeline must not be turned on against a server whose state diverges from `main` — the user has noted that `main` currently contains refactored code not yet deployed. The rollout is therefore staged:

1. **Prerequisites land in repo:** `db/schema.sql`, `.deployignore`, new `.github/workflows/deploy.yml`, `rollback.sh` (committed but not yet placed on server).
2. **Configure GitHub Secrets** with Zone SSH credentials.
3. **One-time manual deploy of `main`** (via FileZilla or a manual local rsync command — same as today) so the server matches `main` before automation takes over. The user does this carefully, validates the site works, then proceeds.
4. **Place `rollback.sh` on server** and verify it works against a no-op deploy.
5. **First automated deploy:** push a trivial change to `main` (e.g. a README edit) — verify the pipeline runs end-to-end and the site is unaffected.
6. **From here forward, all deploys go through the pipeline.**

---

## Risks and mitigations

| Risk | Mitigation |
|---|---|
| CI test DB connection details accidentally point to Zone | CI uses GitHub Actions service container on `127.0.0.1`. There is no network path from the runner to Zone's MySQL. |
| Deploy key leaked from GitHub Secrets | Key is dedicated to deploys only (not the user's personal key). Can be rotated by generating a new keypair and updating the secret + server's `authorized_keys`. |
| `--delete` wipes server-only files | `.deployignore` lists everything the server owns (`src/db/laoseis.php`, `error_log`). Reviewed as part of every PR that adds new server-only files. |
| Backup disk fills | Job prunes to the 3 most recent backups every run. Each backup is a full copy of the webroot, so disk usage is bounded at ~4× the webroot size. |
| Pre-deploy backup itself fails (e.g. SSH timeout) | Job is structured so backup is a separate step before rsync. If backup fails, the job fails before any files are changed. |
| Schema drift between `db/schema.sql` and the live DB | Documented convention: whoever changes the schema re-runs `mysqldump --no-data` and commits the updated dump in the same PR. Caught in code review. |
| Tests pass in CI but real prod has different data | Out of scope — this pipeline gates on test correctness, not data compatibility. Additive-only schema rule mitigates the common case. |

---

## Open implementation details (resolved during planning)

- Exact PHP version to install in CI (likely `shivammathur/setup-php@v2` with PHP 8.x — match production).
- Where to compute the bcrypt hash for the test user (inline `php -r` is simplest).
- Whether the `rollback.sh` script should also restore the corresponding DB dump (decision: no — keep it code-only; DB recovery stays manual).
- Whether `db/schema.sql` should include the `Login` table's test user row (decision: no — schema dump is `--no-data`; CI seeds the user as a separate step so prod imports of the same dump don't carry test credentials).

---

## Files added/changed by this project

**New:**
- `.github/workflows/deploy.yml` — the full test + deploy workflow
- `.github/dependabot.yml` — weekly grouped devDependency updates
- `.deployignore` — rsync exclusion list
- `db/schema.sql` — schema dump from Zone dev DB (one-time, then maintained)
- `rollback.sh` — placed on server, not part of webroot deploy

**Unchanged:**
- `.github/workflows/playwright.yml` — kept as-is for PR feedback
- All application code
- `src/db/laoseis.php` on the production server — never touched by deploy

**Configuration (outside repo):**
- GitHub Secrets: `ZONE_SSH_HOST`, `ZONE_SSH_USER`, `ZONE_SSH_KEY`, `ZONE_DEPLOY_PATH`
- Zone server: deploy public key added to `~/.ssh/authorized_keys`
- Zone server: `rollback.sh` placed alongside webroot
