# Deployment pipeline — final setup steps (user actions)

The code side of the CI/CD pipeline is implemented and committed on branch `feature/deploy-pipeline`. This document covers everything that **only you** can do to finish wiring it up — actions that require Zone server credentials, your local SSH keys, the GitHub UI, or your judgment about the live site.

When all six steps below are complete, every merge into `main` will automatically test the code in an isolated environment and deploy to Zone Media.

**Estimated time: ~30-45 minutes** (most of which is waiting on CI runs).

---

## Step 1 — Export the MySQL schema from the Zone dev DB

CI needs a copy of your database structure to bootstrap an empty MySQL instance for tests. The dump contains schema only — no data.

1. Open `src/db/laoseis.php` locally. Note the host, user, password, and dev DB name.

2. From the repo root, run:

   ```bash
   mkdir -p db
   mysqldump --no-data --routines --triggers \
     -h <dev_host> -u <dev_user> -p <dev_db_name> \
     > db/schema.sql
   ```

   Enter the dev DB password when prompted.

3. Sanity check — should print `7`:

   ```bash
   grep -c "CREATE TABLE" db/schema.sql
   ```

4. Commit on the feature branch:

   ```bash
   git checkout feature/deploy-pipeline
   git add db/schema.sql
   git commit -m "Add MySQL schema dump for CI test environment"
   git push
   ```

> **Maintenance:** any time you change the schema in production going forward, re-run this `mysqldump` command and commit the updated `db/schema.sql` in the same PR as your code change. CI imports it on every run.

---

## Step 2 — Generate a dedicated SSH deploy key

GitHub Actions needs a way to authenticate to Zone. Generate a fresh keypair just for this purpose — **do not reuse your personal SSH key**.

