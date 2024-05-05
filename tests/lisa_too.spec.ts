import { test, expect } from '@playwright/test';

// Utility function to ensure the user is logged in
async function ensureLoggedIn(page) {

    // Attempt to navigate to the main page
    await page.goto('http://localhost:8000/index.php');

    // Check if certain elements that indicate a logged-in state are present
    if (await page.locator('nav').isVisible() && await page.locator('.logout-button').isVisible()) {
        // User is already logged in
        return;
    } else {
        // User is not logged in, perform the login
        await page.goto('http://localhost:8000/src/login/login.php');
        await page.fill('input[name="username"]', 'Sanks');
        await page.fill('input[name="password"]', 'qwerty');
        await page.click('input[type="submit"]');
        // Check to confirm login was successful
        await expect(page.url()).toContain('index.php');
    }
}

test.describe('Tehtud Tööd testid', () => {
    test.beforeEach(async ({ page }) => {
        await ensureLoggedIn(page);
    });
    test('test 1: Searchbar functionality for RegNr with multiple results', async ({ page }) => {
        await page.waitForLoadState('networkidle');
        await page.click('a[href="src/tehtud_tood/tehtud_tood.php"]');

        // Input a specific RegNr in the searchBar and trigger the search
        const testCode = '45ZGI';
        await page.fill('#searchBar', testCode);
        await page.keyboard.press('Enter');

        // Locator for the first cells in each visible row
        const cells = page.locator('tbody tr:not([style*="display: none"]) td:first-child');

        // Get count of cells
        const count = await cells.count();

        // Loop through each cell and check if it contains the testCode
        for (let i = 0; i < count; i++) {
            const cellText = await cells.nth(i).textContent();
            expect(cellText).toContain(testCode);
        }
    });

    test('test 2: Add a new job to database and check the table', async ({ page }) => {
        // Navigate to Tehtud Tööd page
        await page.click('a[href="src/tehtud_tood/tehtud_tood.php"]');

        await page.click('a[href="lisa_too.php"]');
        // Check that are on the correct page
        await expect(page.url()).toContain('lisa_too.php');

        // Test data for form
        const RegNr = '911ABC';
        const Date = '2024-09-23';
        const Odomeeter = '102030';
        const Description = 'Test Test some work';

        // Fill the form
        await page.fill('input[name="RegNr"]', RegNr);
        await page.fill('input[name=Kuupaev]', Date);
        await page.fill('input[name="Odomeeter"]', Odomeeter);
        await page.fill('textarea[name="Tehtud_tood"]', Description);

        await page.click('input[type="submit"]');
        // Check that after add, get redirected to Tehtud Tööd page
        expect(page.url()).toContain('tehtud_tood.php');

        await expect(page.locator('#myTable tbody tr:first-of-type td:nth-of-type(1)')).toHaveText(RegNr);
    });
});