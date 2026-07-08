import { defineConfig, devices } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8081';

export default defineConfig({
    testDir: './tests',
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
    // Set E2E_NO_SERVER=1 to run against an already-running shop (e.g. during local development).
    webServer: process.env.E2E_NO_SERVER
        ? undefined
        : {
              command: 'symfony server:start --port=8081 --no-tls',
              cwd: '../tests/Application',
              env: { APP_ENV: 'dev' },
              url: baseURL,
              reuseExistingServer: !process.env.CI,
              timeout: 120_000,
          },
});
