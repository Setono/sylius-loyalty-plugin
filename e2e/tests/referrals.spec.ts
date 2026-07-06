import { expect, test } from '@playwright/test';
import { loginAdmin, loginShop } from './helpers';

test.describe('referrals', () => {
    test('the share block, landing URL, query parameter, and registration attribution', async ({ page, context }) => {
        // The dashboard generates the code lazily and shows the share block
        await loginShop(page, 'loyalty@example.com');
        await page.goto('/en_US/account/loyalty');

        const code = (await page.locator('[data-test-referral-code]').textContent() ?? '').trim();
        expect(code).toMatch(/^[0-9A-HJKMNP-TV-Z]{8}$/);
        await expect(page.locator('[data-test-referral-url]')).toHaveValue(new RegExp(`/r/${code}$`));

        // The landing URL sets the attribution cookie and redirects home
        await page.goto('/en_US/logout');
        await context.clearCookies({ name: 'setono_sylius_loyalty_ref' });
        await page.goto(`/en_US/r/${code}`);
        await expect(page).toHaveURL(/\/en_US\/$/);
        expect((await context.cookies()).find(c => c.name === 'setono_sylius_loyalty_ref')?.value).toBe(code);

        // The query parameter works on any shop URL — last click wins
        await context.clearCookies({ name: 'setono_sylius_loyalty_ref' });
        await page.goto(`/en_US/products/sport-basic-white-t-shirt?ref=${code}`);
        expect((await context.cookies()).find(c => c.name === 'setono_sylius_loyalty_ref')?.value).toBe(code);

        // Registering with the cookie creates a pending referral, visible in the admin grid
        const email = `referee-${Date.now()}@example.com`;
        await page.goto('/en_US/register');
        await page.locator('#sylius_customer_registration_firstName').fill('Ref');
        await page.locator('#sylius_customer_registration_lastName').fill('Eree');
        await page.locator('#sylius_customer_registration_email').fill(email);
        await page.locator('#sylius_customer_registration_user_plainPassword_first').fill('password123');
        await page.locator('#sylius_customer_registration_user_plainPassword_second').fill('password123');
        await Promise.all([
            page.waitForNavigation(),
            page.locator('form[name="sylius_customer_registration"]').evaluate((f: HTMLFormElement) => f.submit()),
        ]);

        await loginAdmin(page);
        await page.goto('/admin/loyalty/referrals');
        const row = page.locator('tr', { hasText: email });
        await expect(row).toBeVisible();
        await expect(row).toContainText('pending');
        await expect(row).toContainText(code);
    });
});
