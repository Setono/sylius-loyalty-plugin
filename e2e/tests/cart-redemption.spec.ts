import { expect, test, type Page } from '@playwright/test';

// The default Sylius fixtures create this shop customer; the `setono_loyalty` fixture suite gives
// their loyalty account a balance so the redemption widget has something to spend.
const CUSTOMER = { email: 'shop@example.com', password: 'sylius' };

async function login(page: Page): Promise<void> {
    await page.goto('/en_US/login');
    await page.fill('input[name="_username"]', CUSTOMER.email);
    await page.fill('input[name="_password"]', CUSTOMER.password);
    await page.click('[type="submit"]');
    await page.waitForLoadState('networkidle');
}

async function addFirstProductToCart(page: Page): Promise<void> {
    await page.goto('/en_US/');
    await page.locator('a[href*="/products/"]').first().click();
    await page.locator('form button[type="submit"]', { hasText: /add to cart/i }).first().click();
    await page.waitForLoadState('networkidle');
}

test.describe('cart loyalty redemption', () => {
    test('a logged-in customer sees the widget and can request points', async ({ page }) => {
        await login(page);
        await addFirstProductToCart(page);

        await page.goto('/en_US/cart');

        // The widget renders for the logged-in customer.
        await expect(page.locator('[data-test-loyalty-redemption]')).toBeVisible();

        // Requesting points persists onto the cart.
        await page.locator('[data-test-loyalty-points]').fill('1000');
        await page.locator('[data-test-loyalty-apply]').click();
        await page.waitForLoadState('networkidle');

        await expect(page.locator('[data-test-loyalty-points]')).toHaveValue('1000');
    });

    test('the widget is not shown to a guest', async ({ page }) => {
        await page.goto('/en_US/cart');

        await expect(page.locator('[data-test-loyalty-redemption]')).toHaveCount(0);
    });
});
