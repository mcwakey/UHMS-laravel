import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  timeout: 60_000,
  use: {
    baseURL: process.env.UHMS_BASE_URL ?? 'http://localhost:8000',
    trace: 'on-first-retry',
  },
});
