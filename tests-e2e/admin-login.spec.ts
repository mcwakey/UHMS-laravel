import { test } from '@playwright/test';

test.describe.skip('legacy admin login smoke', () => {
  test('migrated to tests/auth.spec.ts', () => {
    // Kept as a pointer for older local commands. The active Level 1 auth suite
    // lives in tests-e2e/tests/auth.spec.ts and uses UHMS_* environment vars.
  });
});
