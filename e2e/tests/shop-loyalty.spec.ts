import { expect, test } from '@playwright/test';
import { loginShop } from './helpers';

test.describe('shop loyalty dashboard', () => {
    test('shows the balance hero, expiring-soon callout, and running-balance history', async ({ page }) => {
        // This account is dedicated to this spec, so its ledger stays exact
        await loginShop(page, 'history@example.com');
        await page.goto('/en_US/account/loyalty');

        // Fixture history: +2000 goodwill, +300 expiring, -150 correction = 2150
        await expect(page.locator('[data-test-loyalty-hero] .value')).toHaveText('2150');

        // The 300-point lot expires within the 30-day horizon, minus the 150-point debit that
        // replay attributes to it first (earliest expiry is consumed first)
        await expect(page.locator('[data-test-loyalty-expiring]')).toContainText('150 points expire');

        const rows = page.locator('[data-test-loyalty-history] tbody tr');
        await expect(rows).toHaveCount(3);

        // Newest first with bank-statement running balances
        await expect(rows.nth(0)).toContainText('-150');
        await expect(rows.nth(0)).toContainText('2150');
        await expect(rows.nth(1)).toContainText('+300');
        await expect(rows.nth(1)).toContainText('2300');
        await expect(rows.nth(2)).toContainText('+2000');
        await expect(rows.nth(2)).toContainText('2000');

        // Credit rows show their expiry date
        await expect(rows.nth(1).locator('[data-test-expiry]')).toBeVisible();

        // The account menu links here
        await expect(page.locator('a:has-text("My loyalty")').first()).toBeVisible();
    });
});

test.describe('cart redemption', () => {
    test('applies a preset, uses max with clamping, and removes the redemption', async ({ page }) => {
        await loginShop(page, 'loyalty@example.com');

        await page.goto('/en_US/products/sport-basic-white-t-shirt');
        await page.locator('button:has-text("Add to cart")').click();
        await expect(page).toHaveURL(/\/cart\//);

        const widget = page.locator('#setono-sylius-loyalty-redemption');
        await expect(widget).toBeVisible();
        await expect(widget.locator('[data-test-loyalty-balance]')).toHaveText('2150');

        // Presets show the currency equivalent — the one place it appears
        await expect(widget.locator('[data-test-loyalty-presets] button').first()).toContainText('(');

        // Use max records the whole balance; the applied amount is min(requested, cap) — and
        // fixture prices are random, so either the full 2150 applies or the clamp notice
        // explains the reduction from 2150
        await widget.locator('[data-test-loyalty-use-max]').click();
        const applied = page.locator('#setono-sylius-loyalty-redemption [data-test-loyalty-applied]');
        await expect(applied).toBeVisible();
        const clamped = page.locator('#setono-sylius-loyalty-redemption [data-test-loyalty-clamped]');
        if (await clamped.count() > 0) {
            await expect(clamped).toContainText('2150');
        } else {
            await expect(applied).toContainText('2150');
        }

        // Removing restores the pre-redemption state
        await page.locator('#setono-sylius-loyalty-redemption [data-test-loyalty-remove]').click();
        await expect(page.locator('#setono-sylius-loyalty-redemption [data-test-loyalty-applied]')).toHaveCount(0);
    });

    test('is hidden for customers below the redemption minimum', async ({ page }) => {
        await loginShop(page, 'fresh@example.com');

        await page.goto('/en_US/products/sport-basic-white-t-shirt');
        await page.locator('button:has-text("Add to cart")').click();
        await expect(page).toHaveURL(/\/cart\//);

        await expect(page.locator('#setono-sylius-loyalty-redemption')).toHaveCount(0);
    });
});
