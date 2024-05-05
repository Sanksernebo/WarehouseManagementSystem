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

test.describe('Index page tests', () => {
  test.beforeEach(async ({ page }) => {
    await ensureLoggedIn(page);
  });

  test('test 1: Check navigation bar elements', async ({ page }) => {
    await expect(page.locator('nav')).toBeVisible();
    // check for logo in nav-bar
    await expect(page.locator('.logo img')).toHaveAttribute('src', 'src/img/cartehniklogo_valge.svg');
    // check for nav-bar link
    const link = page.locator('a[href="src/tehtud_tood/tehtud_tood.php"]');
    await expect(link).toHaveAttribute('href', 'src/tehtud_tood/tehtud_tood.php');
  });

  test('test 2: Searchbar functionality for specific Tootekood', async ({ page }) => {
    await page.waitForLoadState('networkidle');  // Wait for the page to be idle

    // Input a specific Tootekood in the searchBar and trigger the search
    const testCode = '750-';
    await page.fill('#searchBar', testCode);

    await page.keyboard.press('Enter');

    // Check if at least one visible row contains the expected product code
    const rowsDisplayingCode = page.locator(`tbody tr:not([style*="display: none"]) td:first-child`);
    await expect(rowsDisplayingCode).toHaveText(new RegExp(testCode));  // Using a regular expression to check for partial match

    // Verify that the rows showing are correct
    const visibleRows = page.locator('tbody tr:not([style*="display: none"])');
    await expect(visibleRows).toHaveCount(1);  // Check if only one row is visible

    // Ensure each visible cell in the first column contains the test code if visible
    const visibleRowsCodes = page.locator('tbody tr:not([style*="display: none"]) td:first-child');
    await visibleRowsCodes.allTextContents().then(texts => {
      texts.forEach(text => {
        expect(text).toContain(testCode);
      });
    });
  });

  test('test 3: Change product data', async ({ page }) => {
    const productCode = '148-H203WK';
    const updateLink = await page.locator(`td:text("${productCode}")`).locator('xpath=..').locator('td:last-child a').first();

    await updateLink.click();

    await page.waitForSelector('form[name="frmUser"]');

    // New name in the Nimetus field
    const newName = 'Kütusefilter Ford Transit';
    await page.fill('input[name="Nimetus"]', newName);

    // Submit the form
    await page.click('input[type="submit"]');

    // Verify the redirection to index page
    await expect(page.url()).toContain('index.php');

    // Attempt to fetch the text content of the updated name field
    const nameCell = page.locator(`td:text("${productCode}")`).locator('xpath=..').locator('td:nth-child(2)');
    await expect(nameCell).toHaveCount(1);

    const updatedName = await nameCell.textContent();

    // Check if updatedName is not null or empty
    if (updatedName) {
      expect(updatedName.trim()).toBe(newName);
    } else {
      throw new Error('No updated name found or element does not contain text.');
    }
  });
});