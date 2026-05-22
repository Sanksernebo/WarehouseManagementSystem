# Zone Media Deploy Pipeline Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace manual FileZilla uploads with an automated GitHub Actions pipeline that runs Playwright tests against an isolated MySQL+PHP environment and deploys to Zone Media on every push to `main`.

**Architecture:** Single GitHub Actions workflow (`deploy.yml`) with two sequential jobs — `test` (ephemeral MySQL service container, PHP-CLI server, full Playwright suite) and `deploy` (rsync over SSH with a pre-deploy backup for fast rollback). Existing `playwright.yml` is updated to PR-only triggers with the same ephemeral env so PR feedback is meaningful. Dependabot keeps Playwright current.

**Tech Stack:** GitHub Actions, MySQL 8 (service container), PHP 8 (`shivammathur/setup-php@v2`), Playwright, rsync over SSH (ed25519 deploy key), Dependabot.

**Spec:** [docs/superpowers/specs/2026-05-21-zone-media-deploy-pipeline-design.md](../specs/2026-05-21-zone-media-deploy-pipeline-design.md)

---

## File Structure

**New files in repo:**

| Path | Purpose |
|---|---|
| `.github/workflows/deploy.yml` | Test + deploy workflow, triggered on push to `main` and `workflow_dispatch` |
| `.github/dependabot.yml` | Weekly grouped devDependency updates |
| `.deployignore` | rsync exclusion list — repo-only files never reach the server |
| `db/schema.sql` | MySQL schema dump from Zone dev DB (no data), used by CI to bootstrap test env |
| `rollback.sh` | Script placed on Zone server (not in webroot) for fast rollback |

**Modified files:**

| Path | Change |
|---|---|
| `.github/workflows/playwright.yml` | Triggers change to PR-only on `[main, development]`; add same ephemeral DB setup |

**Files explicitly not touched on the server by deploy:**
- `src/db/laoseis.php` (production DB credentials)
- `error_log` (PHP error log file)

---

## Conventions and tools used in this plan

- **Tasks marked `[USER ACTION]`** require the human to do something outside the agent's control (Zone server, GitHub UI, local shell with secrets). These cannot be automated by the implementation agent and the plan must pause for them.
- **Verification of CI changes** is done by pushing to a feature branch (`feature/deploy-pipeline`) and triggering the workflow via the GitHub Actions UI (`workflow_dispatch`). The `deploy` job is guarded by `if: github.ref == 'refs/heads/main'`, so workflow runs on a feature branch will run tests only — never deploy.
- **All commits are made on a `feature/deploy-pipeline` branch off `development`.** When the work is complete and verified, the branch merges into `development`, then `development` merges into `main`, and the first automated deploy fires.

---

## Task 0: Create feature branch

**Files:** none (git only)

- [ ] **Step 1: Branch off development**

```bash
git checkout development
git pull
git checkout -b feature/deploy-pipeline
```

- [ ] **Step 2: Confirm clean state**

Run: `git status`
Expected: `On branch feature/deploy-pipeline / nothing to commit, working tree clean`

---

## Task 1: Export DB schema from Zone dev DB  [USER ACTION]

**Files:**
- Create: `db/schema.sql`

The CI test job needs the schema to bootstrap an empty MySQL service container. No agent can do this — it requires Zone dev DB credentials that live only on the user's machine / in FileZilla settings.

- [ ] **Step 1: Find the dev DB connection details**

Open `src/db/laoseis.php` in the local checkout. Note the host, user, password, and dev DB name.

- [ ] **Step 2: Export schema with no data**

From the project root, with the values from Step 1:

```bash
mkdir -p db
mysqldump --no-data --routines --triggers \
  -h <dev_host> -u <dev_user> -p <dev_db_name> \
  > db/schema.sql
```

When prompted for the password, enter the dev DB password.

- [ ] **Step 3: Sanity check the dump**

Run: `grep -c "CREATE TABLE" db/schema.sql`
Expected: `7` (the seven tables listed in README: `Ladu`, `Tehtud_tood`, `Rehvi_Ladu`, `Rehvi_myyk`, `Ladu_logi`, `Kalender`, `Login`). If the count is different, double-check the DB connection and re-run.

