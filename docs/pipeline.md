# CI/CD Pipeline — How it works and how to use it

Operational reference for the GitHub Actions pipeline that tests and deploys the Warehouse Management System to Zone Media. Read this before adding features that touch the database, before changing CI configuration, or when debugging a failed deploy.

For the original design rationale, see [superpowers/specs/2026-05-21-zone-media-deploy-pipeline-design.md](superpowers/specs/2026-05-21-zone-media-deploy-pipeline-design.md).

---

## 1. Overview

The pipeline replaces manual FileZilla uploads with two automated GitHub Actions workflows:

| File | Triggers on | What it does |
|---|---|---|
| `.github/workflows/playwright.yml` | PR into `main` or `development` | Spins up an ephemeral MySQL + PHP environment, runs Playwright. Provides pre-merge feedback. |
| `.github/workflows/deploy.yml` | Push to `main` (i.e. merge) | Same test job, plus a deploy job that snapshots the live webroot and rsyncs the new code to Zone over SSH. |

The branching model is unchanged: `feature/*` → `development` → `main`. The act of landing on `main` is what triggers a deploy. Nothing else does.

```
            ┌──────────────────────────────────┐
            │  feature/<name>                  │
            │  PR ──► Playwright Tests (PR) ──►│ merge to development
            └──────────────────────────────────┘
                              │
                              ▼
            ┌──────────────────────────────────┐
            │  development                     │
            │  PR ──► Playwright Tests (PR) ──►│ merge to main
            └──────────────────────────────────┘
                              │
                              ▼
            ┌──────────────────────────────────┐
            │  main                            │
            │  push ──► Test and Deploy ──────►│ Zone Media production
            └──────────────────────────────────┘
```

## 2. Architecture

### 2.1 Test job (used by both workflows)

Runs inside a GitHub-hosted Ubuntu runner. Roughly:

1. Check out the repo.
2. Install Node, PHP 8.2, MySQL 8 (the MySQL is a *service container*, not a regular install — GitHub Actions starts it as a sidecar).
3. Wait for MySQL to be reachable.
4. Import `db/schema.sql` into the MySQL container, **rewriting `DEFINER` clauses to `CURRENT_USER`** as it goes (more on this in §6).
5. Create a CI-only `src/db/laoseis.php` that connects to the local MySQL container (the production `laoseis.php` is gitignored and lives only on the server).
6. Seed a `TestUser` row in the `Login` table with a bcrypt'd password.
7. Start `php -S localhost:8000` with `PHP_CLI_SERVER_WORKERS=4` so it handles concurrent requests.
8. `npm ci`, install Playwright browsers, run `npx playwright test --workers=4`.
9. Always upload `playwright-report/` and `php.log` as a GitHub Actions artifact, regardless of outcome.

When the job ends, the runner is destroyed. The ephemeral DB exists for the duration of the job only — it never connects to anything on Zone, and there is no network path from a GitHub runner to your Zone database.

### 2.2 Deploy job (deploy.yml only)

Runs **only when the test job passes** AND the event is a push to `main`. The guard:

```yaml
if: github.event_name == 'push' && github.ref == 'refs/heads/main'
```

Steps:

1. Check out the repo.
2. Load `ZONE_SSH_KEY` (private deploy key, stored as a GitHub Secret) into the runner's SSH agent.
3. `ssh-keyscan` Zone's host key so the connection isn't blocked by host verification.
4. **Snapshot the live webroot** on Zone: `cp -a <webroot> <webroot>.bak-<commit-sha>`. Prune snapshots older than the 3 most recent.
5. **rsync** with `--delete --exclude-from=.deployignore` to push the working tree onto the server.

The deploy job is fast (~30 seconds) because rsync only transfers files that actually differ.

### 2.3 GitHub Secrets

