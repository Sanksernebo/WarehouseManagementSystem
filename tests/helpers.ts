import { expect, Page } from '@playwright/test';

export const BASE_URL = 'http://localhost:8000';

export const TEST_USER = {
    username: 'TestUser',
    password: 'testtest',
};

export async function ensureLoggedIn(page: Page) {
    await page.goto(`${BASE_URL}/index.php`);
    if (await page.locator('nav').isVisible() && await page.locator('.logout-button').isVisible()) {
        return;
    }
    await page.goto(`${BASE_URL}/src/login/login.php`);
    await page.fill('input[name="username"]', TEST_USER.username);
    await page.fill('input[name="password"]', TEST_USER.password);
    await page.click('input[type="submit"]');
    await expect(page).toHaveURL(/index\.php/);
}