- [ ] **Step 4: Commit**

```bash
git add db/schema.sql
git commit -m "Add MySQL schema dump for CI test environment"
```

---

## Task 2: Create `.deployignore`

**Files:**
- Create: `.deployignore`

This file is consumed by rsync's `--exclude-from` flag. Anything listed here will not be uploaded to the server, AND will not be deleted from the server if it already exists there. Critical for protecting `src/db/laoseis.php` (which holds production DB credentials and lives only on the server) and `error_log`.

- [ ] **Step 1: Create the file**

Create `.deployignore` in the project root with this exact content:

```
.git/
.github/
.gitignore
.idea/
.deployignore
.DS_Store
node_modules/
tests/
playwright-report/
test-results/
playwright.config.ts
package.json
package-lock.json
README.md
docs/
db/
error_log
src/db/laoseis.php
```

- [ ] **Step 2: Verify with a local dry-run rsync**

This rsync command does NOT touch the server — it only prints what *would* be transferred to a fictional target. We use it to confirm the exclusion list catches the right things.

Run: `rsync -avzn --delete --exclude-from=.deployignore ./ /tmp/wms-dryrun/ 2>&1 | head -40`

Expected output should include lines like `index.php`, `style.css`, `src/login/`, `src/kalender/`, etc. — and should NOT include `node_modules/`, `tests/`, `.git/`, `db/`, `docs/`, `error_log`, or `src/db/laoseis.php`.

- [ ] **Step 3: Commit**

```bash
git add .deployignore
git commit -m "Add .deployignore for rsync deploy exclusion list"
```

---

## Task 3: Create Dependabot config

**Files:**
- Create: `.github/dependabot.yml`

- [ ] **Step 1: Create the file**

Create `.github/dependabot.yml` with this exact content:

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

- [ ] **Step 2: Commit**

```bash
git add .github/dependabot.yml
git commit -m "Add Dependabot config for weekly grouped devDependency updates"
```

Dependabot picks up the config automatically once it lands on the default branch. No further action needed.

---

## Task 4: Generate Zone deploy SSH key and configure GitHub Secrets  [USER ACTION]

The agent cannot generate or upload SSH keys — these are credentials that must stay on the user's machine and in their Zone control panel / GitHub repo settings.

- [ ] **Step 1: Generate a dedicated deploy keypair locally**

```bash
ssh-keygen -t ed25519 -f ~/.ssh/zone_deploy -C "github-actions-deploy" -N ""
```

