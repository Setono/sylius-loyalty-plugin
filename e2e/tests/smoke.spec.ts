import { expect, test } from '@playwright/test';

test.describe('smoke', () => {
    test('shop homepage renders', async ({ page }) => {
        await page.goto('/en_US/');

        await expect(page.locator('body')).toContainText(/./);
        expect(page.url()).toContain('/en_US/');
    });

    test('admin login page renders and accepts the fixture credentials', async ({ page }) => {
        await page.goto('/admin/login');

        await page.getByLabel('Username').fill('sylius');
        await page.getByLabel('Password').fill('sylius');
        await page.getByRole('button', { name: 'Login' }).click();

        await expect(page).toHaveURL(/\/admin(\/|$)/);
        await expect(page.locator('body')).toContainText('Dashboard');
    });
});
