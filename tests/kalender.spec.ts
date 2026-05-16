import { test, expect, Page, TestInfo, Locator } from '@playwright/test';
import { BASE_URL, ensureLoggedIn } from './helpers';

// Returns a unique future date per test slot, worker, and browser project.
// workerIndex resets to 0 per project, so we add a browser-specific offset
// (1000 days apart) to prevent chromium/firefox/webkit from colliding on the same DB rows.
function testDate(slot: number, testInfo: TestInfo): string {
    const browserOffsets: Record<string, number> = {
        chromium: 0,
        firefox: 1000,
        webkit: 2000,
    };
    const browserOffset = browserOffsets[testInfo.project.name] ?? 0;
    const base = new Date();
    base.setFullYear(base.getFullYear() + 10);
    base.setDate(base.getDate() + slot + testInfo.workerIndex * 100 + browserOffset);
    return [
        base.getFullYear(),
        String(base.getMonth() + 1).padStart(2, '0'),
        String(base.getDate()).padStart(2, '0'),
    ].join('-');
}

async function openDailyView(page: Page, date: string) {
    const [year, month] = date.split('-');
    await page.goto(
        `${BASE_URL}/src/kalender/kalender.php?year=${year}&month=${month}&date=${date}`,
        { waitUntil: 'domcontentloaded' }
    );
}

async function followLink(locator: Locator) {
    const href = await locator.getAttribute('href');
    await locator.page().goto(`${BASE_URL}/src/kalender/${href}`, { waitUntil: 'load' });
}

// Deletes all bookings on a given date via the UI.
// Safe to call per-test because each test owns a unique date (slot + workerIndex).
async function ensureCleanDate(page: Page, date: string) {
    await openDailyView(page, date);
    while (await page.locator('.daily-view-table a[href*="kustuta_aeg.php"]').count() > 0) {
        await followLink(page.locator('.daily-view-table a[href*="kustuta_aeg.php"]').first());
        if (await page.locator('input[name="confirm_delete"]').count() > 0) {
            await Promise.all([
                page.waitForURL(/kalender\.php/, { waitUntil: 'domcontentloaded' }),
                page.click('input[name="confirm_delete"]'),
            ]);
        }
        await openDailyView(page, date);
    }
}

