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

// Create a Tehtud Töö and return its too_id by reading it from the listing's
// edit/pdf link after the redirect.
async function createJobAndGetId(page: Page, regNr: string, description: string): Promise<string> {
    await page.goto(`${BASE_URL}/src/tehtud_tood/lisa_too.php`, { waitUntil: 'load' });
    await page.fill('input[name="RegNr"]', regNr);
    await page.fill('input[name="Kuupaev"]', '2030-01-15T10:00');
    await page.fill('input[name="Odomeeter"]', '100000');
    await page.fill('textarea[name="Tehtud_tood"]', description);
    await Promise.all([
        page.waitForURL(/tehtud_tood\.php/, { waitUntil: 'load' }),
        submitForm(page),
    ]);

    // Find the row matching the unique regNr and extract too_id from the PDF link
    const row = page.locator('tbody tr').filter({ hasText: regNr.toUpperCase() });
    await expect(row).toHaveCount(1);
    const href = await row.locator('a[href*="pdf_koostamine.php"]').getAttribute('href');
    const match = href?.match(/too_id=(\d+)/);
    expect(match).not.toBeNull();
    return match![1];
}

test.describe('PDF generaator testid', () => {
    test('unauthenticated user is redirected to login', async ({ page }) => {
        await page.context().clearCookies();
        await page.goto(`${BASE_URL}/src/pdf_generaator/pdf_koostamine.php?too_id=1`);
        await expect(page).toHaveURL(/login\.php/);
    });

    test.describe('Logged in', () => {
        test.beforeEach(async ({ page }) => {
            await ensureLoggedIn(page);
        });

        test('missing too_id parameter shows a clear error', async ({ page }) => {
            const response = await page.goto(`${BASE_URL}/src/pdf_generaator/pdf_koostamine.php`);
            expect(response?.status()).toBe(200);
            await expect(page.locator('body')).toContainText('RegNr puudub');
        });

        test('non-existent too_id shows a clear error', async ({ page }) => {
            // Use a too_id that is extremely unlikely to exist.
            const response = await page.goto(
                `${BASE_URL}/src/pdf_generaator/pdf_koostamine.php?too_id=999999999`
            );
            expect(response?.status()).toBe(200);
            await expect(page.locator('body')).toContainText('Andmeid ei leitud');
        });

        test('existing too_id returns a PDF document', async ({ page, request }, testInfo) => {
            // PR prefix + 6-digit RUN_ID + worker index → ≤ 10 chars
            const regNr = `PR${RUN_ID}${testInfo.workerIndex}`;
            const tooId = await createJobAndGetId(page, regNr, 'PDF genereerimise test');

            // Carry the auth cookie over to APIRequest so the PHP session is recognised.
            const cookies = await page.context().cookies();
            const response = await request.get(
                `${BASE_URL}/src/pdf_generaator/pdf_koostamine.php?too_id=${tooId}`,
                {
                    headers: {
                        Cookie: cookies.map(c => `${c.name}=${c.value}`).join('; '),
                    },
                }
            );

            expect(response.status()).toBe(200);
            // TCPDF outputs binary PDF starting with "%PDF-"
            const body = await response.body();
            const head = body.slice(0, 5).toString('ascii');
            expect(head).toBe('%PDF-');
        });
    });
});
