import { expect, test, type Page } from '@playwright/test';

// Seeded by Sylius' default fixtures.
const ADMIN = { username: 'sylius', password: 'sylius' };

// Seeded by the `setono_loyalty` fixture suite: shop@example.com has a 5000-point balance on FASHION_WEB.
const SEEDED_ACCOUNT = { email: 'shop@example.com', channel: 'FASHION_WEB', balance: '5000' };

async function loginAdmin(page: Page): Promise<void> {
    await page.goto('/admin/login');
    await page.fill('input[name="_username"]', ADMIN.username);
    await page.fill('input[name="_password"]', ADMIN.password);
    await page.click('[type="submit"]');
    await page.waitForLoadState('networkidle');
}

test.describe('admin loyalty accounts grid', () => {
    test('the accounts grid lists seeded accounts', async ({ page }) => {
        await loginAdmin(page);
        await page.goto('/admin/loyalty/accounts/');
        await page.waitForLoadState('networkidle');

        // The seeded account is listed with its channel, balance and an "enabled" status label.
        const row = page.locator('table tbody tr', { hasText: SEEDED_ACCOUNT.email });
        await expect(row).toBeVisible();
        await expect(row).toContainText(SEEDED_ACCOUNT.channel);
        await expect(row).toContainText(SEEDED_ACCOUNT.balance);
        await expect(row).toContainText('Enabled');
    });

    test('filtering by disabled state hides the seeded (enabled) account', async ({ page }) => {
        await loginAdmin(page);
        await page.goto('/admin/loyalty/accounts/');
        await page.waitForLoadState('networkidle');

        // The account is listed before filtering.
        await expect(page.locator('table tbody tr', { hasText: SEEDED_ACCOUNT.email })).toBeVisible();

        // Filtering to "disabled" removes it — the seeded account is enabled.
        await page.locator('select[name="criteria[enabled]"]').selectOption('false');
        await page.locator('button:has-text("Filter")').first().click();
        await page.waitForLoadState('networkidle');

        await expect(page.locator('table tbody tr', { hasText: SEEDED_ACCOUNT.email })).toHaveCount(0);
    });
});
