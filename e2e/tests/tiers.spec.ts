import { expect, test } from '@playwright/test';
import { loginAdmin, loginShop } from './helpers';

test.describe('shop tier UI', () => {
    test('shows the tier badge and progress toward the next tier', async ({ page }) => {
        // history@example.com has 2300 lifetime points: Silver (1000), progressing to Gold (5000)
        await loginShop(page, 'history@example.com');
        await page.goto('/en_US/account/loyalty');

        await expect(page.locator('[data-test-tier-badge]')).toContainText('Silver');
        await expect(page.locator('[data-test-loyalty-tier]')).toContainText('25% points bonus');

        const progress = page.locator('[data-test-tier-progress]');
        await expect(progress).toContainText('2300 / 5000');
        await expect(progress).toContainText('Gold');
        await expect(page.locator('[data-test-tier-celebration]')).toHaveCount(0);
    });
});

test.describe('earn hints', () => {
    test('the product page shows the hint to anonymous visitors with a variant map', async ({ page }) => {
        await page.goto('/en_US/products/sport-basic-white-t-shirt');

        const hint = page.locator('[data-test-earn-hint]');
        await expect(hint).toBeVisible();
        await expect(hint).toContainText(/You will earn \d+ points/);
        expect(await page.locator('#setono-sylius-loyalty-earn-hint-map span').count()).toBeGreaterThan(0);
    });

    test('the cart shows the order earn hint including the tier multiplier', async ({ page }) => {
        await loginShop(page, 'loyalty@example.com');

        await page.goto('/en_US/products/sport-basic-white-t-shirt');
        await page.locator('button:has-text("Add to cart")').click();
        await expect(page).toHaveURL(/\/cart\//);

        await expect(page.locator('[data-test-cart-earn-hint]')).toContainText(/earns ~\d+ points/);
    });
});

test.describe('admin tiers', () => {
    test('the tiers grid lists the fixture tiers', async ({ page }) => {
        await loginAdmin(page);
        await page.goto('/admin/loyalty/tiers/');

        await expect(page.locator('tr', { hasText: 'Silver' })).toBeVisible();
        await expect(page.locator('tr', { hasText: 'Gold' })).toBeVisible();
    });

    test('the accounts grid shows the tier and the dashboard has the Phase 2 widgets', async ({ page }) => {
        await loginAdmin(page);
        await page.goto('/admin/loyalty/accounts');
        await expect(page.locator('tr', { hasText: 'loyalty@example.com' })).toContainText('Silver');

        await page.goto('/admin/loyalty');
        await expect(page.locator('[data-test-stat-redemption-rate]')).toBeVisible();
        await expect(page.locator('[data-test-stat-active-accounts]')).toBeVisible();
        await expect(page.locator('[data-test-card-tiers]')).toBeVisible();
    });
});