This creates `~/.ssh/zone_deploy` (private) and `~/.ssh/zone_deploy.pub` (public) with no passphrase (required — GitHub Actions can't enter one).

- [ ] **Step 2: Add the public key to Zone server**

Print the public key:

```bash
cat ~/.ssh/zone_deploy.pub
```

SSH into Zone (using your existing credentials) and append the printed line to `~/.ssh/authorized_keys`:

```bash
ssh <your_zone_user>@<your_zone_host>
# On the server:
mkdir -p ~/.ssh && chmod 700 ~/.ssh
echo 'ssh-ed25519 AAAA... github-actions-deploy' >> ~/.ssh/authorized_keys
chmod 600 ~/.ssh/authorized_keys
exit
```

- [ ] **Step 3: Verify the deploy key works**

From your laptop:

```bash
ssh -i ~/.ssh/zone_deploy <your_zone_user>@<your_zone_host> "echo deploy key works && pwd"
```

Expected: `deploy key works` followed by the server's home directory path. If it fails, recheck `authorized_keys` formatting on the server.

- [ ] **Step 4: Find your absolute web root path**

On Zone, find where files actually live. SSH in and run:

```bash
ssh <your_zone_user>@<your_zone_host> "find ~ -maxdepth 3 -name 'index.php' -path '*htdocs*' 2>/dev/null | head -3"
```

Note the directory containing `index.php` (without the trailing `/index.php`). This is your `ZONE_DEPLOY_PATH`. Example on Zone: `/data01/virt12345/domeenid/www.example.ee/htdocs`.

- [ ] **Step 5: Add four secrets to the GitHub repo**

Go to `https://github.com/Sanksernebo/WarehouseManagementSystem/settings/secrets/actions` and click "New repository secret" for each:

| Name | Value |
|---|---|
| `ZONE_SSH_HOST` | The hostname from Step 3 |
| `ZONE_SSH_USER` | The username from Step 3 |
| `ZONE_SSH_KEY` | The contents of `~/.ssh/zone_deploy` (the *private* key — paste the entire file including `-----BEGIN OPENSSH PRIVATE KEY-----` and `-----END OPENSSH PRIVATE KEY-----` lines) |
| `ZONE_DEPLOY_PATH` | The absolute path from Step 4 |

- [ ] **Step 6: Confirm secrets are saved**

Refresh the secrets page. All four should be listed (values are hidden — that's expected).

---

## Task 5: Create `deploy.yml` skeleton with test job

**Files:**
- Create: `.github/workflows/deploy.yml`

This task builds the full test job. The deploy job is added in Task 7 (after we've verified the test job works in isolation).

- [ ] **Step 1: Create the workflow file**

Create `.github/workflows/deploy.yml` with this exact content:

```yaml
name: Test and Deploy

on:
  push:
    branches: [main]
  workflow_dispatch:

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_ROOT_PASSWORD: testroot
          MYSQL_DATABASE: laoseis_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping -h localhost -uroot -ptestroot"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=10
    steps:
      - uses: actions/checkout@v4

      - uses: actions/setup-node@v4
        with:
          node-version: lts/*
          cache: npm

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mysqli

      - name: Wait for MySQL
        run: |
          for i in {1..30}; do
            if mysqladmin ping -h 127.0.0.1 -uroot -ptestroot --silent 2>/dev/null; then
              echo "MySQL is up"
              exit 0
            fi
            sleep 1
          done
          echo "MySQL did not become ready" >&2
          exit 1

      - name: Import schema
        run: mysql -h 127.0.0.1 -uroot -ptestroot laoseis_test < db/schema.sql

      - name: Write CI database config
        run: |
          cat > src/db/laoseis.php <<'PHP'
          <?php
          $conn = mysqli_connect('127.0.0.1', 'root', 'testroot', 'laoseis_test');
          if (!$conn) { die('Connection failed: ' . mysqli_connect_error()); }
          PHP

      - name: Seed test user
        run: |
          HASH=$(php -r "echo password_hash('testtest', PASSWORD_DEFAULT);")
          mysql -h 127.0.0.1 -uroot -ptestroot laoseis_test -e \
            "INSERT INTO Login (kasutajanimi, parool) VALUES ('TestUser', '$HASH');"

      - name: Start PHP server
        run: |
          php -S localhost:8000 > php.log 2>&1 &
          for i in {1..30}; do
            if curl -sf http://localhost:8000/ -o /dev/null; then
              echo "PHP server is up"
              exit 0
            fi
            sleep 1
          done
          echo "PHP server did not start. Log:" >&2
          cat php.log >&2
          exit 1

      - run: npm ci

      - name: Install Playwright browsers
        run: npx playwright install --with-deps

      - name: Run Playwright tests
        run: npx playwright test

      - uses: actions/upload-artifact@v4
        if: always()
        with:
          name: playwright-report
          path: playwright-report/
          retention-days: 7
```

- [ ] **Step 2: Validate YAML locally (optional, recommended)**

If you have `actionlint` installed (via `brew install actionlint`):

```bash
actionlint .github/workflows/deploy.yml
```

Expected: no output (errors are printed if found). If actionlint is not installed, skip — Step 4 will surface real errors.

- [ ] **Step 3: Commit**

```bash
git add .github/workflows/deploy.yml
git commit -m "Add deploy.yml with ephemeral MySQL+PHP test job"
```

- [ ] **Step 4: Push the branch**

```bash
git push -u origin feature/deploy-pipeline
```

---

## Task 6: Verify test job runs in CI

- [ ] **Step 1: Trigger the workflow manually**

Open `https://github.com/Sanksernebo/WarehouseManagementSystem/actions/workflows/deploy.yml` in the browser. Click **Run workflow**, select `feature/deploy-pipeline`, click the green **Run workflow** button.

- [ ] **Step 2: Watch the run**

Open the running job. Each step should turn green in sequence:
1. Checkout — seconds
2. Setup Node, Setup PHP — ~30 sec
3. Wait for MySQL — usually <10 sec
4. Import schema — <5 sec
5. Write CI config, Seed test user — <5 sec
6. Start PHP server — <10 sec
7. npm ci — ~30 sec (first run, cached after)
8. Install Playwright browsers — ~1 min
9. Run Playwright tests — ~1-3 min

Total expected: ~3-5 minutes.

Expected end state: all green, `playwright-report` artifact uploaded.

- [ ] **Step 3: Triage failures (if any)**

Common failures and fixes (apply on the feature branch and push to re-test):

| Failure | Likely cause | Fix |
|---|---|---|
| `Import schema` fails with "no such file" | `db/schema.sql` wasn't pushed | Verify Task 1 was committed: `git log --oneline -- db/schema.sql` |
| `Seed test user` fails with "Unknown column 'kasutajanimi'" | Schema uses different column names | Open `db/schema.sql`, find the `Login` table, adjust column names in the INSERT step |
| Playwright tests fail to find login form | `localhost:8000` not serving correctly | Check `php.log` artifact, verify `php -S localhost:8000 -t .` is the right command for the repo root |
| Many test failures with "expect ... toBeVisible" | Tests assume seed data that the schema dump didn't include | Either add seed steps for the missing data, or scope down the test suite — but verify these tests pass locally first |

- [ ] **Step 4: After green, no commit needed**

The test job works. Move on.

---

## Task 7: Add deploy job (dry-run first)

**Files:**
- Modify: `.github/workflows/deploy.yml`

We add the deploy job in dry-run mode first. This means rsync prints what it *would* transfer without actually transferring anything. Lets us verify SSH connectivity and exclusion logic without risking the server.

- [ ] **Step 1: Append the deploy job to `deploy.yml`**

Open `.github/workflows/deploy.yml`. After the `test:` job block (after the `upload-artifact` step), add this new top-level job (same indentation level as `test:`):

```yaml
  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
    env:
      SSH_USER: ${{ secrets.ZONE_SSH_USER }}
      SSH_HOST: ${{ secrets.ZONE_SSH_HOST }}
      DEPLOY_PATH: ${{ secrets.ZONE_DEPLOY_PATH }}
    steps:
      - uses: actions/checkout@v4

      - name: Set up SSH
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.ZONE_SSH_KEY }}" > ~/.ssh/id_ed25519
          chmod 600 ~/.ssh/id_ed25519
          ssh-keyscan -H "$SSH_HOST" >> ~/.ssh/known_hosts

      - name: Deploy via rsync (DRY RUN)
        run: |
          rsync -avzn --delete \
            --exclude-from=.deployignore \
            -e "ssh -i ~/.ssh/id_ed25519" \
            ./ "$SSH_USER@$SSH_HOST:$DEPLOY_PATH/"
```

Note the `-n` flag in `rsync -avzn` — that's the dry-run flag. We add it now and remove it in Task 11.

Also note `if: github.event_name == 'push' && github.ref == 'refs/heads/main'` — this guard means the deploy job will NOT run when we trigger the workflow via `workflow_dispatch` on `feature/deploy-pipeline`. To test the deploy job, we temporarily relax this guard in Step 3 below.

- [ ] **Step 2: Commit**

```bash
git add .github/workflows/deploy.yml
git commit -m "Add deploy job with rsync dry-run (verification only)"
```

- [ ] **Step 3: Temporarily allow deploy job to run on this branch for verification**

Edit `.github/workflows/deploy.yml`. Change the `if:` line on the `deploy` job to:

```yaml
    if: github.event_name == 'workflow_dispatch' || (github.event_name == 'push' && github.ref == 'refs/heads/main')
```

Commit (we'll revert this in Task 11):

```bash
git add .github/workflows/deploy.yml
git commit -m "TEMP: allow deploy job on workflow_dispatch for verification"
git push
```

- [ ] **Step 4: Trigger and watch**

Trigger `Run workflow` on `feature/deploy-pipeline` again from the GitHub Actions UI. The `deploy` job should now run after `test` passes.

In the `Deploy via rsync (DRY RUN)` step, expected output ends with something like:

```
sent 234 bytes  received 12345 bytes  ...
total size is 1234567  speedup is 99.99 (DRY RUN)
```

The middle of the output lists every file that *would* be sent. Verify by skimming:
- `index.php`, `style.css`, `src/...` are listed (good — these get deployed)
- `node_modules/`, `tests/`, `db/`, `src/db/laoseis.php`, `error_log`, `.git/` are NOT listed (good — these are excluded)

If you see `src/db/laoseis.php` in the output, the exclusion didn't apply — fix `.deployignore` and re-run.

- [ ] **Step 5: Triage failures**

| Failure | Likely cause | Fix |
|---|---|---|
| `Permission denied (publickey)` | Public key not in server's `authorized_keys`, or wrong key in secret | Re-do Task 4 Step 2; verify `ZONE_SSH_KEY` secret contains the *private* key including header/footer lines |
| `Host key verification failed` | `ssh-keyscan` didn't capture the host | Check `SSH_HOST` value; some hosts use non-standard SSH ports (add `-p <port>` to `ssh-keyscan` and a matching `-e "ssh -p <port>"` to rsync) |
| `rsync: failed to set permissions` | Filesystem quirk on shared hosting | Add `--no-perms --no-owner --no-group` to the rsync flags |

---

## Task 8: Add pre-deploy backup snapshot

**Files:**
- Modify: `.github/workflows/deploy.yml`

Before rsync overwrites the server, snapshot the current webroot. Keeps the last 3 snapshots for Layer 1 rollback.

- [ ] **Step 1: Insert backup step before the rsync step**

In `.github/workflows/deploy.yml`, in the `deploy:` job, add this step *between* the `Set up SSH` step and the `Deploy via rsync (DRY RUN)` step:

```yaml
      - name: Snapshot current webroot
        env:
          SHA: ${{ github.sha }}
        run: |
          ssh -i ~/.ssh/id_ed25519 "$SSH_USER@$SSH_HOST" \
            "DEPLOY='$DEPLOY_PATH' SHA='$SHA' bash -s" <<'REMOTE'
          set -e
          PARENT="$(dirname "$DEPLOY")"
          BASENAME="$(basename "$DEPLOY")"
          cd "$PARENT"
          if [ -d "$BASENAME" ]; then
            cp -a "$BASENAME" "${BASENAME}.bak-${SHA}"
            ls -1dt "${BASENAME}".bak-* 2>/dev/null | tail -n +4 | xargs -r rm -rf
            echo "Snapshot created: ${BASENAME}.bak-${SHA}"
          else
            echo "No existing webroot at $DEPLOY — skipping snapshot (first deploy)"
          fi
          REMOTE
```

The heredoc is single-quoted (`'REMOTE'`) so local `$VAR` expansion does NOT happen — variables are interpreted by the remote bash. We pass `DEPLOY` and `SHA` to the remote shell via `bash -s` env prefix.

- [ ] **Step 2: Commit and push**

```bash
git add .github/workflows/deploy.yml
git commit -m "Add pre-deploy backup snapshot with 3-backup retention"
git push
```

- [ ] **Step 3: Trigger and verify**

Trigger the workflow via `workflow_dispatch` on `feature/deploy-pipeline`.

In the `Snapshot current webroot` step, expected output: `Snapshot created: htdocs.bak-<sha>` (or `No existing webroot ... first deploy` if the path doesn't exist yet — unlikely since the user has been using Zone manually).

- [ ] **Step 4: Verify on server**

SSH in manually and check:

```bash
ssh <your_zone_user>@<your_zone_host> "ls -1dt $(dirname '<ZONE_DEPLOY_PATH>')/$(basename '<ZONE_DEPLOY_PATH>').bak-* 2>/dev/null"
```

Expected: one or more `.bak-<sha>` directories. The snapshot is real.

---

## Task 9: One-time manual deploy of `main` to align server  [USER ACTION]

The user's `main` branch contains refactored code that has never been deployed. Running automated deploy as the *first* sync against a divergent server is risky. Do one careful manual sync first.

- [ ] **Step 1: Decide method**

Two options, pick whichever feels safer:

**Option A — Use FileZilla as you always have.** Compare local checkout of `main` against server, upload changed files, take your time, verify the site after.

**Option B — Use rsync locally with your personal SSH key, same flags as the workflow.** Faster but commits you to the rsync model immediately.

For Option B:

```bash
git checkout main
git pull
rsync -avzn --delete \
  --exclude-from=.deployignore \
  -e "ssh -i ~/.ssh/zone_deploy" \
  ./ <your_zone_user>@<your_zone_host>:<ZONE_DEPLOY_PATH>/
```

Note the `-n` — that's a DRY RUN. Inspect the output carefully. If it looks correct, re-run *without* the `-n`.

- [ ] **Step 2: Verify the site after**

Open the production site in a browser. Log in. Click through each module (Laoseis, Tehtud Tööd, Rehvid Laos, etc.). If anything is broken, do not proceed — investigate and fix before turning on automation.

- [ ] **Step 3: Return to the feature branch**

```bash
git checkout feature/deploy-pipeline
```

---

## Task 10: Create `rollback.sh` and place it on the server  [USER ACTION]

**Files:**
- Create: `rollback.sh` (in repo, but also placed manually on server)

This script lives next to the webroot on the server, NOT inside it. It's invoked manually when something breaks.

- [ ] **Step 1: Create the script in the repo**

Create `rollback.sh` in the project root with this exact content:

```bash
#!/usr/bin/env bash
# Roll back the webroot to the most recent backup snapshot.
# Run this on the Zone server from the directory ABOVE the webroot.
#
# Usage: ./rollback.sh [target_webroot_name]
#   target_webroot_name defaults to "htdocs"

set -euo pipefail

WEBROOT="${1:-htdocs}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

if [ ! -d "$WEBROOT" ]; then
  echo "Error: no directory named '$WEBROOT' in $(pwd)" >&2
  exit 1
fi

LATEST_BACKUP="$(ls -1dt "${WEBROOT}".bak-* 2>/dev/null | head -n 1 || true)"

if [ -z "$LATEST_BACKUP" ]; then
  echo "Error: no backups found matching ${WEBROOT}.bak-*" >&2
  exit 1
fi

echo "Will roll back:"
echo "  Current: $WEBROOT"
echo "  Restore: $LATEST_BACKUP"
echo "  (current will be moved aside to ${WEBROOT}.broken-${TIMESTAMP})"
read -rp "Proceed? [y/N] " ans
[ "$ans" = "y" ] || [ "$ans" = "Y" ] || { echo "Cancelled."; exit 0; }

mv "$WEBROOT" "${WEBROOT}.broken-${TIMESTAMP}"
mv "$LATEST_BACKUP" "$WEBROOT"

echo "Rollback complete."
echo "Broken version preserved at: ${WEBROOT}.broken-${TIMESTAMP}"
```

- [ ] **Step 2: Make it executable and commit**

```bash
chmod +x rollback.sh
git add rollback.sh
git commit -m "Add rollback.sh for Layer 1 server-side rollback"
```

Note: `rollback.sh` is intentionally NOT in `.deployignore`'s exclusion list — but it will end up *inside* the webroot if deployed. We want it *next to* the webroot, not inside it. Add it to `.deployignore`:

- [ ] **Step 3: Exclude rollback.sh from deploy**

Open `.deployignore`. Add `rollback.sh` to the list (e.g. after `error_log`):

```
error_log
src/db/laoseis.php
rollback.sh
```

Commit:

```bash
git add .deployignore
git commit -m "Exclude rollback.sh from rsync (lives outside webroot on server)"
```

- [ ] **Step 4: Upload rollback.sh to server**

SCP it to the parent of your webroot:

```bash
scp -i ~/.ssh/zone_deploy rollback.sh \
  <your_zone_user>@<your_zone_host>:$(dirname '<ZONE_DEPLOY_PATH>')/rollback.sh
```

Verify:

```bash
ssh <your_zone_user>@<your_zone_host> "ls -la $(dirname '<ZONE_DEPLOY_PATH>')/rollback.sh"
```

Expected: file exists, mode `-rwxr-xr-x` or similar (executable).

- [ ] **Step 5: Smoke-test rollback (DO NOT actually roll back yet)**

Run rollback.sh in interactive mode but cancel at the prompt:

```bash
ssh <your_zone_user>@<your_zone_host>
cd <parent_of_webroot>
./rollback.sh htdocs
# When prompted "Proceed? [y/N]", type N and press Enter
```

Expected: script lists "Current" and "Restore" paths, asks for confirmation, exits cleanly when you say N. This confirms it found a backup and can identify the right paths. The actual rollback would have worked.

---

## Task 11: Switch deploy to real (remove dry-run), restore deploy guard

**Files:**
- Modify: `.github/workflows/deploy.yml`

Time to make the deploy actually deploy.

- [ ] **Step 1: Remove `-n` from the rsync command**

In `.github/workflows/deploy.yml`, in the `deploy:` job, find the rsync step. Change:

```yaml
      - name: Deploy via rsync (DRY RUN)
        run: |
          rsync -avzn --delete \
```

to:

```yaml
      - name: Deploy via rsync
        run: |
          rsync -avz --delete \
```

- [ ] **Step 2: Restore strict deploy guard**

In the same file, on the `deploy:` job, change the `if:` line back to:

```yaml
    if: github.event_name == 'push' && github.ref == 'refs/heads/main'
```

This re-locks the deploy job so it only runs on a real push to `main`. Verification of the full flow happens in Task 13.

- [ ] **Step 3: Commit and push**

```bash
git add .github/workflows/deploy.yml
git commit -m "Switch deploy to real rsync and restore main-only guard"
git push
```

- [ ] **Step 4: Run test job one more time to confirm nothing broke**

Trigger `workflow_dispatch` on `feature/deploy-pipeline`. Only the `test` job should run (deploy is now guarded). Expected: test job green, deploy job skipped.

---

## Task 12: Update `playwright.yml` to PR-only with full env

**Files:**
- Modify: `.github/workflows/playwright.yml`

Currently `playwright.yml` triggers on push to `[main, master]` and PRs to `[main, master]`. After this task, it only runs on PRs into `[main, development]` and uses the same ephemeral DB setup as `deploy.yml` so tests actually pass.

- [ ] **Step 1: Replace `.github/workflows/playwright.yml` with this content**

```yaml
name: Playwright Tests (PR)

on:
  pull_request:
    branches: [main, development]

jobs:
  test:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8
        env:
          MYSQL_ROOT_PASSWORD: testroot
          MYSQL_DATABASE: laoseis_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping -h localhost -uroot -ptestroot"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=10
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: lts/*
          cache: npm
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: mysqli
      - name: Wait for MySQL
        run: |
          for i in {1..30}; do
            if mysqladmin ping -h 127.0.0.1 -uroot -ptestroot --silent 2>/dev/null; then exit 0; fi
            sleep 1
          done
          exit 1
      - name: Import schema
        run: mysql -h 127.0.0.1 -uroot -ptestroot laoseis_test < db/schema.sql
      - name: Write CI database config
        run: |
          cat > src/db/laoseis.php <<'PHP'
          <?php
          $conn = mysqli_connect('127.0.0.1', 'root', 'testroot', 'laoseis_test');
          if (!$conn) { die('Connection failed: ' . mysqli_connect_error()); }
          PHP
      - name: Seed test user
        run: |
          HASH=$(php -r "echo password_hash('testtest', PASSWORD_DEFAULT);")
          mysql -h 127.0.0.1 -uroot -ptestroot laoseis_test -e \
            "INSERT INTO Login (kasutajanimi, parool) VALUES ('TestUser', '$HASH');"
      - name: Start PHP server
        run: |
          php -S localhost:8000 > php.log 2>&1 &
          for i in {1..30}; do
            if curl -sf http://localhost:8000/ -o /dev/null; then exit 0; fi
            sleep 1
          done
          cat php.log >&2
          exit 1
      - run: npm ci
      - run: npx playwright install --with-deps
      - run: npx playwright test
      - uses: actions/upload-artifact@v4
        if: always()
        with:
          name: playwright-report
          path: playwright-report/
          retention-days: 7
```

This duplicates the test setup from `deploy.yml` rather than extracting a reusable workflow. For a 2-workflow setup with a small surface area, this is simpler than the abstraction. If a third workflow ever needs the same setup, extract a composite action then.

- [ ] **Step 2: Commit and push**

```bash
git add .github/workflows/playwright.yml
git commit -m "Update playwright.yml to PR-only with ephemeral DB setup"
git push
```

- [ ] **Step 3: Verify PR workflow fires**

Open a PR from `feature/deploy-pipeline` into `development`. The `Playwright Tests (PR)` workflow should automatically trigger. Watch it pass.

Do NOT merge the PR yet — Task 13 verifies end-to-end before merge.

---

## Task 13: End-to-end verification with merge to `main`

This is the moment of truth. We merge the feature work, then push to main, and watch the real deploy happen.

- [ ] **Step 1: Verify the PR into `development` is green**

The PR opened in Task 12 Step 3 should be all green (Playwright Tests passing). Merge it into `development`.

```bash
# Either merge via GitHub UI or:
git checkout development
git merge --no-ff feature/deploy-pipeline
git push
```

- [ ] **Step 2: Open a PR from `development` to `main`**

Via GitHub UI or:

```bash
gh pr create --base main --head development \
  --title "Add CI/CD pipeline" \
  --body "Implements deploy.yml, .deployignore, dependabot.yml, rollback.sh per spec."
```

The PR triggers `playwright.yml` (Playwright Tests). Wait for green.

- [ ] **Step 3: Merge to `main`**

Merge the PR. This pushes to `main` and fires `deploy.yml`.

- [ ] **Step 4: Watch the deploy run**

Open the Actions tab. The `Test and Deploy` workflow should be running:
1. `test` job — ~3-5 min
2. `deploy` job — ~30 sec to 2 min depending on how much rsync needs to transfer

Expected: both green.

- [ ] **Step 5: Verify on server**

SSH in:

```bash
ssh <your_zone_user>@<your_zone_host>
cd <parent_of_webroot>
ls -1dt htdocs.bak-* | head -5
```

Expected: at least one `htdocs.bak-<sha>` snapshot present, matching the commit SHA of the merge to main.

Open the production site in a browser. Verify everything still works:
- Log in
- Each module loads (Laoseis, Tehtud Tööd, Rehvid Laos, Müüdud Rehvid, Müüdud Tooted, Töögraafik)
- A PDF export still works

- [ ] **Step 6: Test rollback works**

This is the safety net validation. From the server, simulate a rollback:

```bash
cd <parent_of_webroot>
./rollback.sh htdocs
# Answer Y this time
```

Expected: current webroot moved to `htdocs.broken-<timestamp>`, latest backup restored as `htdocs`. Reload the site in browser — it should still work (you've rolled back to the same state, essentially).

Then re-deploy by triggering the workflow manually (workflow_dispatch on `main`) to restore the latest state, or just leave the rolled-back version in place and confirm site works.

Actually, to keep state clean: undo the rollback by swapping back:

```bash
mv htdocs htdocs.tmp
mv htdocs.broken-<timestamp> htdocs
rm -rf htdocs.tmp
# Or: re-run deploy via workflow_dispatch on main from GitHub UI
```

- [ ] **Step 7: Done**

The pipeline is live. From here on, every merge into `main` deploys automatically. Manual FileZilla uploads are no longer needed (but remain as Layer 3 fallback).

---

## Self-review summary

This plan covers every section of the spec:

| Spec section | Covered by |
|---|---|
| Goal / architecture | Tasks 5, 7, 8 |
| CI test environment | Tasks 1, 5, 6 |
| Deploy job (rsync, secrets, backup) | Tasks 4, 7, 8, 11 |
| `.deployignore` exclusion list | Task 2 |
| Rollback Layer 1 (server backups + script) | Tasks 8, 10 |
| Rollback Layer 2 (git revert) | Documented in spec; no implementation task — works because of Tasks 5, 11 |
| Rollback Layer 3 (FileZilla fallback) | No code; remains user capability |
| Dependency maintenance (Dependabot) | Task 3 |
| Schema dump prerequisite | Task 1 |
| One-time manual deploy of main | Task 9 |
| Two-workflow structure (PR + deploy) | Task 12 |
| Rollout plan ordering | Task numbering follows spec's rollout order |
