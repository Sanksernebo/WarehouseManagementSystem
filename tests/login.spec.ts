import { test, expect } from '@playwright/test';
import { BASE_URL, TEST_USER } from './helpers';

test.describe('Login success and failed', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${BASE_URL}/src/login/login.php`);
  });

  test('should redirect to the main page on successful login', async ({ page }) => {
    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('input[type="submit"]');
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