import { test, expect, Page } from '@playwright/test';
import { BASE_URL, ensureLoggedIn } from './helpers';

// RegNr column is VARCHAR(10) — use last 6 digits of timestamp so each test
// run gets a unique RegNr that fits the column.
const RUN_ID = (Date.now() % 1000000).toString().padStart(6, '0');

async function submitForm(page: Page) {
    await page.locator('form').evaluate((form: HTMLFormElement) => {
        const btn = form.querySelector<HTMLInputElement>('input[type="submit"]');
        if (btn) form.requestSubmit(btn);
        else form.requestSubmit();
    });
}

async function createTireStorage(page: Page, data: {
    regNr: string;
    omanik: string;
    kogus: string;
    hooaeg: 'Suverehv' | 'Naastrehv' | 'Lamellrehv';
    kuupaev: string;
}) {
    await page.goto(`${BASE_URL}/src/rehv_ladu/lisa_rehv_ladu.php`, { waitUntil: 'load' });
    await page.fill('input[name="RegNr"]', data.regNr);
    await page.fill('input[name="Omanik"]', data.omanik);
    await page.fill('input[name="Kogus"]', data.kogus);
    await page.selectOption('select[name="hooaeg"]', data.hooaeg);
    await page.fill('input[name="Kuupaev"]', data.kuupaev);
    await Promise.all([
        page.waitForURL(/rehv_ladu\.php/, { waitUntil: 'load' }),
        submitForm(page),
    ]);
}

test.describe('Rehvi Ladu testid', () => {
    test.beforeEach(async ({ page }) => {
        await ensureLoggedIn(page);
    });

    test('unauthenticated user is redirected to login from list page', async ({ page }) => {
        await page.context().clearCookies();
        await page.goto(`${BASE_URL}/src/rehv_ladu/rehv_ladu.php`);
        await expect(page).toHaveURL(/login\.php/);
    });

    test('unauthenticated user is redirected to login from add page', async ({ page }) => {
        await page.context().clearCookies();
        await page.goto(`${BASE_URL}/src/rehv_ladu/lisa_rehv_ladu.php`);
        await expect(page).toHaveURL(/login\.php/);
    });

    test('add form renders all required fields', async ({ page }) => {
        await page.goto(`${BASE_URL}/src/rehv_ladu/lisa_rehv_ladu.php`);
        await expect(page.locator('input[name="RegNr"]')).toBeVisible();
        await expect(page.locator('input[name="Omanik"]')).toBeVisible();
        await expect(page.locator('input[name="Kogus"]')).toBeVisible();
        await expect(page.locator('select[name="hooaeg"]')).toBeVisible();
        await expect(page.locator('input[name="Kuupaev"]')).toBeVisible();
    });

    test('season select offers the three allowed values', async ({ page }) => {
        await page.goto(`${BASE_URL}/src/rehv_ladu/lisa_rehv_ladu.php`);
        const options = await page.locator('select[name="hooaeg"] option').allTextContents();
        expect(options).toEqual(['Suverehv', 'Naastrehv', 'Lamellrehv']);
    });

    test('newly added tire storage entry appears in the list', async ({ page }, testInfo) => {
        // RL prefix + 6-digit RUN_ID + worker index → ≤ 10 chars
        const regNr = `RL${RUN_ID}${testInfo.workerIndex}`;
        await createTireStorage(page, {
            regNr,
            omanik: 'Test Omanik',
            kogus: '4',
            hooaeg: 'Suverehv',
            kuupaev: '2030-06-15',
        });

        // After submit we are on rehv_ladu.php
        const row = page.locator('tbody tr').filter({ hasText: regNr.toUpperCase() });
        await expect(row).toHaveCount(1);
        await expect(row).toContainText('Test Omanik');
        await expect(row).toContainText('4 tk');
        await expect(row).toContainText('Suverehv');
    });

    test('search bar filters tire storage list by registration number', async ({ page }, testInfo) => {
        const regNr = `RS${RUN_ID}${testInfo.workerIndex}`;
        await createTireStorage(page, {
            regNr,
            omanik: 'Otsing Omanik',
            kogus: '2',
            hooaeg: 'Naastrehv',
            kuupaev: '2030-11-01',
        });

        await page.fill('#searchBar', regNr);
        await page.locator('#searchBar').dispatchEvent('keyup');

        const visibleRows = page.locator('tbody tr:not([style*="display: none"])');
        await expect(visibleRows).toHaveCount(1);
        await expect(visibleRows.first().locator('td:first-child')).toContainText(regNr.toUpperCase());
    });

    test('list page shows the Lisa Rehvid Lattu link', async ({ page }) => {
        await page.goto(`${BASE_URL}/src/rehv_ladu/rehv_ladu.php`);
        const addLink = page.locator('a.lisa-link[href*="lisa_rehv_ladu.php"]');
        await expect(addLink).toBeVisible();
    });
});
