import { expect, test, type Page } from '@playwright/test';

// Seeded by the `setono_loyalty` fixture suite: this customer has a loyalty balance of 5000 and one
// earn transaction.
const CUSTOMER = { email: 'shop@example.com', password: 'sylius' };

async function login(page: Page): Promise<void> {
    await page.goto('/en_US/login');
    await page.fill('input[name="_username"]', CUSTOMER.email);
    await page.fill('input[name="_password"]', CUSTOMER.password);
    await page.click('[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('shop loyalty account', () => {
    test('the account menu links to the loyalty page, which shows balance + history', async ({ page }) => {
        await login(page);
        await page.goto('/en_US/account/dashboard');

        const menuLink = page.locator('a[href$="/account/loyalty"]');
        await expect(menuLink.first()).toBeVisible();
        await menuLink.first().click();
        await page.waitForLoadState('networkidle');

        // Balance hero reflects the seeded balance.
        await expect(page.locator('[data-test-loyalty-balance]')).toBeVisible();
        await expect(page.locator('[data-test-loyalty-balance-value]')).toHaveText('5000');

        // History renders at least the seeded earn transaction.
        await expect(page.locator('[data-test-loyalty-history]')).toBeVisible();
        await expect(page.locator('[data-test-loyalty-history-row]').first()).toBeVisible();
    });

    test('the loyalty page requires authentication', async ({ page }) => {
        await page.goto('/en_US/account/loyalty');

        await expect(page).toHaveURL(/\/login$/);
    });
});