async function createBooking(page: Page, data: {
    kliendi_nimi: string;
    reg_nr?: string;
    broneeritud_aeg: string;
    algus_aeg: string;
    lopp_aeg: string;
    kirjeldus?: string;
}) {
    await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`, { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="kliendi_nimi"]', data.kliendi_nimi);
    if (data.reg_nr) await page.fill('input[name="reg_nr"]', data.reg_nr);
    await page.fill('input[name="broneeritud_aeg"]', data.broneeritud_aeg);
    await page.selectOption('select[name="algus_aeg"]', data.algus_aeg);
    await page.selectOption('select[name="lopp_aeg"]', data.lopp_aeg);
    if (data.kirjeldus) await page.fill('textarea[name="kirjeldus"]', data.kirjeldus);
    await Promise.all([
        page.waitForURL(/kalender\.php/, { waitUntil: 'load' }),
        page.locator('form').evaluate((form: HTMLFormElement) => form.requestSubmit()),
    ]);
}

test.describe('Kalender testid', () => {
    test.beforeEach(async ({ page }) => {
        await page.route('**fontawesome.com**', route => route.abort());
        await page.route('**cloudflare.com**', route => route.abort());
        await ensureLoggedIn(page);
    });

    test.describe('Kalender vaade', () => {
        test('unauthenticated user is redirected to login', async ({ page }) => {
            await page.context().clearCookies();
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            await expect(page).toHaveURL(/login\.php/);
        });

        test('calendar displays current month and year', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            const now = new Date();
            const estonianMonths = ['Jaanuar', 'Veebruar', 'Märts', 'Aprill', 'Mai', 'Juuni',
                'Juuli', 'August', 'September', 'Oktoober', 'November', 'Detsember'];
            await expect(page.locator('.current-month')).toContainText(estonianMonths[now.getMonth()]);
            await expect(page.locator('.current-month')).toContainText(String(now.getFullYear()));
        });

        test('today is visually highlighted', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            // Read today's date from the PHP-generated Täna button to avoid timezone mismatch
            const href = await page.locator('a.today-btn').getAttribute('href');
            const todayDate = new URLSearchParams(href!.split('?')[1]).get('date')!;
            const todayDay = String(parseInt(todayDate.split('-')[2]));
            const currentDayEl = page.locator('.current-day');
            await expect(currentDayEl).toBeVisible();
            await expect(currentDayEl).toContainText(todayDay);
        });

        test('previous month navigation changes the displayed month', async ({ page }) => {
            // Use a fixed month to avoid timezone issues and make the assertion deterministic
            await page.goto(`${BASE_URL}/src/kalender/kalender.php?year=2030&month=3`);
            await page.click('a:has-text("Eelmine Kuu")');
            await page.waitForLoadState('domcontentloaded');
            await expect(page.locator('.current-month')).toContainText('Veebruar');
            await expect(page.locator('.current-month')).toContainText('2030');
        });

        test('next month navigation changes the displayed month', async ({ page }) => {
            // Use a fixed month to avoid timezone issues and make the assertion deterministic
            await page.goto(`${BASE_URL}/src/kalender/kalender.php?year=2030&month=3`);
            await page.click('a:has-text("Järgmine Kuu")');
            await page.waitForLoadState('domcontentloaded');
            await expect(page.locator('.current-month')).toContainText('Aprill');
            await expect(page.locator('.current-month')).toContainText('2030');
        });

        test('Täna button returns to current month from another month', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            await page.click('a:has-text("Järgmine Kuu")');
            await page.click('a.today-btn');
            const now = new Date();
            const estonianMonths = ['Jaanuar', 'Veebruar', 'Märts', 'Aprill', 'Mai', 'Juuni',
                'Juuli', 'August', 'September', 'Oktoober', 'November', 'Detsember'];
            await expect(page.locator('.current-month')).toContainText(estonianMonths[now.getMonth()]);
            await expect(page.locator('.current-month')).toContainText(String(now.getFullYear()));
        });

        test('Täna button selects today and shows the daily view', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            await page.click('a:has-text("Järgmine Kuu")');
            await page.click('a.today-btn');
            await expect(page.locator('.daily-view')).toBeVisible();
            await expect(page.locator('.current-day.selected-day, .selected-day')).toBeVisible();
        });

        test('clicking a day shows the daily view for that day', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            await Promise.all([
                page.waitForURL(/kalender\.php.*date=/, { waitUntil: 'domcontentloaded' }),
                page.locator('.calendar-day').first().click(),
            ]);
            await expect(page.locator('.daily-view')).toBeVisible();
            await expect(page.locator('.daily-view h2')).toContainText('Broneeringud');
        });

        test('selected day is visually highlighted', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            await Promise.all([
                page.waitForURL(/kalender\.php.*date=/, { waitUntil: 'domcontentloaded' }),
                page.locator('.calendar-day').first().click(),
            ]);
            await expect(page.locator('.selected-day')).toBeVisible();
        });

        test('navigating to next month and clicking a day stays on that month', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php?year=2030&month=3`, { waitUntil: 'domcontentloaded' });
            await Promise.all([
                page.waitForURL(/kalender\.php.*date=/, { waitUntil: 'domcontentloaded' }),
                page.locator('.calendar-day').first().click(),
            ]);
            await expect(page.locator('.current-month')).toContainText('Märts');
            await expect(page.locator('.daily-view')).toBeVisible();
        });

        test('daily view shows 09:00–18:00 time slots', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            await Promise.all([
                page.waitForURL(/kalender\.php.*date=/, { waitUntil: 'domcontentloaded' }),
                page.locator('.calendar-day').first().click(),
            ]);
            await expect(page.locator('.daily-view-table td:has-text("09:00 kuni 10:00")')).toBeVisible();
            await expect(page.locator('.daily-view-table td:has-text("17:00 kuni 18:00")')).toBeVisible();
        });

        test('Lisa Broneering link navigates to add booking form', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/kalender.php`);
            await page.click('a.lisa-link');
            await expect(page).toHaveURL(/lisa_uus_aeg\.php/);
        });
    });

    test.describe('Broneeringu lisamine', () => {
        test('form renders all required fields', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            await expect(page.locator('input[name="kliendi_nimi"]')).toBeVisible();
            await expect(page.locator('input[name="reg_nr"]')).toBeVisible();
            await expect(page.locator('input[name="broneeritud_aeg"]')).toBeVisible();
            await expect(page.locator('select[name="algus_aeg"]')).toBeVisible();
            await expect(page.locator('select[name="lopp_aeg"]')).toBeVisible();
            await expect(page.locator('textarea[name="kirjeldus"]')).toBeVisible();
        });

        test('start time select contains only 09:00–17:00', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            const options = await page.locator('select[name="algus_aeg"] option:not([value=""])').allTextContents();
            expect(options.at(0)).toBe('09:00');
            expect(options.at(-1)).toBe('17:00');
            expect(options).toHaveLength(9);
        });

        test('end time select contains only 10:00–18:00', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            const options = await page.locator('select[name="lopp_aeg"] option:not([value=""])').allTextContents();
            expect(options.at(0)).toBe('10:00');
            expect(options.at(-1)).toBe('18:00');
            expect(options).toHaveLength(9);
        });

        test('start >= end time shows error', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            await page.fill('input[name="kliendi_nimi"]', 'Test Klient');
            await page.fill('input[name="broneeritud_aeg"]', '2038-01-15');
            await page.selectOption('select[name="algus_aeg"]', '12:00');
            await page.selectOption('select[name="lopp_aeg"]', '11:00');
            await page.click('input[type="submit"]');
            await expect(page.locator('.error')).toContainText('Algusaeg peab olema enne lõppaega');
        });

        test('start >= end time error retains all field values', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            await page.fill('input[name="kliendi_nimi"]', 'Kinnipeetud Klient');
            await page.fill('input[name="reg_nr"]', 'RETAIN1');
            await page.fill('input[name="broneeritud_aeg"]', '2038-01-15');
            await page.selectOption('select[name="algus_aeg"]', '13:00');
            await page.selectOption('select[name="lopp_aeg"]', '11:00');
            await page.fill('textarea[name="kirjeldus"]', 'Kirjelduse tekst');
            await page.click('input[type="submit"]');

            await expect(page.locator('input[name="kliendi_nimi"]')).toHaveValue('Kinnipeetud Klient');
            await expect(page.locator('input[name="reg_nr"]')).toHaveValue('RETAIN1');
            await expect(page.locator('input[name="broneeritud_aeg"]')).toHaveValue('2038-01-15');
            await expect(page.locator('select[name="algus_aeg"]')).toHaveValue('13:00');
            await expect(page.locator('select[name="lopp_aeg"]')).toHaveValue('11:00');
            await expect(page.locator('textarea[name="kirjeldus"]')).toHaveValue('Kirjelduse tekst');
        });

        test('start >= end time error highlights the time fields', async ({ page }) => {
            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            await page.fill('input[name="kliendi_nimi"]', 'Test');
            await page.fill('input[name="broneeritud_aeg"]', '2038-01-15');
            await page.selectOption('select[name="algus_aeg"]', '14:00');
            await page.selectOption('select[name="lopp_aeg"]', '13:00');
            await page.click('input[type="submit"]');

            await expect(page.locator('select[name="algus_aeg"]')).toHaveClass(/field-error/);
            await expect(page.locator('select[name="lopp_aeg"]')).toHaveClass(/field-error/);
        });

        test('time conflict error shows conflicting booking client and reg number', async ({ page }, testInfo) => {
            const date = testDate(0, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Konflikt Klient',
                reg_nr: 'KON001',
                broneeritud_aeg: date,
                algus_aeg: '10:00',
                lopp_aeg: '12:00',
            });

            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            await page.fill('input[name="kliendi_nimi"]', 'Uus Klient');
            await page.fill('input[name="broneeritud_aeg"]', date);
            await page.selectOption('select[name="algus_aeg"]', '11:00');
            await page.selectOption('select[name="lopp_aeg"]', '13:00');
            await page.click('input[type="submit"]');

            await expect(page.locator('.error')).toContainText('Valitud ajavahemik on juba broneeritud');
            await expect(page.locator('.error')).toContainText('Konflikt Klient');
            await expect(page.locator('.error')).toContainText('KON001');
        });

        test('time conflict error shows the conflicting booking timeframe', async ({ page }, testInfo) => {
            const date = testDate(1, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Ajavahem Klient',
                broneeritud_aeg: date,
                algus_aeg: '10:00',
                lopp_aeg: '12:00',
            });

            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            await page.fill('input[name="kliendi_nimi"]', 'Uus Klient');
            await page.fill('input[name="broneeritud_aeg"]', date);
            await page.selectOption('select[name="algus_aeg"]', '11:00');
            await page.selectOption('select[name="lopp_aeg"]', '13:00');
            await page.click('input[type="submit"]');

            await expect(page.locator('.error')).toContainText('10:00');
            await expect(page.locator('.error')).toContainText('12:00');
        });

        test('time conflict error highlights date and time fields', async ({ page }, testInfo) => {
            const date = testDate(2, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Väli Klient',
                broneeritud_aeg: date,
                algus_aeg: '10:00',
                lopp_aeg: '12:00',
            });

            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            await page.fill('input[name="kliendi_nimi"]', 'Uus Klient');
            await page.fill('input[name="broneeritud_aeg"]', date);
            await page.selectOption('select[name="algus_aeg"]', '11:00');
            await page.selectOption('select[name="lopp_aeg"]', '13:00');
            await page.click('input[type="submit"]');

            await expect(page.locator('input[name="broneeritud_aeg"]')).toHaveClass(/field-error/);
            await expect(page.locator('select[name="algus_aeg"]')).toHaveClass(/field-error/);
            await expect(page.locator('select[name="lopp_aeg"]')).toHaveClass(/field-error/);
        });

        test('successful booking redirects to calendar', async ({ page }, testInfo) => {
            const date = testDate(3, testInfo);
            await ensureCleanDate(page, date);
            await page.goto(`${BASE_URL}/src/kalender/lisa_uus_aeg.php`);
            await page.fill('input[name="kliendi_nimi"]', 'Redirect Test');
            await page.fill('input[name="broneeritud_aeg"]', date);
            await page.selectOption('select[name="algus_aeg"]', '09:00');
            await page.selectOption('select[name="lopp_aeg"]', '10:00');
            await page.click('input[type="submit"]');
            await expect(page).toHaveURL(/kalender\.php/);
        });

        test('created booking appears in the daily view', async ({ page }, testInfo) => {
            const date = testDate(4, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Päeva Klient',
                reg_nr: 'DAY001',
                broneeritud_aeg: date,
                algus_aeg: '14:00',
                lopp_aeg: '16:00',
                kirjeldus: 'Testimise broneering',
            });

            await openDailyView(page, date);
            await expect(page.locator('.daily-view-table')).toContainText('Päeva Klient');
            await expect(page.locator('.daily-view-table')).toContainText('DAY001');
            await expect(page.locator('.daily-view-table')).toContainText('Testimise broneering');
        });
    });

    test.describe('Broneeringu muutmine', () => {
        test('edit form pre-populates all existing booking values', async ({ page }, testInfo) => {
            const date = testDate(5, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Muuda Klient',
                reg_nr: 'EDIT01',
                broneeritud_aeg: date,
                algus_aeg: '09:00',
                lopp_aeg: '11:00',
                kirjeldus: 'Algne kirjeldus',
            });

            await openDailyView(page, date);
            await followLink(page.locator('.daily-view-table a[href*="muuda_aega.php"]').first());

            await expect(page.locator('input[name="kliendi_nimi"]')).toHaveValue('Muuda Klient');
            await expect(page.locator('input[name="reg_nr"]')).toHaveValue('EDIT01');
            await expect(page.locator('select[name="algus_aeg"]')).toHaveValue('09:00');
            await expect(page.locator('select[name="lopp_aeg"]')).toHaveValue('11:00');
            await expect(page.locator('textarea[name="kirjeldus"]')).toHaveValue('Algne kirjeldus');
        });

        test('successful edit redirects to calendar', async ({ page }, testInfo) => {
            const date = testDate(6, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Muuda Redirect',
                broneeritud_aeg: date,
                algus_aeg: '09:00',
                lopp_aeg: '10:00',
            });

            await openDailyView(page, date);
            await followLink(page.locator('.daily-view-table a[href*="muuda_aega.php"]').first());
            await page.fill('input[name="kliendi_nimi"]', 'Muudetud Klient');
            await Promise.all([
                page.waitForURL(/kalender\.php/, { waitUntil: 'load' }),
                page.locator('form').evaluate((form: HTMLFormElement) => form.requestSubmit()),
            ]);

            await expect(page).toHaveURL(/kalender\.php/);
        });

        test('edited booking shows updated values in daily view', async ({ page }, testInfo) => {
            const date = testDate(7, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Vana Klient',
                reg_nr: 'OLD001',
                broneeritud_aeg: date,
                algus_aeg: '09:00',
                lopp_aeg: '10:00',
            });

            await openDailyView(page, date);
            await followLink(page.locator('.daily-view-table a[href*="muuda_aega.php"]').first());
            await page.fill('input[name="kliendi_nimi"]', 'Uuendatud Klient');
            await page.fill('input[name="reg_nr"]', 'NEW001');
            await Promise.all([
                page.waitForURL(/kalender\.php/, { waitUntil: 'load' }),
                page.locator('form').evaluate((form: HTMLFormElement) => form.requestSubmit()),
            ]);

            await openDailyView(page, date);
            await expect(page.locator('.daily-view-table')).toContainText('Uuendatud Klient');
            await expect(page.locator('.daily-view-table')).toContainText('NEW001');
            await expect(page.locator('.daily-view-table')).not.toContainText('Vana Klient');
        });

        test('start >= end time on edit shows error and retains submitted values', async ({ page }, testInfo) => {
            const date = testDate(8, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Viga Test',
                broneeritud_aeg: date,
                algus_aeg: '10:00',
                lopp_aeg: '12:00',
            });

            await openDailyView(page, date);
            await followLink(page.locator('.daily-view-table a[href*="muuda_aega.php"]').first());
            await page.selectOption('select[name="algus_aeg"]', '14:00');
            await page.selectOption('select[name="lopp_aeg"]', '12:00');
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                page.locator('form').evaluate((form: HTMLFormElement) => form.requestSubmit()),
            ]);
            await expect(page.locator('.error')).toContainText('Algusaeg peab olema enne lõppaega');
            await expect(page.locator('select[name="algus_aeg"]')).toHaveValue('14:00');
            await expect(page.locator('select[name="lopp_aeg"]')).toHaveValue('12:00');
        });

        test('overlap on edit shows conflicting booking details and timeframe', async ({ page }, testInfo) => {
            const date = testDate(9, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Esimene Klient',
                reg_nr: 'FIRST1',
                broneeritud_aeg: date,
                algus_aeg: '09:00',
                lopp_aeg: '10:00',
            });
            await createBooking(page, {
                kliendi_nimi: 'Teine Klient',
                broneeritud_aeg: date,
                algus_aeg: '11:00',
                lopp_aeg: '12:00',
            });

            await openDailyView(page, date);
            await expect(page.locator('.daily-view-table a[href*="muuda_aega.php"]')).toHaveCount(2);
            // Edit the second booking (11:00) to overlap with the first (09:00)
            await followLink(page.locator('.daily-view-table a[href*="muuda_aega.php"]').nth(1));
            await page.selectOption('select[name="algus_aeg"]', '09:00');
            await page.selectOption('select[name="lopp_aeg"]', '11:00');
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                page.locator('form').evaluate((form: HTMLFormElement) => form.requestSubmit()),
            ]);
            await expect(page.locator('.error')).toContainText('Valitud ajavahemik on juba broneeritud');
            await expect(page.locator('.error')).toContainText('Esimene Klient');
            await expect(page.locator('.error')).toContainText('FIRST1');
            await expect(page.locator('.error')).toContainText('09:00');
            await expect(page.locator('.error')).toContainText('10:00');
        });
    });

    test.describe('Broneeringu kustutamine', () => {
        test('delete page shows booking details as readonly fields', async ({ page }, testInfo) => {
            const date = testDate(10, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Kustuta Klient',
                reg_nr: 'DEL001',
                broneeritud_aeg: date,
                algus_aeg: '13:00',
                lopp_aeg: '15:00',
                kirjeldus: 'Kustutamise test',
            });

            await openDailyView(page, date);
            await followLink(page.locator('.daily-view-table a[href*="kustuta_aeg.php"]').first());

            await expect(page.locator('input[name="kliendi_nimi"]')).toHaveValue('Kustuta Klient');
            await expect(page.locator('input[name="reg_nr"]')).toHaveValue('DEL001');
            await expect(page.locator('textarea')).toHaveValue('Kustutamise test');
            await expect(page.locator('input[name="confirm_delete"]')).toBeVisible();
        });

        test('confirming delete redirects to calendar', async ({ page }, testInfo) => {
            const date = testDate(11, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Eemaldatav Klient',
                broneeritud_aeg: date,
                algus_aeg: '09:00',
                lopp_aeg: '10:00',
            });

            await openDailyView(page, date);
            await followLink(page.locator('.daily-view-table a[href*="kustuta_aeg.php"]').first());
            await page.click('input[name="confirm_delete"]');

            await expect(page).toHaveURL(/kalender\.php/);
        });

        test('deleted booking no longer appears in daily view', async ({ page }, testInfo) => {
            const date = testDate(12, testInfo);
            await ensureCleanDate(page, date);
            await createBooking(page, {
                kliendi_nimi: 'Kaob Ära Klient',
                broneeritud_aeg: date,
                algus_aeg: '09:00',
                lopp_aeg: '10:00',
            });

            await openDailyView(page, date);
            await followLink(page.locator('.daily-view-table a[href*="kustuta_aeg.php"]').first());
            await page.click('input[name="confirm_delete"]');
            await page.waitForURL(/kalender\.php/);

            await openDailyView(page, date);
            await expect(page.locator('.daily-view-table')).not.toContainText('Kaob Ära Klient');
        });
    });
});
