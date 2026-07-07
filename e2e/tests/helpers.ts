import { expect, Page } from '@playwright/test';

export async function loginShop(page: Page, email: string, password = 'sylius'): Promise<void> {
    await page.goto('/en_US/login');
    await page.locator('#_username').fill(email);
    await page.locator('#_password').fill(password);
    await Promise.all([
        page.waitForNavigation(),
        page.locator('#_username').locator('xpath=ancestor::form').evaluate((form: HTMLFormElement) => form.submit()),
    ]);
    await expect(page.locator('a[href*="logout"]').first()).toBeVisible();
}

export async function loginAdmin(page: Page): Promise<void> {
    await page.goto('/admin/login');
    await page.locator('#_username').fill('sylius');
    await page.locator('#_password').fill('sylius');
    await Promise.all([
        page.waitForNavigation(),
        page.locator('#_username').locator('xpath=ancestor::form').evaluate((form: HTMLFormElement) => form.submit()),
    ]);
    await expect(page).toHaveURL(/\/admin(\/|$)/);
}
