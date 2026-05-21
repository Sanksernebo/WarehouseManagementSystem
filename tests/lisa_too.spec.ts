import { test, expect, Page } from '@playwright/test';
import { BASE_URL, ensureLoggedIn } from './helpers';

// RegNr column in DB is VARCHAR(10) — keep all identifiers ≤ 10 chars.
// Use last 6 digits of timestamp (000000–999999) so each test run gets a
// unique RegNr without exceeding the column limit.
const RUN_ID = (Date.now() % 1000000).toString().padStart(6, '0');

// Submit via the named submit button so PHP's isset($_POST['submit']) check
// passes, while also bypassing any fixed-footer pointer interception.
async function submitForm(page: Page) {
    await page.locator('form').evaluate((form: HTMLFormElement) => {
        const btn = form.querySelector<HTMLInputElement>('input[type="submit"]');
        if (btn) form.requestSubmit(btn);
        else form.requestSubmit();
    });
}

async function createJob(page: Page, regNr: string, description: string) {
    await page.goto(`${BASE_URL}/src/tehtud_tood/lisa_too.php`, { waitUntil: 'load' });
    await page.fill('input[name="RegNr"]', regNr);
    await page.fill('input[name="Kuupaev"]', '2030-01-15T10:00');
    await page.fill('input[name="Odomeeter"]', '100000');
    await page.fill('textarea[name="Tehtud_tood"]', description);
    await Promise.all([
        page.waitForURL(/tehtud_tood\.php/, { waitUntil: 'load' }),
        submitForm(page),
    ]);
}

test.describe('Tehtud Tööd testid', () => {
    test.beforeEach(async ({ page }) => {
        await ensureLoggedIn(page);
    });

    test('search bar filters jobs by registration number', async ({ page }, testInfo) => {
        // SR prefix + 6-digit run ID + worker index = 9 chars (fits VARCHAR(10))
        const regNr = `SR${RUN_ID}${testInfo.workerIndex}`;
        // Create two jobs with the same RegNr to verify multiple-row filtering
        await createJob(page, regNr, 'Otsingu test 1');
        await createJob(page, regNr, 'Otsingu test 2');

        // After second createJob we are already on tehtud_tood.php
        await page.fill('#searchBar', regNr);
        await page.locator('#searchBar').press('End'); // triggers real keyup → search()

        const visibleRows = page.locator('tbody tr:not([style*="display: none"])');
        const count = await visibleRows.count();
        expect(count).toBeGreaterThanOrEqual(2);
        for (let i = 0; i < count; i++) {
            await expect(visibleRows.nth(i).locator('td:first-child')).toContainText(regNr);
        }
    });

    test('new job appears in the list after being added', async ({ page }, testInfo) => {
        // AD prefix + 6-digit run ID + worker index = 9 chars (fits VARCHAR(10))
        const regNr = `AD${RUN_ID}${testInfo.workerIndex}`;
        const description = 'Uue töö kirjeldus';

        await createJob(page, regNr, description);

        // createJob redirects to tehtud_tood.php — search immediately
        await page.fill('#searchBar', regNr);
        await page.locator('#searchBar').press('End');

        const visibleRows = page.locator('tbody tr:not([style*="display: none"])');
        await expect(visibleRows).toHaveCount(1);
        await expect(visibleRows.first().locator('td:first-child')).toContainText(regNr);
        await expect(visibleRows.first().locator('td:nth-child(4)')).toContainText(description);
    });
});