1. On your laptop:

   ```bash
   ssh-keygen -t ed25519 -f ~/.ssh/zone_deploy -C "github-actions-deploy" -N ""
   ```

   This creates `~/.ssh/zone_deploy` (private) and `~/.ssh/zone_deploy.pub` (public) with no passphrase (required — Actions can't enter one).

2. Print the public key:

   ```bash
   cat ~/.ssh/zone_deploy.pub
   ```

3. SSH into Zone with your existing credentials and append the public key:

   ```bash
   ssh <your_zone_user>@<your_zone_host>
   # On the server:
   mkdir -p ~/.ssh && chmod 700 ~/.ssh
   echo 'ssh-ed25519 AAAA... github-actions-deploy' >> ~/.ssh/authorized_keys
   chmod 600 ~/.ssh/authorized_keys
   exit
   ```

   (Replace the `AAAA...` line with the actual public key from step 2.)

4. Verify the new key works:

   ```bash
   ssh -i ~/.ssh/zone_deploy <your_zone_user>@<your_zone_host> "echo deploy key works && pwd"
   ```

   Expected: `deploy key works` followed by the server's home directory path.

---

## Step 3 — Find your absolute web root path on Zone

Use the deploy key you just verified:

```bash
ssh -i ~/.ssh/zone_deploy <your_zone_user>@<your_zone_host> \
  "find ~ -maxdepth 4 -name 'index.php' -path '*htdocs*' 2>/dev/null | head -3"
```

Note the directory containing `index.php` (without the trailing `/index.php`). Example: `/data01/virt12345/domeenid/www.example.ee/htdocs`. This is your `ZONE_DEPLOY_PATH`.

---

## Step 4 — Add four secrets to the GitHub repo

Open `https://github.com/Sanksernebo/WarehouseManagementSystem/settings/secrets/actions`. Click **New repository secret** four times:

| Secret name | Value |
|---|---|
| `ZONE_SSH_HOST` | Hostname from Step 2 (e.g. `username.zonevs.eu`) |
| `ZONE_SSH_USER` | Username from Step 2 |
| `ZONE_SSH_KEY` | Full contents of `~/.ssh/zone_deploy` — the **private** key. Include `-----BEGIN OPENSSH PRIVATE KEY-----` and `-----END OPENSSH PRIVATE KEY-----` lines. |
| `ZONE_DEPLOY_PATH` | Absolute path from Step 3 |

To print the private key on your laptop:

```bash
cat ~/.ssh/zone_deploy
```

After saving, all four should appear in the secrets list (values hidden — that's expected).

---

## Step 5 — Verify the CI test job actually runs

Before doing the real deploy, prove the test pipeline works end-to-end in CI.

1. Open `https://github.com/Sanksernebo/WarehouseManagementSystem/actions/workflows/deploy.yml`.

2. Click **Run workflow** (top-right), select branch `feature/deploy-pipeline`, click the green **Run workflow** button.

3. Wait ~3-5 minutes. Watch the `test` job:
   - Checkout, Setup Node, Setup PHP ✓
   - Wait for MySQL ✓
   - Import schema ✓
   - Write CI config, Seed test user ✓
   - Start PHP server ✓
   - npm ci, Install browsers, Run Playwright tests ✓

   The `deploy` job will be **skipped** (correct — the workflow guard only allows deploy on push to `main`).

4. If the test job fails:

   | Failure | Fix |
   |---|---|
   | `Import schema` — "no such file" | `db/schema.sql` wasn't pushed. Re-do Step 1. |
   | `Seed test user` — "Unknown column" | Schema dump uses different Login column names. Edit `.github/workflows/deploy.yml` Seed step to match your actual column names. |
   | Playwright tests fail | Inspect the uploaded `playwright-report` artifact. The most likely cause is missing seed data the tests depend on. |

5. When green, the test job is proven. Move on.

---

## Step 6 — One-time manual deploy of `main` to align the server

Your `main` branch currently contains refactored code that's never been deployed. Don't let the first automated deploy be the one that ships this — do it manually first, watching carefully, so you can investigate and fix any breakage in calm.

**Option A — FileZilla (familiar):** sync your local `main` checkout to the server as you always have. Verify the site after.

**Option B — Local rsync with the new deploy key:**

```bash
git checkout main
git pull
rsync -avzn --delete \
  --exclude-from=.deployignore \
  -e "ssh -i ~/.ssh/zone_deploy" \
  ./ <your_zone_user>@<your_zone_host>:<ZONE_DEPLOY_PATH>/
```

The `-n` flag is **dry run** — read the output carefully. If it looks right (no surprising deletions, no `src/db/laoseis.php` in the transfer list), re-run **without** `-n` to actually deploy:

```bash
rsync -avz --delete \
  --exclude-from=.deployignore \
  -e "ssh -i ~/.ssh/zone_deploy" \
  ./ <your_zone_user>@<your_zone_host>:<ZONE_DEPLOY_PATH>/
```

**After deploy:** open the production site in a browser. Log in. Click through every module (Laoseis, Tehtud Tööd, Rehvid Laos, Müüdud Rehvid, Müüdud Tooted, Töögraafik). Export a PDF. **Do not proceed if anything is broken** — investigate and fix first.

Return to the feature branch when done:

```bash
git checkout feature/deploy-pipeline
```

---

## Step 7 — Upload `rollback.sh` to the server

The script lives **next to** the webroot on the server, not inside it. From your laptop:

```bash
scp -i ~/.ssh/zone_deploy rollback.sh \
  <your_zone_user>@<your_zone_host>:$(dirname '<ZONE_DEPLOY_PATH>')/rollback.sh
```

Replace `<ZONE_DEPLOY_PATH>` with your actual path. Example: if `ZONE_DEPLOY_PATH=/data01/virt12345/domeenid/www.example.ee/htdocs`, then the script goes to `/data01/virt12345/domeenid/www.example.ee/rollback.sh`.

Smoke-test it (cancel at the prompt — don't actually roll back yet):

```bash
ssh -i ~/.ssh/zone_deploy <your_zone_user>@<your_zone_host>
cd <parent_of_webroot>
./rollback.sh htdocs
# When prompted "Proceed? [y/N]", type N and press Enter
```

Expected: lists "Current" and "Restore" paths (the Restore will say "no backups found" if you haven't done any pipeline-driven deploys yet — that's expected and fine for now; the first automated deploy will create the first backup).

---

## Step 8 — Merge to `main` and watch the first automated deploy

Everything is now in place. Time to ship.

1. Merge feature branch → development via PR (this triggers the new `Playwright Tests (PR)` workflow):

   ```bash
   gh pr create --base development --head feature/deploy-pipeline \
     --title "Add CI/CD pipeline" \
     --body "See docs/superpowers/specs/2026-05-21-zone-media-deploy-pipeline-design.md"
   ```

   Wait for the PR's Playwright check to go green. Merge.

2. Open a PR from `development` to `main`:

   ```bash
   gh pr create --base main --head development \
     --title "Deploy CI/CD pipeline to production" \
     --body "Includes all CI/CD infrastructure plus accumulated refactor work."
   ```

   Wait for green. Merge.

3. The merge triggers `Test and Deploy` on `main`. Watch it in the Actions tab:
   - `test` job runs (~3-5 min)
   - `deploy` job runs (~30 sec to 2 min)

4. Verify on server. SSH in:

   ```bash
   ssh -i ~/.ssh/zone_deploy <your_zone_user>@<your_zone_host>
   cd <parent_of_webroot>
   ls -1dt htdocs.bak-* | head -3
   ```

   Expected: at least one `htdocs.bak-<sha>` snapshot, matching the merge commit's SHA. The pipeline is working.

5. Open the production site. Verify it still works exactly as it did after Step 6.

---

## You're done

From now on:

- **Feature work:** branch off `development`, push, open PR into `development`. The Playwright tests run on the PR automatically.
- **Releases:** merge `development` into `main`. The deploy pipeline takes care of the rest.
- **If something breaks in production:** SSH in, run `./rollback.sh htdocs` from the parent directory. Site restored in ~10 seconds. Then `git revert <bad-commit>` on `main` and push — the pipeline redeploys the clean state.
- **Manual FileZilla** still works as a last resort if GitHub Actions itself is down.

## Reference

- Design spec: [docs/superpowers/specs/2026-05-21-zone-media-deploy-pipeline-design.md](superpowers/specs/2026-05-21-zone-media-deploy-pipeline-design.md)
- Implementation plan: [docs/superpowers/plans/2026-05-21-zone-media-deploy-pipeline.md](superpowers/plans/2026-05-21-zone-media-deploy-pipeline.md)
- Workflow files: `.github/workflows/deploy.yml`, `.github/workflows/playwright.yml`
- Exclusion list: `.deployignore`
- Rollback script: `rollback.sh` (in repo; also lives next to webroot on server)
- Dependabot config: `.github/dependabot.yml`
