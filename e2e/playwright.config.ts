import { defineConfig, devices } from '@playwright/test';

/**
 * The suite runs against the Sylius test application in ../tests/Application.
 *
 * Prerequisites (handled by CI, see .github/workflows/build.yaml):
 *  - composer dependencies installed
 *  - test app assets built (yarn install && yarn build in tests/Application)
 *  - database created with schema and fixtures loaded (APP_ENV=test)
 *
 * The database is seeded once and shared by all specs, so the suite runs with a
 * single worker for determinism.
 */
export default defineConfig({
    testDir: './tests',
    workers: 1,
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    timeout: 30_000,
    use: {
        baseURL: 'http://127.0.0.1:8080',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        locale: 'en_US',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
    webServer: {
        command: 'php -S 127.0.0.1:8080 -t ../tests/Application/public',
        url: 'http://127.0.0.1:8080',
        reuseExistingServer: !process.env.CI,
        env: {
            APP_ENV: 'test',
            APP_DEBUG: '0',
        },
    },
});
