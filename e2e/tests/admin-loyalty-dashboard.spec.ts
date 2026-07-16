import { expect, test, type Page } from '@playwright/test';

const ADMIN = { username: 'sylius', password: 'sylius' };

async function loginAdmin(page: Page): Promise<void> {
    await page.goto('/admin/login');
    await page.fill('input[name="_username"]', ADMIN.username);
    await page.fill('input[name="_password"]', ADMIN.password);
    await page.click('[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('admin loyalty dashboard', () => {
    test('the Marketing menu opens the dashboard, which shows numeric stats', async ({ page }) => {
        await loginAdmin(page);
        await page.goto('/admin');

        const menuLink = page.locator('a[href$="/admin/loyalty/"]');
        await expect(menuLink.first()).toBeVisible();
        await menuLink.first().click();
        await page.waitForLoadState('networkidle');

        await expect(page.locator('[data-test-loyalty-dashboard]')).toBeVisible();

        // Every stat renders as a non-negative integer.
        for (const stat of ['accounts', 'outstanding', 'earned', 'redeemed']) {
            await expect(page.locator(`[data-test-loyalty-stat-${stat}]`)).toHaveText(/^\d+$/);
        }
    });

    test('the dashboard links through to the accounts grid', async ({ page }) => {
        await loginAdmin(page);
        await page.goto('/admin/loyalty/');
        await page.waitForLoadState('networkidle');

        await page.locator('[data-test-loyalty-accounts-link]').click();
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/\/admin\/loyalty\/accounts\/$/);
        await expect(page.locator('table')).toBeVisible();
    });
});
