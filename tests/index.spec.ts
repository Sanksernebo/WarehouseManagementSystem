import { test, expect, Page } from '@playwright/test';
import { BASE_URL, ensureLoggedIn } from './helpers';

// Unique prefix per test run so each run creates fresh DB rows that won't
// clash with existing data or previous test runs.
const RUN_ID = Date.now().toString().slice(-10);

// Submit a form via its submit button so PHP's isset($_POST['submit']) check
// passes, while also bypassing any fixed-footer pointer interception.
async function submitForm(page: Page) {
    await page.locator('form').evaluate((form: HTMLFormElement) => {
        const btn = form.querySelector<HTMLInputElement>('input[type="submit"]');
        if (btn) form.requestSubmit(btn);
        else form.requestSubmit();
    });
}

async function createProduct(page: Page, tootekood: string, nimetus: string) {
    await page.goto(`${BASE_URL}/src/lisa_lattu/lisa_lattu.php`, { waitUntil: 'load' });
    await page.fill('input[name="Tootekood"]', tootekood);
    await page.fill('input[name="Nimetus"]', nimetus);
    await page.fill('input[name="Kogus"]', '1');
    await page.fill('input[name="Sisseost"]', '1.00');
    await page.fill('input[name="Jaehind"]', '2.00');
    await Promise.all([
        page.waitForURL(/index\.php/, { waitUntil: 'load' }),
        submitForm(page),
    ]);
}

async function deleteProduct(page: Page, tootekood: string) {
    await page.goto(`${BASE_URL}/index.php`, { waitUntil: 'load' });
    const row = page.locator('tbody tr').filter({ hasText: tootekood });
    if (await row.count() === 0) return;
    const href = await row.locator('a[href*="delete-process.php"]').getAttribute('href');
    if (!href) return;
    await page.goto(`${BASE_URL}/${href}`, { waitUntil: 'load' });
    await Promise.all([
        page.waitForURL(/index\.php/, { waitUntil: 'load' }),
        page.locator('form').evaluate((form: HTMLFormElement) => {
            const btn = form.querySelector<HTMLInputElement>('input[name="confirm_delete"]');
            if (btn) form.requestSubmit(btn);
        }),
    ]);
}

test.describe('Index page tests', () => {
    test.beforeEach(async ({ page }) => {
        await ensureLoggedIn(page);
    });

    test('navigation bar shows logo and links', async ({ page }) => {
        await expect(page.locator('nav')).toBeVisible();
        await expect(page.locator('.logo img')).toHaveAttribute('src', 'src/img/cartehniklogo_valge.svg');
        await expect(page.locator('a[href="src/tehtud_tood/tehtud_tood.php"]')).toBeVisible();
    });

    test('search bar filters stock table by product code', async ({ page }, testInfo) => {
        const tootekood = `E2E-SRCH-${RUN_ID}-${testInfo.workerIndex}`;
        await createProduct(page, tootekood, 'Testoode otsing');

        await page.goto(`${BASE_URL}/index.php`, { waitUntil: 'load' });
        await page.fill('#searchBar', tootekood);
        await page.locator('#searchBar').dispatchEvent('keyup');

        const visibleRows = page.locator('tbody tr:not([style*="display: none"])');
        await expect(visibleRows).toHaveCount(1);
        await expect(visibleRows.first().locator('td:first-child')).toContainText(tootekood);

        await deleteProduct(page, tootekood);
    });

    test('product name can be updated and shows the new value', async ({ page }, testInfo) => {
        const tootekood = `E2E-EDIT-${RUN_ID}-${testInfo.workerIndex}`;
        const updatedName = 'Uuendatud nimetus';
        await createProduct(page, tootekood, 'Algne nimetus');

        await page.goto(`${BASE_URL}/index.php`, { waitUntil: 'load' });
        const row = page.locator('tbody tr').filter({ hasText: tootekood });
        const editHref = await row.locator('a[href*="update-process.php"]').getAttribute('href');
        await page.goto(`${BASE_URL}/${editHref}`, { waitUntil: 'load' });

        await page.fill('input[name="Nimetus"]', updatedName);
        await Promise.all([
            page.waitForURL(/index\.php/, { waitUntil: 'load' }),
            submitForm(page),
        ]);

        const updatedRow = page.locator('tbody tr').filter({ hasText: tootekood });
        await expect(updatedRow.locator('td:nth-child(2)')).toHaveText(updatedName);

        await deleteProduct(page, tootekood);
    });
});