| Name | Value |
|---|---|
| `ZONE_SSH_HOST` | The Zone hostname for this site |
| `ZONE_SSH_USER` | The Zone virtual-server account name (typically `virtNNNNNN`) |
| `ZONE_SSH_KEY` | Contents of the `zone_deploy` private key (ed25519) |
| `ZONE_DEPLOY_PATH` | Absolute path to the webroot on the server (typically `/data01/virtNNNNNN/domeenid/<domain>/htdocs/<sitedir>`) |

The actual values for this project live in GitHub Secrets only — do not commit them anywhere in the repo.

The deploy key is dedicated to GitHub Actions only — it is not the same key you use from your laptop. The matching public key sits in Zone's SSH keys panel.

### 2.4 What is NOT deployed

Listed in `.deployignore` and never reaches the server even when present locally:

- Git internals (`.git/`, `.gitignore`)
- CI infra (`.github/`, `.deployignore`, `playwright.config.ts`, `package.json`, `package-lock.json`, `node_modules/`)
- Tests (`tests/`, `playwright-report/`, `test-results/`)
- Docs (`README.md`, `docs/`)
- Schema dump (`db/`) — only useful for CI
- Server-owned files (`src/db/laoseis.php`, `error_log`, `rollback.sh`)

If you add new server-only files in the future (logs, uploaded customer files, generated PDFs), add them to `.deployignore` before they get caught by `--delete`.

## 3. Daily workflow

### 3.1 Adding a feature (no database change)

```bash
git checkout development
git pull
git checkout -b feature/<short-name>

# ... edit code, run tests locally ...

git push -u origin feature/<short-name>
```

Open a PR `feature/<short-name>` → `development` on GitHub. The `Playwright Tests (PR)` check runs automatically. When it goes green, merge.

When you're ready to release, open a PR `development` → `main`. Same check runs. Merge to deploy.

### 3.2 Emergency rollback

If a deploy lands and the site is broken:

```bash
ssh -i ~/.ssh/zone_deploy <zone-user>@<zone-host>
cd <parent-of-webroot>
./rollback.sh <webroot-dirname>
# Answer "y" at the prompt
```

(The specific host/user/path are in your password manager and in GitHub Secrets — do not paste them here.)

The current webroot is moved aside to `<webroot>.broken-<timestamp>` and the most recent backup (`<webroot>.bak-<sha>`) is restored as the active webroot. Site is back in ~10 seconds.

Then fix it properly in git:

```bash
git checkout main
git revert <bad-commit-sha>
git push
```

The pipeline redeploys the reverted state. Your `bak-` snapshots, the live webroot, and `main` are now all in agreement again.

## 4. Database schema changes

The pipeline deploys **code only**. It does not touch the database. Every schema change is a manual, deliberate operation. This section is the procedure to follow.

### 4.1 The two databases on Zone

Both live on the same Zone MySQL server, separated only by name:

- `<dev-db-name>` — what `src/db/laoseis.php` connects to on your laptop. Safe to experiment in.
- `<prod-db-name>` — what `src/db/laoseis.php` connects to *on the production server*. Touch with care.

