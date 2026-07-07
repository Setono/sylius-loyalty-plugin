import { expect, test } from '@playwright/test';
import { loginAdmin } from './helpers';

test.describe('admin loyalty', () => {
    test.beforeEach(async ({ page }) => {
        await loginAdmin(page);
    });

    test('the dashboard shows stats and navigation cards', async ({ page }) => {
        await page.goto('/admin/loyalty');

        await expect(page.locator('[data-test-stat-accounts] .value')).not.toHaveText('0');
        await expect(page.locator('[data-test-card-accounts]')).toBeVisible();
        await expect(page.locator('[data-test-card-rules]')).toBeVisible();
        await expect(page.locator('[data-test-card-program]')).toBeVisible();
        await expect(page.locator('[data-test-card-tester]')).toBeVisible();
    });

    test('the accounts grid links to the ledger inspector with healthy invariants', async ({ page }) => {
        await page.goto('/admin/loyalty/accounts');

        // history@example.com is read-only across the suite, so its ledger counts stay exact
        // even on re-runs (the adjustment spec below appends rows to loyalty@example.com)
        const row = page.locator('tr', { hasText: 'history@example.com' });
        await expect(row).toContainText('2150');
        await row.locator('a:has-text("Inspect")').click();

        await expect(page.locator('[data-test-account-balance] .value')).toHaveText('2150');
        await expect(page.locator('[data-test-invariants-ok]')).toBeVisible();
        await expect(page.locator('[data-test-lots] tbody tr')).toHaveCount(2);
        await expect(page.locator('[data-test-ledger] tbody tr')).toHaveCount(3);
    });

    test('a manual adjustment is written to the ledger with its reason and note', async ({ page }) => {
        await page.goto('/admin/loyalty/accounts');
        await page.locator('tr', { hasText: 'loyalty@example.com' }).locator('a:has-text("Inspect")').click();

        const form = page.locator('[data-test-adjustment-form]');
        await form.locator('input[name="points"]').fill('50');
        await form.locator('input[name="note"]').fill('e2e credit');
        await form.evaluate((f: HTMLFormElement) => f.submit());
        await expect(page.locator('[data-test-account-balance] .value')).toHaveText('2200');

        // Net the balance back out so the suite stays re-runnable
        const debitForm = page.locator('[data-test-adjustment-form]');
        await debitForm.locator('input[name="points"]').fill('-50');
        await debitForm.locator('input[name="note"]').fill('e2e debit');
        await debitForm.evaluate((f: HTMLFormElement) => f.submit());
        await expect(page.locator('[data-test-account-balance] .value')).toHaveText('2150');
    });

    test('the earning rules grid lists the fixture rules', async ({ page }) => {
        await page.goto('/admin/loyalty/earning-rules/');

        await expect(page.locator('tr', { hasText: 'Base rate: 1 point per 1.00' })).toBeVisible();
        await expect(page.locator('tr', { hasText: 'Weekend double points (dry run)' })).toBeVisible();
        await expect(page.locator('tr', { hasText: 'Registration bonus' })).toBeVisible();
    });

    test('the rule tester evaluates an order read-only with per-item claims', async ({ page }) => {
        // Fixture order numbers are assigned sequentially, so the first one always exists
        await page.goto('/admin/loyalty/rule-tester?order=000000001');

        await expect(page.locator('[data-test-tester-award]')).toContainText('Final award');
        await expect(page.locator('[data-test-tester-rules] tbody tr', { hasText: 'Base rate: 1 point per 1.00' })).toBeVisible();
        await expect(page.locator('[data-test-tester-claims] tbody tr').first()).toBeVisible();
    });

    // CodeMirror loads as ESM from the CDN; retry to absorb CDN flakiness
    test.describe(() => {
        test.describe.configure({ retries: 2 });

        test('the expression editor mounts with completion and inline linting', async ({ page }) => {
            test.slow();

        await page.goto('/admin/loyalty/earning-rules/new');

        // CodeMirror replaces the textarea; wait for the editable surface itself, not just
        // the wrapper, so the following DOM-level inserts never race the mount
        const editor = page.locator('.setono-sylius-loyalty-expression-editor').first();
        const content = page.locator('.setono-sylius-loyalty-expression-editor .cm-content').first();
        await expect(content).toBeVisible({ timeout: 45000 });

        // Insert text through the browser's input pipeline (keyboard focus on CodeMirror's
        // contenteditable is flaky headless; execCommand still triggers completion + linting).
        // page.evaluate instead of locator.evaluate: the open completion tooltip re-renders
        // the editor subtree, which can wedge the locator's element resolution mid-test.
        const insert = (text: string) =>
            page.evaluate((value) => {
                const content = document.querySelector('.setono-sylius-loyalty-expression-editor .cm-content');
                if (!(content instanceof HTMLElement)) {
                    throw new Error('editor content not found');
                }
                content.focus();
                document.execCommand('insertText', false, value);
            }, text);

        await insert('customer.');

        // Nested, catalog-driven completion offers the customer view's members
        const completion = page.locator('.cm-tooltip-autocomplete');
        await expect(completion).toBeVisible({ timeout: 10000 });
        await expect(completion).toContainText('email');

        // An off-whitelist member produces a lint diagnostic from the server
        await insert('password');
        await expect(editor.locator('.cm-lint-marker-error, .cm-lintRange-error').first()).toBeVisible({ timeout: 10000 });

        // The reference panel renders from the same catalog and inserts examples
        const reference = page.locator('.setono-sylius-loyalty-expression-reference').first();
        await expect(reference).toBeVisible();
        await reference.locator('summary').click();
        await expect(reference).toContainText('taxon_total');
        await reference.locator('a', { hasText: 'Weekend bonus' }).click();
        await expect(page.locator('.setono-sylius-loyalty-expression-editor .cm-content').first()).toContainText('day_of_week()');
        });
    });
});
