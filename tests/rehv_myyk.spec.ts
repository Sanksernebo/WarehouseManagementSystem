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

async function createTireSale(page: Page, data: {
    regNr: string;
    moot: string;
    tootja: string;
    kogus: string;
    hooaeg: 'Suverehv' | 'Naastrehv' | 'Lamellrehv';
    tarnija: 'INTERCARS' | 'ERIMELL' | 'LATTAKO' | 'MUU';
    kuupaev: string;
}) {
    await page.goto(`${BASE_URL}/src/rehv_myyk/lisa_rehv_myyk.php`, { waitUntil: 'load' });
    await page.fill('input[name="RegNr"]', data.regNr);
    await page.fill('input[name="Moot"]', data.moot);
    await page.fill('input[name="Tootja"]', data.tootja);
    await page.fill('input[name="Kogus"]', data.kogus);
    await page.selectOption('select[name="hooaeg"]', data.hooaeg);
    await page.selectOption('select[name="tarnija"]', data.tarnija);
    await page.fill('input[name="Kuupaev"]', data.kuupaev);
    await Promise.all([
        page.waitForURL(/rehv_myyk\.php/, { waitUntil: 'load' }),
        submitForm(page),
    ]);
}

test.describe('Rehvi Müük testid', () => {
    test.beforeEach(async ({ page }) => {
        await ensureLoggedIn(page);
    });

    test('unauthenticated user is redirected to login from list page', async ({ page }) => {
        await page.context().clearCookies();
        await page.goto(`${BASE_URL}/src/rehv_myyk/rehv_myyk.php`);
        await expect(page).toHaveURL(/login\.php/);
    });

    test('unauthenticated user is redirected to login from add page', async ({ page }) => {
        await page.context().clearCookies();
        await page.goto(`${BASE_URL}/src/rehv_myyk/lisa_rehv_myyk.php`);
        await expect(page).toHaveURL(/login\.php/);
    });

    test('add form renders all required fields', async ({ page }) => {
        await page.goto(`${BASE_URL}/src/rehv_myyk/lisa_rehv_myyk.php`);
        await expect(page.locator('input[name="RegNr"]')).toBeVisible();
        await expect(page.locator('input[name="Moot"]')).toBeVisible();
        await expect(page.locator('input[name="Tootja"]')).toBeVisible();
        await expect(page.locator('input[name="Kogus"]')).toBeVisible();
        await expect(page.locator('select[name="hooaeg"]')).toBeVisible();
        await expect(page.locator('select[name="tarnija"]')).toBeVisible();
        await expect(page.locator('input[name="Kuupaev"]')).toBeVisible();
    });

    test('supplier select offers the expected suppliers', async ({ page }) => {
        await page.goto(`${BASE_URL}/src/rehv_myyk/lisa_rehv_myyk.php`);
        const values = await page.locator('select[name="tarnija"] option').evaluateAll(
            (opts: HTMLOptionElement[]) => opts.map(o => o.value)
        );
        expect(values).toEqual(['INTERCARS', 'ERIMELL', 'LATTAKO', 'MUU']);
    });

    test('newly added sale appears in the list with all field values', async ({ page }, testInfo) => {
        // RM prefix + 6-digit RUN_ID + worker index → ≤ 10 chars
        const regNr = `RM${RUN_ID}${testInfo.workerIndex}`;
        await createTireSale(page, {
            regNr,
            moot: '205/55R16',
            tootja: 'Nokian',
            kogus: '4',
            hooaeg: 'Suverehv',
            tarnija: 'INTERCARS',
            kuupaev: '2030-06-15',
        });

        const row = page.locator('tbody tr').filter({ hasText: regNr.toUpperCase() });
        await expect(row).toHaveCount(1);
        // Moot is rendered UPPERCASE by the SELECT — assert that.
        await expect(row).toContainText('205/55R16');
        await expect(row).toContainText('Nokian');
        await expect(row).toContainText('4 tk');
        await expect(row).toContainText('Suverehv');
        await expect(row).toContainText('INTERCARS');
    });

    test('search bar filters sales list by registration number', async ({ page }, testInfo) => {
        const regNr = `MS${RUN_ID}${testInfo.workerIndex}`;
        await createTireSale(page, {
            regNr,
            moot: '195/65R15',
            tootja: 'Michelin',
            kogus: '2',
            hooaeg: 'Lamellrehv',
            tarnija: 'ERIMELL',
            kuupaev: '2030-10-01',
        });

        await page.fill('#searchBar', regNr);
        await page.locator('#searchBar').dispatchEvent('keyup');

        const visibleRows = page.locator('tbody tr:not([style*="display: none"])');
        await expect(visibleRows).toHaveCount(1);
        await expect(visibleRows.first().locator('td:first-child')).toContainText(regNr.toUpperCase());
    });
});
