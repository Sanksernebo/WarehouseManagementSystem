import { test, expect } from '@playwright/test';

test.describe('Login success and failed', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the login page before each test
    await page.goto('http://localhost:8000/src/login/login.php');
  });

  test('should redirect to the main page on successful login', async ({ page }) => {
    // Assuming input fields have names
    await page.fill('input[name="username"]', 'Sanks');
    await page.fill('input[name="password"]', 'qwerty');
    await page.click('input[type="submit"]');
    // Check for redirection
    expect(page.url()).toContain('index.php');
  });

  test('should show an error on incorrect login', async ({ page }) => {
    await page.fill('input[name="username"]', 'wrong');
    await page.fill('input[name="password"]', 'wrong');
    await page.click('input[type="submit"]');
    // Check that the page did not redirect
    expect(page.url()).toContain('login.php');
    // Check for error message, assuming there is a text or HTML element to show this
    await expect(page.locator('.error')).toHaveText('Vale kasutajanimi või parool!');
  });
});