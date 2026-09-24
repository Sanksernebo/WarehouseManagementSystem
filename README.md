# Warehouse Management System

A web-based management system built for **Rõngu Auto OÜ**, an auto repair shop in Estonia. Handles inventory, work orders, tire storage, sales logging, and appointment scheduling — all in one place.

---

## Features

| Module | Description |
|--------|-------------|
| **Laoseis** | Product inventory — add, edit, delete parts and supplies |
| **Tehtud Tööd** | Work order log — add and edit completed jobs per vehicle with odometer and date |
| **Rehvid Laos** | Tire storage — add and edit customer tires stored on-site by season |
| **Müüdud Rehvid** | Tire sales log — add and edit tire sales with size, brand, and supplier |
| **Müüdud Tooted** | Product sales log — view items sold out of inventory |
| **Töögraafik** | Appointment calendar — monthly view with time-slot availability and booking management |
| **PDF Export** | Generate printable work order PDFs for any completed job |
| **Kasutajad** | Logged-in users can create new user accounts |

### Search and pagination

The list pages (Laoseis, Tehtud Tööd, Rehvid Laos, Müüdud Rehvid) use a shared server-side search bar:

- The page initially shows the 50 most recent rows.
- Typing in the search bar queries that page's `search.php` (debounced) and replaces the table contents.
- A **Laadi veel** button below the table loads the next 50 rows for the current search, and hides itself when there are no more.

The shared pieces live in `src/includes/searchbar_init.php` (markup + setup) and `src/includes/searchbar.js` (client logic). Each module provides its own `search.php` (JSON endpoint) and `_row.php` (row template used by both the initial render and search results).

## Tech Stack

- **Backend:** PHP 8+ with MySQLi (prepared statements throughout)
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, Vanilla JavaScript
- **PDF Generation:** [TCPDF](https://tcpdf.org/) (bundled in `src/TCPDF/`)
- **Auth:** PHP session-based login with `password_hash` / `password_verify`
- **Testing:** Playwright (end-to-end)
- **CI/CD:** GitHub Actions — tests on every PR, automatic deploy to Zone Media on merge to `main`

## Project Structure

```
/
├── index.php                   # Inventory overview (home page)
├── style.css
├── db/
│   └── schema.sql              # Schema dump (used by CI to build its test DB)
├── docs/
│   └── pipeline.md             # CI/CD and database change procedures
├── tests/                      # Playwright end-to-end tests
├── playwright.config.ts
├── rollback.sh                 # Server-side rollback script for deploys
├── .deployignore               # Files excluded from the deploy rsync
├── .github/
│   ├── workflows/
│   │   ├── playwright.yml      # Tests on PRs into main / development
│   │   └── deploy.yml          # Test + deploy on push to main
│   └── dependabot.yml
└── src/
    ├── includes/               # Shared components
    │   ├── nav.php             # Navigation bar (for src/* pages)
    │   ├── nav_root.php        # Navigation bar (for root index.php)
    │   ├── footer.php          # Footer
    │   ├── csrf.php            # CSRF token helpers
    │   ├── searchbar_init.php  # Search bar markup + setup
    │   └── searchbar.js        # Search bar / "Laadi veel" client logic
    ├── login/                  # Login, logout, create user
    ├── db/                     # Database connection (laoseis.php, gitignored)
    ├── lisa_lattu/             # Add inventory items
    ├── avaleht_nupud/          # Edit / delete / search inventory items
    ├── tehtud_tood/            # Work orders
    ├── myydud_tooted/          # Product sales log
    ├── rehv_ladu/              # Tire storage
    ├── rehv_myyk/              # Tire sales
    ├── kalender/               # Appointment calendar
    ├── pdf_generaator/         # PDF work order export
    ├── fonts/  img/            # Lato font, logos
    └── TCPDF/                  # PDF library
```

## Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/Sanksernebo/WarehouseManagementSystem.git
   cd WarehouseManagementSystem
   ```

2. **Configure the database connection**

   Create `src/db/laoseis.php` (it is gitignored — never commit credentials):
   ```php
   <?php
   $conn = mysqli_connect('localhost', 'db_user', 'db_password', 'db_name');
   if (!$conn) {
       die('Connection failed: ' . mysqli_connect_error());
   }
   ```

3. **Import the database schema**

   ```bash
   mysql -u db_user -p db_name < db/schema.sql
   ```
   This creates the tables `Kalender`, `Ladu`, `Ladu_lisatud`, `Ladu_logi`, `Login`, `Rehvi_Ladu`, `Rehvi_myyk` and `Tehtud_tood`, plus their triggers. If the import fails with a `DEFINER` error, rewrite the definers first:
   ```bash
   sed -E 's/DEFINER=`[^`]*`@`[^`]*`/DEFINER=CURRENT_USER/g' db/schema.sql | mysql -u db_user -p db_name
   ```

4. **Create the first user account**

   The in-app user creation page (`src/login/loo_kasutaja.php`) requires being logged in, so insert the first user directly:
   ```bash
   php -r "echo password_hash('your_password', PASSWORD_DEFAULT), PHP_EOL;"
   ```
   ```sql
   INSERT INTO Login (kasutajanimi, parool) VALUES ('username', '<hash from above>');
   ```

5. **Serve with PHP**
   ```bash
   php -S localhost:8000
   ```
   Then open `http://localhost:8000` in your browser.

## Testing

End-to-end tests are written with Playwright and run against a live local server.

1. Make sure the app is running on `http://localhost:8000` (see Setup).
2. Create the test user the tests log in with: username `TestUser`, password `testtest` (same `INSERT` as in Setup step 4).
3. Install dependencies and browsers:
   ```bash
   npm ci
   npx playwright install
   ```
4. Run the tests:
   ```bash
   npx playwright test
   ```

Tests create their own data and use unique values, so they can run against a shared dev database. The PHP built-in server is single-threaded, so the config uses one worker; for faster runs start the server with `PHP_CLI_SERVER_WORKERS=4 php -S localhost:8000` and pass `--workers=4`.

## Development Workflow and Deployment

Branching model: `feature/*` → `development` → `main`.

- Branch off `development`, push the branch, and open a PR into `development`. The **Playwright Tests (PR)** workflow runs the full test suite against a throwaway MySQL database built from `db/schema.sql`.
- To release, open a PR from `development` into `main`. Merging it triggers the **Test and Deploy** workflow, which reruns the tests, snapshots the live webroot on Zone Media, and rsyncs the code over SSH (excluding everything in `.deployignore`).
- If a deploy breaks the site, run `rollback.sh` on the server to restore the latest snapshot, then `git revert` the bad commit.

Deploys ship **code only**. Database schema changes are applied manually to the dev and production databases, and `db/schema.sql` must be regenerated so CI knows about them. See [docs/pipeline.md](docs/pipeline.md) for the full procedure, GitHub Secrets, rollback steps, and known quirks.

## Security

- All database queries use prepared statements (no raw SQL interpolation)
- Passwords hashed with `password_hash()` / verified with `password_verify()`
- CSRF tokens on every POST form
- Output escaped with `htmlspecialchars()` throughout
- Session-based authentication guards all pages and search endpoints (unauthenticated `search.php` requests return `401`)
- Database credentials live only in the gitignored `src/db/laoseis.php` and are never deployed from the repo

## License

Private project — all rights reserved.
