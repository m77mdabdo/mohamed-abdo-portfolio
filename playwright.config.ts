import { defineConfig } from '@playwright/test'

// Browser checks against a running local server (php artisan serve --port=8020).
// BASE=http://127.0.0.1:8020 npx playwright test --trace off
export default defineConfig({
  testDir: 'tests/e2e',
  outputDir: 'qa-output/test-results',
  timeout: 90_000,
  workers: 2,
  reporter: [['list']],
  use: { baseURL: process.env.BASE || 'http://127.0.0.1:8020', trace: 'off' },
})
