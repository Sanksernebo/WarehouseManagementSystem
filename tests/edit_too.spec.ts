import { test, expect, Page } from '@playwright/test';
import { BASE_URL, ensureLoggedIn } from './helpers';

const RUN_ID = (Date.now() % 1000000).toString().padStart(6, '0');

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

async function openEditForm(page: Page, regNr: string) {
    const row = page.locator('tbody tr').filter({ hasText: regNr.toUpperCase() });
    await expect(row).toHaveCount(1);
    const href = await row.locator('a[href*="edit-work-process.php"]').first().getAttribute('href');
    await page.goto(`${BASE_URL}/${href}`, { waitUntil: 'load' });
}

test.describe('Tehtud Töö muutmine', () => {
    test.beforeEach(async ({ page }) => {
        await ensureLoggedIn(page);
    });

    test('unauthenticated user is redirected to login', async ({ page }) => {
        await page.context().clearCookies();
        await page.goto(`${BASE_URL}/src/tehtud_tood/edit-work-process.php?too_id=1`);
        await expect(page).toHaveURL(/login\.php/);
    });

    test('non-existent too_id renders an error message', async ({ page }) => {
        const response = await page.goto(
            `${BASE_URL}/src/tehtud_tood/edit-work-process.php?too_id=999999999`
        );
        expect(response?.status()).toBe(200);
        await expect(page.locator('body')).toContainText('Autot ei leitud');
    });

    test('edit form pre-populates with existing job values', async ({ page }, testInfo) => {
        // EP prefix + 6-digit RUN_ID + worker index → ≤ 10 chars
        const regNr = `EP${RUN_ID}${testInfo.workerIndex}`;
        const description = 'Algne kirjeldus';
        await createJob(page, regNr, description);
        await openEditForm(page, regNr);

        await expect(page.locator('input[name="RegNr"]')).toHaveValue(regNr.toUpperCase());
        await expect(page.locator('input[name="Kuupaev"]')).toHaveValue('2030-01-15T10:00');
        await expect(page.locator('input[name="Odomeeter"]')).toHaveValue('100000');
        await expect(page.locator('textarea[name="Tehtud_tood"]')).toHaveValue(description);
    });

    test('updating the description persists and appears in the list', async ({ page }, testInfo) => {
        // EU prefix + 6-digit RUN_ID + worker index → ≤ 10 chars
        const regNr = `EU${RUN_ID}${testInfo.workerIndex}`;
        await createJob(page, regNr, 'Vana kirjeldus');
        await openEditForm(page, regNr);

        const newDescription = 'Uuendatud töö kirjeldus';
        await page.fill('textarea[name="Tehtud_tood"]', newDescription);
        await Promise.all([
            page.waitForURL(/tehtud_tood\.php/, { waitUntil: 'load' }),
            submitForm(page),
        ]);

        await page.fill('#searchBar', regNr);
        await page.locator('#searchBar').press('End');
        const row = page.locator('tbody tr').filter({ hasText: regNr.toUpperCase() });
        await expect(row).toHaveCount(1);
        await expect(row).toContainText(newDescription);
        await expect(row).not.toContainText('Vana kirjeldus');
    });

    test('updating odomeeter persists and appears in the list', async ({ page }, testInfo) => {
        const regNr = `EO${RUN_ID}${testInfo.workerIndex}`;
        await createJob(page, regNr, 'Odomeetri test');
        await openEditForm(page, regNr);

        await page.fill('input[name="Odomeeter"]', '123456');
        await Promise.all([
            page.waitForURL(/tehtud_tood\.php/, { waitUntil: 'load' }),
            submitForm(page),
        ]);

        await page.fill('#searchBar', regNr);
        await page.locator('#searchBar').press('End');
        const row = page.locator('tbody tr').filter({ hasText: regNr.toUpperCase() });
        await expect(row).toContainText('123456');
    });
});