The CI ephemeral DB (`laoseis_test` inside the runner's MySQL container) is a third, throwaway environment that has no relationship to either Zone DB.

### 4.2 The rule: additive-only when possible

Changes come in two flavors:

| Type | Examples | Safety |
|---|---|---|
| **Additive** | `ADD COLUMN`, `ADD TABLE`, `ADD INDEX`, adding a new trigger | Old code keeps working against the new schema. Code rollback is safe. |
| **Destructive** | `DROP COLUMN`, `DROP TABLE`, `RENAME COLUMN`, narrowing a column type | Old code may break against the new schema. Code rollback may leave you stranded. |

**Default to additive.** When you need to remove or rename, do it in two deploys (see §4.5).

### 4.3 Procedure for an additive schema change

Example: adding a `email_meeldetuletus` column to the `Kalender` table.

**Step 1 — Apply the change to the dev DB**

Either via Zone's phpMyAdmin web UI (Andmebaasid → click dev DB → SQL tab) or via your laptop's MySQL client:

```sql
ALTER TABLE Kalender ADD COLUMN email_meeldetuletus TINYINT(1) NOT NULL DEFAULT 0;
```

**Step 2 — Write code that uses the new column**

Edit PHP files to read/write `email_meeldetuletus`. Run the app locally (`php -S localhost:8000`) and verify it works against the dev DB.

**Step 3 — Regenerate `db/schema.sql`**

CI needs the updated schema so its ephemeral DB knows about the new column. From the project root:

```bash
mysqldump --no-data --routines --triggers --column-statistics=0 \
  -h <dev_host> -u <dev_user> -p <dev_db_name> \
  > db/schema.sql
```

Sanity check (number depends on table count — should match what's currently there or be one higher if you added a table):

```bash
grep -c "CREATE TABLE" db/schema.sql
```

**Step 4 — PR feature → development**

```bash
git add db/schema.sql src/ # or whatever files changed
git commit -m "Add email reminder column to calendar"
git push
```

Open the PR. The `Playwright Tests (PR)` check now uses the new schema. If tests touch the new column behavior, they'll exercise it for real.

**Step 5 — Merge to development.**

**Step 6 — Apply the SAME schema change to the production DB**

**This must happen BEFORE the deploy.** If you deploy code that expects a column the production DB doesn't have, every request to the affected feature errors until the column exists.

In Zone's phpMyAdmin, run the exact same `ALTER TABLE` statement against the prod DB.

**Step 7 — PR development → main, merge**

The auto-deploy fires. New code lands on production. Schema is already there waiting for it.

**Order matters for additive changes:** dev schema → dev code → CI green → prod schema → prod code.

### 4.4 Procedure for a destructive schema change

Example: removing the `vana_veerg` column that nothing uses anymore.

**Destructive changes are split into two deploys** to keep the code-DB contract consistent at every moment.

**Deploy 1 — Code stops using the column. Schema unchanged.**

1. Edit PHP so nothing reads/writes `vana_veerg`. Leave the column alone in both dev and prod DBs.
2. PR → development → main as a normal feature.
3. Auto-deploy ships code that ignores the column. Production still has the column; nothing breaks.

**Wait at least a few days** before doing the second deploy. This gives you time to discover any code path you missed that still uses the column. If something is broken, you can put the code back without touching the schema.

**Deploy 2 — Drop the column.**

1. Take a backup first: `mysqldump <prod_db> > backup-before-drop-vana-veerg-$(date +%F).sql`. Store this somewhere safe outside the production server (download it).
2. Apply to dev DB: `ALTER TABLE Ladu DROP COLUMN vana_veerg;`
3. Regenerate `db/schema.sql` (same `mysqldump` command as §4.3 step 3).
4. PR with just the schema change + the updated schema.sql. CI should still pass (code already doesn't use the column).
5. Merge to development → main.
6. Apply `ALTER TABLE Ladu DROP COLUMN vana_veerg;` to prod DB.
7. Auto-deploy ships the schema-aligned code.

The two-deploy pattern works because at no point does running production code disagree with the production schema.

### 4.5 Renaming a column or table

A rename is a destructive change. Do it as: add new → backfill data → switch code → drop old. Same two-deploy pattern as §4.4, just with an extra step in the middle.

1. Deploy 1: `ALTER TABLE X ADD COLUMN new_name <type>;` then code that writes to BOTH old and new columns, reads from new with fallback to old.
2. Backfill: `UPDATE X SET new_name = old_name WHERE new_name IS NULL;` on prod.
3. Deploy 2: code reads/writes only new_name. Old column still exists, unused.
4. Deploy 3: drop old_name column.

For low-traffic admin systems like this one, you can often collapse steps 1-3 into one deploy and just have a brief maintenance window. Judgement call.

### 4.6 If you need to rollback after a schema change

| Scenario | What to do |
|---|---|
| Code rollback only, additive change was deployed | Safe — old code still works against new schema. Just `./rollback.sh` or git revert. |
| Code rollback after a destructive change | Risky — old code expects columns/tables that no longer exist. Restore the column first using the mysqldump backup from §4.4 step 1, then rollback code. |
| Bad schema change, no code change yet | Apply the inverse SQL on prod. No code rollback needed. |
| Bad schema + bad code | Code rollback first (`./rollback.sh`), then restore DB from the mysqldump backup, then plan the recovery. |

This is the reason §4.4 step 1 (take a backup) is non-negotiable.

### 4.7 What if you forget to regenerate `db/schema.sql`?

CI will catch it. Your tests will pass locally (you have the updated dev DB) but fail in CI (the ephemeral DB only has whatever `db/schema.sql` describes). You'll see errors like `Unknown column 'email_meeldetuletus'` in the test output. Re-run the `mysqldump`, commit, push, the PR check turns green.

### 4.8 What if `mysqldump` can't connect from your laptop?

Zone may require IP whitelisting for external MySQL connections (separate from the SSH IP whitelist). If `mysqldump` times out or hits "host not allowed":

1. Check zone.ee control panel for an "Allowed IPs for MySQL" setting and add your current IP.
2. If that's not an option, SSH into Zone and run `mysqldump` there, then `scp` the file back to your laptop.

## 5. Maintenance

### 5.1 Dependabot

`.github/dependabot.yml` opens a grouped PR weekly for outdated npm devDependencies (mostly Playwright). Treat these like any other PR: wait for the `Playwright Tests (PR)` check, review the changelog if it's a major bump, merge.

These updates have no production impact — they only affect what runs in CI. The `.deployignore` keeps all JS infrastructure out of the production webroot.

### 5.2 Bumping Playwright manually

If you want to update sooner than Dependabot's weekly schedule:

```bash
npm install -D @playwright/test@latest @types/node@latest
git add package.json package-lock.json
git commit -m "chore: bump playwright"
```

Manual bumps don't usually require runner changes. See §6.4 for the one historical exception (Playwright versions ≤1.44 needed a `ubuntu-22.04` pin).

### 5.3 Rotating the deploy SSH key

If you suspect the deploy key has been compromised, or it's good hygiene to rotate every year or two:

1. Generate a new key: `ssh-keygen -t ed25519 -f ~/.ssh/zone_deploy_new -N ""`
2. Add the new public key to Zone's SSH keys panel.
3. Verify it works: `ssh -i ~/.ssh/zone_deploy_new <zone-user>@<zone-host> "echo ok"`
4. Update the `ZONE_SSH_KEY` GitHub Secret with the new private key contents.
5. Trigger a deploy (push a trivial commit or use `workflow_dispatch`) to verify the secret update works.
6. Once verified, remove the old public key from Zone.

### 5.4 Adding new files that the server owns

If the production server starts maintaining files that should never be deleted by `rsync --delete` (uploaded customer logos, generated PDFs, log rotations, etc.), add them to `.deployignore`. Without that, the next deploy will delete them.

Same goes for anything you generate locally for development that shouldn't ship — e.g. the `.claude/` IDE config that leaked through during our initial manual deploy.

## 6. Known quirks (the "we already hit this" list)

Things that bit us during initial setup; capturing them so future-you doesn't re-debug.

### 6.1 `--column-statistics=0` for `mysqldump`

Modern MySQL clients (8.x) query a `COLUMN_STATISTICS` table that doesn't exist on older Zone servers. Always pass `--column-statistics=0` when dumping the Zone DB.

### 6.2 `mkdir -p src/db` before writing the CI laoseis.php

`src/db/laoseis.php` is gitignored, and git doesn't track empty directories. A fresh CI checkout has no `src/db/`, so the workflow has to create it before writing the CI database config. Already handled in both workflows — don't remove the `mkdir -p` line.

### 6.3 Rewriting `DEFINER` clauses on schema import

`mysqldump` preserves the original `DEFINER` on triggers, views, and routines (a `DEFINER=\`<some-zone-db-user>\`@\`<some-old-ip>\`` clause). That user doesn't exist in CI's ephemeral MySQL, so executing the trigger fails with "the user specified as a definer does not exist". The Import step pipes through `sed` to rewrite all DEFINERs to `CURRENT_USER`. Don't remove the sed.

### 6.4 Ubuntu runner / Playwright version coupling

Older Playwright versions (≤1.44) hardcoded Ubuntu 22.04 package names like `libasound2` and `libffi7`. On Ubuntu 24.04 those were renamed (`libasound2t64`, `libffi8`), causing `playwright install --with-deps` to fail. If you ever downgrade Playwright below 1.45 for some reason, also change `runs-on: ubuntu-latest` to `runs-on: ubuntu-22.04` in both workflows. Keep them aligned.

### 6.5 Zone IP whitelisting

Zone supports IP whitelisting for both SSH and external MySQL access. The whitelist breaks GitHub Actions deploys because runner IPs are unpredictable. For this project, the SSH whitelist is currently disabled (the dedicated deploy key + key-only authentication is the real access control). If you ever re-enable IP whitelisting, you'll need to either add the GitHub Actions Azure IP ranges (large and rotating) or use a self-hosted runner.

### 6.6 Host key rotation

When zone.ee rotates a server's SSH host key, your local `~/.ssh/known_hosts` rejects future connections with "REMOTE HOST IDENTIFICATION HAS CHANGED!". Fix locally with:

```bash
ssh-keygen -R <zone-host>
ssh-keyscan -H <zone-host> >> ~/.ssh/known_hosts
```

GitHub Actions re-runs `ssh-keyscan` every deploy, so it picks up host key changes automatically — no maintenance needed on the CI side.

### 6.7 macOS deprecated `ssh-rsa`

OpenSSH 8.8+ on modern macOS disables the legacy `ssh-rsa` (SHA-1) signature algorithm. If you ever use an old personal RSA key to connect to Zone and get a confusing "Permission denied (publickey)" despite identical key fingerprints, add to `~/.ssh/config`:

```
Host <zone-host>
    PubkeyAcceptedAlgorithms +ssh-rsa
    HostkeyAlgorithms +ssh-rsa
```

Best long-term: replace your personal RSA key with an ed25519 one.

## 7. Reference

### Workflow files

- `.github/workflows/deploy.yml` — main pipeline (test + deploy on push to main)
- `.github/workflows/playwright.yml` — PR test workflow (no deploy)

### Other repo files

- `.deployignore` — rsync exclusion list
- `.github/dependabot.yml` — weekly devDependency updates
- `db/schema.sql` — schema dump used by CI to bootstrap its ephemeral DB
- `rollback.sh` — present in repo for source control; copied to server during one-time setup

### On the Zone server (paths anonymized — real values live in GitHub Secrets and the password manager)

- `$ZONE_DEPLOY_PATH/` — production webroot (the value of the `ZONE_DEPLOY_PATH` secret)
- `$ZONE_DEPLOY_PATH.bak-<sha>/` — pre-deploy snapshots (last 3 kept), sit next to the webroot
- `<parent-of-ZONE_DEPLOY_PATH>/rollback.sh` — manual rollback script
- `$ZONE_DEPLOY_PATH/src/db/laoseis.php` — production DB credentials (never touched by deploy)

### Related docs

- [superpowers/specs/2026-05-21-zone-media-deploy-pipeline-design.md](superpowers/specs/2026-05-21-zone-media-deploy-pipeline-design.md) — design rationale
- [superpowers/plans/2026-05-21-zone-media-deploy-pipeline.md](superpowers/plans/2026-05-21-zone-media-deploy-pipeline.md) — implementation plan
