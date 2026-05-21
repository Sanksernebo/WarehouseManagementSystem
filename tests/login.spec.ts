import { test, expect } from '@playwright/test';
import { BASE_URL, TEST_USER } from './helpers';

test.describe('Login success and failed', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto(`${BASE_URL}/src/login/login.php`);
    });

    test('redirects to main page on successful login', async ({ page }) => {
        await page.fill('input[name="username"]', TEST_USER.username);
        await page.fill('input[name="password"]', TEST_USER.password);
        await page.click('input[type="submit"]');
        await expect(page).toHaveURL(/index\.php/);
    });

    test('shows error and stays on login page for wrong credentials', async ({ page }) => {
        await page.fill('input[name="username"]', 'wrong');
        await page.fill('input[name="password"]', 'wrong');
        await page.click('input[type="submit"]');
        await expect(page).toHaveURL(/login\.php/);
        await expect(page.locator('.error')).toHaveText('Vale kasutajanimi või parool!');
    });
});
