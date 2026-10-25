import { test, expect, Page } from '@playwright/test';
import { BASE_URL, ensureLoggedIn } from './helpers';

// RegNr column in DB is VARCHAR(10) — keep all identifiers ≤ 10 chars.
const RUN_ID = (Date.now() % 1000000).toString().padStart(6, '0');

async function submitForm(page: Page) {
    await page.locator('form').evaluate((form: HTMLFormElement) => {
        const btn = form.querySelector<HTMLInputElement>('input[type="submit"]');
        if (btn) form.requestSubmit(btn);
        else form.requestSubmit();
    });
}

async function createJob(page: Page, regNr: string, withInvoice: boolean) {
    await page.goto(`${BASE_URL}/src/tehtud_tood/lisa_too.php`, { waitUntil: 'load' });
    await page.fill('input[name="RegNr"]', regNr);
    await page.fill('input[name="Kuupaev"]', '2030-01-15T10:00');
    await page.fill('input[name="Odomeeter"]', '100000');
    await page.fill('textarea[name="Tehtud_tood"]', 'Arve test');
    if (withInvoice) await page.check('input[name="Arve"]');
    await Promise.all([
        page.waitForURL(/tehtud_tood\.php/, { waitUntil: 'load' }),
        submitForm(page),
    ]);
}

async function searchFor(page: Page, regNr: string) {
    const responsePromise = page.waitForResponse((r) =>
        r.url().includes('tehtud_tood/search.php')
    );
    await page.fill('#searchBar', regNr);
    await responsePromise;
    await expect(page.locator('#tableBody tr')).toHaveCount(1);
    return page.locator('#tableBody tr').first();
}

test.describe('Tehtud Tööd arve staatus', () => {
    test.beforeEach(async ({ page }) => {
        await ensureLoggedIn(page);
    });

    test('job without invoice shows no invoice icon', async ({ page }, testInfo) => {
        const regNr = `NA${RUN_ID}${testInfo.workerIndex}`;
        await createJob(page, regNr, false);
        const row = await searchFor(page, regNr);
        await expect(row.locator('.arve-button')).toHaveCount(0);
    });

    test('invoice icon advances red → orange → green', async ({ page }, testInfo) => {
        const regNr = `AR${RUN_ID}${testInfo.workerIndex}`;
        await createJob(page, regNr, true);
        const row = await searchFor(page, regNr);

        const icon = row.locator('.arve-button');
        await expect(icon).toHaveClass(/arve-koostamata/);

        await icon.click();
        await expect(icon).toHaveClass(/arve-saadetud/);

        await icon.click();
        await expect(icon).toHaveClass(/arve-makstud/);
        await expect(icon).toBeDisabled();

        // Status is persisted, not just updated in the DOM.
        await page.reload();
        const reloadedRow = await searchFor(page, regNr);
        await expect(reloadedRow.locator('.arve-button')).toHaveClass(/arve-makstud/);
    });

    test('invoice filter shows only jobs with an invoice', async ({ page }, testInfo) => {
        // Same RegNr prefix so the search narrows to just these two jobs.
        const regNr = `AF${RUN_ID}${testInfo.workerIndex}`;
        await createJob(page, regNr, true);
        await createJob(page, regNr, false);

        let responsePromise = page.waitForResponse((r) =>
            r.url().includes('tehtud_tood/search.php')
        );
        await page.fill('#searchBar', regNr);
        await responsePromise;
        await expect(page.locator('#tableBody tr')).toHaveCount(2);

        responsePromise = page.waitForResponse((r) =>
            r.url().includes('tehtud_tood/search.php') && r.url().includes('arve=arvega')
        );
        await page.locator('label.filter-toggle').click();
        await responsePromise;
        await expect(page.locator('#tableBody tr')).toHaveCount(1);
        await expect(page.locator('#tableBody tr .arve-button')).toHaveClass(/arve-koostamata/);
    });

    test('invoice status can be changed on the edit page', async ({ page }, testInfo) => {
        const regNr = `AE${RUN_ID}${testInfo.workerIndex}`;
        await createJob(page, regNr, true);
        const row = await searchFor(page, regNr);

        await Promise.all([
            page.waitForURL(/edit-work-process\.php/),
            row.locator('.muuda-icon').click(),
        ]);
        await expect(page.locator('select[name="Arve_staatus"]')).toHaveValue('koostamata');
        await page.selectOption('select[name="Arve_staatus"]', 'puudub');
        await Promise.all([
            page.waitForURL(/tehtud_tood\.php/, { waitUntil: 'load' }),
            submitForm(page),
        ]);

        const updatedRow = await searchFor(page, regNr);
        await expect(updatedRow.locator('.arve-button')).toHaveCount(0);
    });
});
